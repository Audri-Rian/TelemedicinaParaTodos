# Estratégia de Ambientes e Secrets

Este projeto mantém **um único `.env` por ambiente em runtime** (padrão Laravel), mas separa templates por domínio para facilitar manutenção.

## Objetivo

- Evitar múltiplos arquivos `.env` carregados em runtime (mais simples e previsível)
- Melhorar governança de variáveis por domínio
- Reduzir risco de segredos faltando em staging/produção

## Templates por domínio

- `.env.example`: visão geral completa do projeto
- `.env.telemedicine.example`: variáveis do núcleo da plataforma
- `.env.integrations.example`: variáveis de integrações (FHIR, RNDS, circuit breaker, fila)

Os templates são referência para montar o `.env` final do ambiente.

## Convenção recomendada

- **Runtime**: `.env` único
- **Ambientes**: `.env.local`, `.env.staging`, `.env.production` (arquivos de referência locais/equipe)
- **Prefixos por domínio**:
    - `INTEGRATION_*`, `RNDS_*`, `CB_*`, `RETRY_*`
    - `MEDICAL_RECORD_*`, `SIGNATURE_*`
    - `APP_*`, `DB_*`, `QUEUE_*`, `CACHE_*`

## Checklist por ambiente

### Local (desenvolvimento)

- `APP_ENV=local`, `APP_DEBUG=true`
- `QUEUE_CONNECTION=database` (ou `redis` se disponível)
- `MEDICAL_RECORD_EXPORT_QUEUE_CONNECTION=database`
- `SIGNATURE_DRIVER=null`
- `RNDS_ENABLED=false`

### Staging

- `APP_ENV=staging`, `APP_DEBUG=false`
- Worker de filas ativo (`php artisan queue:work`)
- `INTEGRATION_QUEUE_CONNECTION` validada (`rabbitmq` recomendado)
- Certificados e chaves em secret manager
- Teste de exportação assíncrona validado ponta a ponta

### Produção

- `APP_ENV=production`, `APP_DEBUG=false`
- Segredos fora de arquivo versionado (secret manager)
- `SIGNATURE_DRIVER=icp_brasil` somente após contrato/validação
- Monitoramento de filas (jobs pendentes, failed jobs, tempo de processamento)
- Rotação de credenciais e revisão de permissões periódica

## Boas práticas

- Nunca commitar `.env` real nem certificados
- Atualizar `.env.example` e templates sempre que entrar nova variável
- Preferir defaults em `config/*.php` e não espalhar `env()` fora de config
