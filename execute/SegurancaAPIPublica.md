# Segurança da API Pública — Autenticação, Scopes, Rate Limiting e Auditoria

Este documento especifica a segurança da API pública exposta a parceiros externos (laboratórios, farmácias, hospitais).

---

## 1. Arquitetura de segurança — Visão geral

```
Parceiro externo
      │
      │ (1) HTTPS + Bearer Token
      ▼
┌─────────────────────────────────┐
│         API Gateway             │
│  ┌──────────────────────────┐   │
│  │ Rate Limiter (por token) │   │
│  ├──────────────────────────┤   │
│  │ Auth Middleware           │   │
│  │  ├─ Validar token        │   │
│  │  ├─ Verificar scopes     │   │
│  │  └─ Verificar IP (opt.)  │   │
│  ├──────────────────────────┤   │
│  │ Audit Logger             │   │
│  │  └─ Registrar tudo       │   │
│  ├──────────────────────────┤   │
│  │ Consent Checker          │   │
│  │  └─ Paciente consentiu?  │   │
│  └──────────────────────────┘   │
│              │                  │
│              ▼                  │
│      Controller / Adapter       │
└─────────────────────────────────┘
```

---

## 2. Autenticação

### 2.1 Modelo: OAuth2 Client Credentials

Parceiros se autenticam via **Client Credentials** (máquina-a-máquina, sem interação do usuário):

```
POST /api/v1/public/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_id={partner_client_id}
&client_secret={partner_client_secret}
&scope=lab:read lab:write
```

**Resposta:**

```json
{
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "lab:read lab:write"
}
```

### 2.2 Onde ficam as credenciais

- `client_id` e `client_secret` são armazenados em `integration_credentials` (ver [SchemaIntegracoes.md](SchemaIntegracoes.md))
- `client_secret` é criptografado via Laravel Encrypted Cast
- Tokens gerados são armazenados com TTL (padrão: 1 hora)

### 2.3 Rotação de credenciais

| Ação | Quando | Como |
|------|--------|------|
| Rotação de `client_secret` | A cada 90 dias ou sob demanda | Admin gera novo secret no hub de integrações; antigo válido por 24h de sobreposição |
| Revogação imediata | Suspeita de comprometimento | Admin desativa integração; todos os tokens existentes são invalidados |

### 2.4 Autenticação de webhooks (inbound)

Para webhooks que **nós recebemos** de parceiros, a autenticação usa **HMAC-SHA256**:

```
X-Webhook-Signature: sha256={HMAC(payload, webhook_secret)}
X-Webhook-Timestamp: 1711612800
```

**Validação no middleware:**

1. Verificar que `X-Webhook-Timestamp` está dentro de 5 minutos (anti-replay)
2. Computar `HMAC-SHA256(timestamp + "." + body, webhook_secret)`
3. Comparar com `X-Webhook-Signature` (timing-safe comparison)

---

## 3. Scopes (permissões granulares)

Cada parceiro recebe **apenas os scopes necessários** para sua função. Scopes são verificados em cada request.

### 3.1 Definição de scopes

| Scope | Permissão | Parceiros típicos |
|-------|-----------|-------------------|
| `lab:read` | Ler pedidos de exame destinados a este lab | Laboratório |
| `lab:write` | Enviar resultados de exame | Laboratório |
| `pharmacy:read` | Ler/verificar prescrições | Farmácia |
| `pharmacy:write` | Registrar dispensação | Farmácia |
| `patient:read` | Ler dados do paciente (com consentimento) | Hospital |
| `patient:read:summary` | Ler apenas resumo (sem dados sensíveis) | Convênio |
| `exam:read` | Ler resultados de exames do paciente | Hospital |
| `prescription:read` | Ler prescrições do paciente | Hospital, Farmácia |
| `appointment:read` | Ler dados de consultas | Hospital, Convênio |
| `webhook:register` | Registrar/atualizar webhooks | Qualquer parceiro |

### 3.2 Atribuição de scopes por tipo de parceiro

| Tipo de parceiro | Scopes padrão |
|------------------|---------------|
| **Laboratório** | `lab:read`, `lab:write`, `webhook:register` |
| **Farmácia** | `pharmacy:read`, `pharmacy:write`, `webhook:register` |
| **Hospital** | `patient:read`, `exam:read`, `prescription:read`, `appointment:read` |
| **Convênio** | `patient:read:summary`, `appointment:read` |

### 3.3 Implementação (middleware Laravel)

```php
// Middleware: CheckPartnerScope
public function handle(Request $request, Closure $next, string ...$requiredScopes)
{
    $partner = $request->attributes->get('authenticated_partner');
    $tokenScopes = $partner->currentToken()->scopes;

    foreach ($requiredScopes as $scope) {
        if (!in_array($scope, $tokenScopes)) {
            return response()->json([
                'error' => 'insufficient_scope',
                'required' => $scope,
            ], 403);
        }
    }

    return $next($request);
}

// Uso em rotas:
Route::get('/lab/{slug}/orders', [LabOrderController::class, 'index'])
     ->middleware('auth:partner', 'scope:lab:read');
```

---

## 4. Rate Limiting

### 4.1 Limites por tipo de parceiro

| Tipo | Limite por minuto | Limite por hora | Limite diário |
|------|-------------------|-----------------|---------------|
| **Laboratório** | 60 req/min | 1.000 req/h | 10.000 req/dia |
| **Farmácia** | 30 req/min | 500 req/h | 5.000 req/dia |
| **Hospital** | 120 req/min | 2.000 req/h | 20.000 req/dia |
| **Convênio** | 60 req/min | 1.000 req/h | 10.000 req/dia |

### 4.2 Headers de resposta

Toda resposta inclui headers de rate limit:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1711613400
Retry-After: 30              (só quando limite excedido)
```

### 4.3 Resposta quando excedido

```
HTTP 429 Too Many Requests

{
  "error": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Try again in 30 seconds.",
  "retry_after": 30
}
```

### 4.4 Implementação (Laravel)

```php
// Usando Laravel Rate Limiting nativo
RateLimiter::for('partner-api', function (Request $request) {
    $partner = $request->attributes->get('authenticated_partner');
    $limits = config("integrations.rate_limits.{$partner->type}");

    return [
        Limit::perMinute($limits['per_minute'])->by($partner->id),
        Limit::perHour($limits['per_hour'])->by($partner->id),
        Limit::perDay($limits['per_day'])->by($partner->id),
    ];
});
```

---

## 5. Auditoria de acesso externo

### 5.1 O que registrar

**Toda** chamada de parceiro é registrada em `integration_events` + tabela de auditoria dedicada:

| Campo | Descrição |
|-------|-----------|
| `partner_integration_id` | Qual parceiro |
| `endpoint` | Rota acessada |
| `method` | GET, POST, etc. |
| `scopes_used` | Quais scopes foram necessários |
| `patient_id` | Se acessou dados de paciente específico |
| `ip_address` | IP de origem |
| `response_status` | 200, 403, 429, etc. |
| `response_time_ms` | Latência |
| `request_body_hash` | Hash do body (não o body inteiro, por privacidade) |
| `created_at` | Timestamp |

### 5.2 Auditoria vs. logs de integração

| Aspecto | `integration_events` | Auditoria de API |
|---------|---------------------|------------------|
| **Propósito** | Rastrear fluxos de dados (pedido → resultado) | Rastrear acesso à API |
| **Granularidade** | Por operação de negócio | Por request HTTP |
| **Quem usa** | Admin, suporte | Segurança, compliance, LGPD |
| **Retenção** | 2 anos | 5 anos (LGPD) |

### 5.3 Alertas automáticos

| Evento | Alerta |
|--------|--------|
| 5+ tentativas com token inválido em 5 min | Possível ataque de força bruta |
| Parceiro acessando paciente sem consentimento | Violação LGPD |
| Pico anormal de requests (3x acima da média) | Possível abuso ou erro de integração |
| Parceiro acessando fora do escopo contratual | Violação de contrato |

---

## 6. Camada de consentimento (Consent Enforcement)

Antes de retornar dados de paciente a qualquer parceiro externo, o sistema verifica:

```
Request do parceiro (ex: GET /patients/{id}/exams)
         │
         ▼
   Auth OK? (token + scope)
         │
         ▼
   Paciente tem consentimento ativo para este parceiro?
         │
         ├── Sim → retorna dados
         │
         └── Não → 403 Forbidden
              {
                "error": "consent_required",
                "message": "Patient has not consented to share data with this partner"
              }
```

### 6.1 Tipos de consentimento verificados

| Tipo | Quando verificar |
|------|-----------------|
| `data_sharing_lab` | Lab acessa dados do paciente |
| `data_sharing_pharmacy` | Farmácia verifica prescrição |
| `data_sharing_hospital` | Hospital consulta histórico |
| `data_sharing_insurance` | Convênio verifica cobertura |

### 6.2 Granularidade do consentimento

O consentimento pode ser:
- **Por parceiro:** "Autorizo o Laboratório Hermes a acessar meus exames"
- **Por tipo de dado:** "Autorizo acesso a exames, mas não a prescrições"
- **Por período:** "Autorizo por 12 meses"
- **Revogável:** paciente pode revogar a qualquer momento na interface

---

## 7. Versionamento da API

### 7.1 Estratégia: URL path versioning

```
/api/v1/public/...    ← versão atual
/api/v2/public/...    ← quando houver breaking changes
```

### 7.2 Política de deprecação

| Fase | Duração | Ação |
|------|---------|------|
| **Anúncio** | 3 meses antes | Header `Sunset: {date}` + email para parceiros |
| **Deprecação** | 3 meses | API antiga funciona mas retorna header `Deprecated: true` |
| **Desligamento** | Após 6 meses total | API antiga retorna 410 Gone |

### 7.3 Header de deprecação

```
Sunset: Sat, 28 Sep 2026 00:00:00 GMT
Deprecation: true
Link: </api/v2/public/docs>; rel="successor-version"
```

---

## 8. IP Allowlisting (opcional)

Para parceiros que desejem segurança adicional:

```php
// Em partner_integrations.settings
{
  "allowed_ips": ["203.0.113.0/24", "198.51.100.10"],
  "ip_restriction_enabled": true
}
```

Middleware verifica IP antes de processar a request. Se não configurado, qualquer IP com token válido é aceito.

---

## 9. Checklist de segurança

- [ ] Implementar OAuth2 Client Credentials (Laravel Passport ou Sanctum com abilities)
- [ ] Implementar middleware de scopes
- [ ] Configurar rate limiting por parceiro
- [ ] Implementar validação HMAC para webhooks
- [ ] Implementar camada de consent enforcement
- [ ] Configurar auditoria de toda chamada externa
- [ ] Implementar alertas automáticos para eventos suspeitos
- [ ] Definir e documentar política de rotação de credenciais
- [ ] Configurar CORS restritivo para API pública
- [ ] Habilitar HTTPS obrigatório (HSTS)
- [ ] Implementar header `Sunset` para versionamento futuro
- [ ] Validar payloads FHIR contra schemas antes de processar

---

## 10. Documentos relacionados

- [SchemaIntegracoes.md](SchemaIntegracoes.md) — tabela `integration_credentials` e modelo de dados
- [PadroesRegulatorios.md](PadroesRegulatorios.md) — requisitos LGPD para consentimento e auditoria
- [ResilienciaOperacional.md](ResilienciaOperacional.md) — como falhas de segurança interagem com circuit breaker
- [MVP1.md](MVP1.md) — endpoints específicos do MVP 1

---

*Criado em: março/2026.*
