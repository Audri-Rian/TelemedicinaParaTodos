# Resiliência Operacional — Circuit Breaker, Retry, Idempotência

Este documento especifica como o sistema se comporta quando integrações falham, garantindo degradação graceful e recuperação automática.

---

## 1. Princípio: falhas são normais

APIs externas **vão** falhar — timeout, 500, rede instável, manutenção. O sistema deve:

1. **Não travar** quando o parceiro cai
2. **Não perder dados** (pedidos de exame, resultados)
3. **Recuperar automaticamente** quando o parceiro volta
4. **Informar** admin e usuário sobre o estado

---

## 2. Circuit Breaker

O circuit breaker previne chamadas repetidas a um parceiro que está fora do ar, evitando cascata de erros e saturação de filas.

### 2.1 Estados do circuito

```
     ┌──────────┐
     │  CLOSED  │ ← Estado normal: chamadas passam
     │          │
     └────┬─────┘
          │ N falhas consecutivas
          ▼
     ┌──────────┐
     │   OPEN   │ ← Circuito aberto: chamadas bloqueadas
     │          │   Retorna fallback imediato
     └────┬─────┘
          │ Após timeout de cooling
          ▼
     ┌──────────┐
     │HALF-OPEN │ ← Teste: permite 1 chamada
     │          │
     └────┬─────┘
          │
     ┌────┴────┐
     │         │
  Sucesso    Falha
     │         │
     ▼         ▼
  CLOSED     OPEN
```

### 2.2 Configuração por tipo de parceiro

| Parâmetro | Laboratório | Farmácia | Hospital | RNDS |
|-----------|-------------|----------|----------|------|
| **Falhas para abrir** | 5 | 3 | 5 | 10 |
| **Cooling timeout** | 60s | 30s | 60s | 120s |
| **Chamadas em half-open** | 1 | 1 | 1 | 2 |
| **Janela de contagem** | 60s | 30s | 60s | 120s |

### 2.3 Implementação

```php
// app/Integrations/Services/CircuitBreaker.php

class CircuitBreaker
{
    // Estado armazenado em Redis (compartilhado entre workers)
    // Chave: circuit_breaker:{partner_id}

    public function isAvailable(string $partnerId): bool
    {
        $state = Redis::get("circuit_breaker:{$partnerId}");

        return match ($state) {
            'open' => $this->shouldTryHalfOpen($partnerId),
            'half-open' => true,  // Permite tentativa de teste
            default => true,      // Closed ou inexistente
        };
    }

    public function recordSuccess(string $partnerId): void
    {
        Redis::del("circuit_breaker:{$partnerId}");
        Redis::del("circuit_breaker:{$partnerId}:failures");
    }

    public function recordFailure(string $partnerId): void
    {
        $failures = Redis::incr("circuit_breaker:{$partnerId}:failures");
        $threshold = $this->getThreshold($partnerId);

        if ($failures >= $threshold) {
            Redis::setex("circuit_breaker:{$partnerId}", $this->getCoolingTimeout($partnerId), 'open');
        }
    }
}
```

### 2.4 Comportamento quando circuito está aberto

| Operação | Comportamento |
|----------|--------------|
| **Envio de pedido de exame** | Pedido vai para `integration_queue` (status: queued). Será enviado quando circuito fechar |
| **Pull de resultados (cron)** | Skip silencioso. Log: "Circuit open for partner X, skipping sync" |
| **Pull de resultados (botão)** | Toast: "Laboratório temporariamente indisponível. Tentaremos novamente automaticamente" |
| **Webhook recebido** | Processa normalmente (circuito é por parceiro outbound, não inbound) |

### 2.5 Notificações de circuit breaker

| Evento | Notificação |
|--------|------------|
| Circuito **abriu** | Admin: "Integração com Lab X indisponível. Pedidos pendentes serão enviados quando reconectar" |
| Circuito **fechou** (recuperou) | Admin: "Integração com Lab X restaurada. N pedidos pendentes estão sendo processados" |
| Circuito aberto por **mais de 1h** | Admin (urgente): "Lab X está fora do ar há 1h. Verifique com o parceiro" |

---

## 3. Retry com Backoff Exponencial

### 3.1 Estratégia de retry

Quando uma operação falha e o circuit breaker está fechado, a operação entra na fila de retry com backoff exponencial + jitter.

```
Tentativa 1: falha → retry em 30s ± jitter
Tentativa 2: falha → retry em 2min ± jitter
Tentativa 3: falha → retry em 10min ± jitter
Tentativa 4: falha → retry em 30min ± jitter
Tentativa 5: falha → DESISTIR (marcar como falha final)
```

**Fórmula:**

```
delay = min(base_delay * 2^(attempt - 1), max_delay) + random(0, jitter)
```

| Parâmetro | Valor |
|-----------|-------|
| `base_delay` | 30 segundos |
| `max_delay` | 2 horas |
| `jitter` | 0 a 10 segundos |
| `max_attempts` | 5 (configurável por operação) |

### 3.2 Configuração por tipo de operação

| Operação | Max tentativas | Base delay | Max delay |
|----------|---------------|------------|-----------|
| Envio de pedido de exame | 5 | 30s | 2h |
| Pull de resultado | 3 | 60s | 30min |
| Envio de prescrição | 5 | 30s | 2h |
| Envio à RNDS | 10 | 60s | 6h |
| Webhook outbound | 5 | 10s | 1h |

### 3.3 Implementação (Laravel Jobs + RabbitMQ)

```php
// app/Integrations/Jobs/RetryIntegrationOperation.php

class RetryIntegrationOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [30, 120, 600, 1800, 7200]; // Segundos por tentativa

    public function handle(IntegrationService $service): void
    {
        $item = IntegrationQueueItem::findOrFail($this->queueItemId);

        // Verificar circuit breaker antes de tentar
        if (!$service->circuitBreaker->isAvailable($item->partner_integration_id)) {
            // Re-agendar para após o cooling
            $this->release($service->circuitBreaker->getCoolingTimeout($item->partner_integration_id));
            return;
        }

        $item->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $service->executeOperation($item);
            $item->update(['status' => 'completed', 'completed_at' => now()]);
            $service->circuitBreaker->recordSuccess($item->partner_integration_id);
        } catch (\Throwable $e) {
            $item->update([
                'attempts' => $item->attempts + 1,
                'last_error' => $e->getMessage(),
            ]);
            $service->circuitBreaker->recordFailure($item->partner_integration_id);
            throw $e; // Laravel faz retry com backoff
        }
    }
}
```

### 3.4 Fila dedicada

Operações de integração devem usar uma **fila RabbitMQ dedicada** para não competir com jobs internos (notificações, e-mails):

```php
// config/queue.php
'connections' => [
    'rabbitmq' => [
        'queue' => env('RABBITMQ_QUEUE', 'default'),
        // ...
    ],
],

// No job:
public $queue = 'integrations';
```

---

## 4. Idempotência

### 4.1 Problema

Se um resultado de exame chega **duas vezes** (webhook duplicado, retry do lab), não pode duplicar no prontuário.

### 4.2 Estratégia: chave de idempotência

**Inbound (recebemos de parceiro):**

| Tipo de operação | Chave de idempotência |
|------------------|----------------------|
| Resultado de exame | `partner_id + external_id` (ou `partner_id + fhir_resource_id`) |
| Dispensação de receita | `partner_id + prescription_id + dispensation_id` |
| Qualquer webhook | `X-Idempotency-Key` header (se fornecido pelo parceiro) |

**Outbound (enviamos ao parceiro):**

| Tipo de operação | Chave de idempotência |
|------------------|----------------------|
| Pedido de exame | `examination_id + partner_id` |
| Prescrição | `prescription_id + partner_id` |
| Envio RNDS | `internal_resource_type + internal_resource_id` |

### 4.3 Implementação

```php
// Verificação antes de processar webhook

public function processLabResult(WebhookRequest $request, string $partnerSlug): JsonResponse
{
    $partner = PartnerIntegration::where('slug', $partnerSlug)->firstOrFail();

    $idempotencyKey = $request->header('X-Idempotency-Key')
        ?? $this->extractFhirId($request->input());

    // Verificar duplicata
    $existing = IntegrationEvent::where([
        'partner_integration_id' => $partner->id,
        'external_id' => $idempotencyKey,
        'event_type' => 'result_received',
        'status' => 'success',
    ])->exists();

    if ($existing) {
        // Retorna 200 (não 409) para que o parceiro não faça retry
        return response()->json([
            'status' => 'already_processed',
            'message' => 'This result has already been recorded',
        ], 200);
    }

    // Processar normalmente...
}
```

### 4.4 Para envios outbound

Antes de enviar um pedido de exame, verificar no `fhir_resource_mappings`:

```php
$alreadySent = FhirResourceMapping::where([
    'internal_resource_type' => 'examination',
    'internal_resource_id' => $examination->id,
    'partner_integration_id' => $partner->id,
])->exists();

if ($alreadySent) {
    Log::info("Exam {$examination->id} already sent to {$partner->slug}, skipping");
    return;
}
```

---

## 5. Timeouts

### 5.1 Timeouts por tipo de operação

| Operação | Timeout de conexão | Timeout de resposta | Total máximo |
|----------|-------------------|--------------------|-|
| Envio de pedido de exame | 5s | 15s | 20s |
| Pull de resultado | 5s | 30s | 35s |
| Verificação de prescrição | 3s | 10s | 13s |
| Envio à RNDS | 10s | 60s | 70s |
| Webhook outbound | 5s | 10s | 15s |

### 5.2 Configuração no HTTP client

```php
// app/Integrations/Adapters/BaseAdapter.php

protected function httpClient(PartnerIntegration $partner): PendingRequest
{
    $timeouts = config("integrations.timeouts.{$partner->type}");

    return Http::baseUrl($partner->base_url)
        ->timeout($timeouts['response'])
        ->connectTimeout($timeouts['connect'])
        ->withToken($this->getAccessToken($partner))
        ->withHeaders([
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json',
        ]);
}
```

---

## 6. Fallbacks e degradação graceful

### 6.1 Comportamento por cenário de falha

| Cenário | Impacto no usuário | Fallback |
|---------|-------------------|----------|
| Lab fora do ar ao enviar pedido | Médico não percebe na hora | Pedido fica na fila; toast: "Pedido registrado, será enviado ao laboratório automaticamente" |
| Lab fora do ar ao puxar resultado | Médico clica "Atualizar" e não vê resultado | Toast: "Laboratório temporariamente indisponível. Quando reconectar, resultados aparecerão automaticamente" |
| RNDS fora do ar | Nenhum impacto no fluxo clínico | Envio fica na fila; será enviado quando RNDS voltar. Não bloqueia consulta |
| Farmácia não valida prescrição | Farmacêutico vê erro | Exibir código de verificação alternativo (QR code + código alfanumérico para verificação manual) |
| Webhook nosso falha ao notificar parceiro | Parceiro não recebe notificação | Retry automático; parceiro pode usar pull como fallback |

### 6.2 Princípio: integração nunca bloqueia fluxo clínico

O médico **sempre** consegue:
- Solicitar exame (mesmo se lab estiver fora)
- Emitir prescrição (mesmo se farmácia estiver fora)
- Registrar diagnóstico (mesmo se RNDS estiver fora)

A integração é **assíncrona por design**. O registro local acontece primeiro; o envio ao parceiro é consequência.

---

## 7. Monitoramento e observabilidade

### 7.1 Métricas para dashboard operacional

| Métrica | Tipo | Alerta se |
|---------|------|-----------|
| `integration.request.duration_ms` | Histogram | p99 > 10s |
| `integration.request.status` | Counter (por status code) | Taxa de 5xx > 10% em 5 min |
| `integration.circuit_breaker.state` | Gauge (por parceiro) | Estado = open por > 15 min |
| `integration.queue.size` | Gauge | > 100 itens pendentes |
| `integration.queue.oldest_item_age` | Gauge | > 1 hora |
| `integration.retry.count` | Counter | > 50 retries/hora para mesmo parceiro |
| `integration.webhook.delivery.success_rate` | Gauge | < 95% em 1 hora |

### 7.2 Integração com LGTM Stack

O projeto já tem documentação para LGTM (Loki, Grafana, Tempo, Mimir). As métricas de integração devem alimentar dashboards dedicados:

```
Dashboard: "Interoperabilidade — Operacional"
├── Painel 1: Status dos parceiros (circuit breaker)
├── Painel 2: Fila de integração (tamanho, idade)
├── Painel 3: Taxa de sucesso por parceiro (últimas 24h)
├── Painel 4: Latência de resposta (p50, p95, p99)
└── Painel 5: Erros recentes (últimas 2h)
```

### 7.3 Logs estruturados

Toda operação de integração deve gerar log estruturado (JSON) para ingestão no Loki:

```json
{
  "level": "info",
  "channel": "integration",
  "partner_id": "uuid",
  "partner_slug": "lab-hermes",
  "operation": "send_exam_order",
  "examination_id": "uuid",
  "status": "success",
  "duration_ms": 340,
  "http_status": 201,
  "circuit_breaker_state": "closed",
  "retry_attempt": 0
}
```

---

## 8. Testes de resiliência

### 8.1 Cenários que devem ter teste automatizado

| Cenário | Tipo de teste | O que validar |
|---------|---------------|---------------|
| Parceiro retorna 500 | Unit | Circuit breaker abre após N falhas |
| Parceiro retorna 200 após falhas | Unit | Circuit breaker fecha e fila é processada |
| Timeout na conexão | Unit | Job vai para retry com backoff correto |
| Webhook duplicado | Feature | Idempotência: não duplica no prontuário |
| Webhook com signature inválida | Feature | Retorna 401, não processa |
| Fila com 100 itens pendentes | Load | Sistema não degrada; itens processados em ordem |
| Parceiro volta após 2h fora | Integration | Todos os itens da fila são processados com sucesso |

### 8.2 Stub para testes

O projeto já tem `MediaGatewayStub.php` como referência. O mesmo padrão deve ser aplicado:

```php
// app/Integrations/Adapters/Lab/LabAdapterStub.php
// Simula laboratório para testes (retorna resultados fake, simula falhas configuráveis)
```

---

## 9. Resumo de decisões

| Decisão | Escolha | Motivo |
|---------|---------|--------|
| Estado do circuit breaker | Redis | Compartilhado entre workers; TTL nativo |
| Fila de retry | RabbitMQ (queue: integrations) | Já configurado no projeto; não compete com jobs internos |
| Persistência da fila | Banco (`integration_queue`) | Visibilidade para admin; sobrevive a restart do broker |
| Backoff | Exponencial com jitter | Evita thundering herd quando parceiro volta |
| Idempotência | `external_id` + `partner_id` em `integration_events` | Simples e eficaz; cobre webhook e pull |
| Fallback geral | Fila assíncrona + notificação | Integração nunca bloqueia fluxo clínico |

---

## 10. Documentos relacionados

- [SchemaIntegracoes.md](SchemaIntegracoes.md) — tabelas `integration_queue` e `integration_events`
- [MVP1.md](MVP1.md) — fluxo de retry no contexto do laboratório
- [SegurancaAPIPublica.md](SegurancaAPIPublica.md) — como falhas de auth interagem com circuit breaker
- [docs/interoperabilidade/Arquitetura.md](../docs/interoperabilidade/Arquitetura.md) — adapter pattern e eventos

---

*Criado em: março/2026.*
