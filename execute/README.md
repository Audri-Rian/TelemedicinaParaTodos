# Execute — Plano de Execução da Interoperabilidade

Esta pasta contém os documentos técnicos e regulatórios necessários para **iniciar a implementação** da feature de interoperabilidade. Diferente da pasta `docs/interoperabilidade/` (que define visão, produto e estratégia), esta pasta foca no **"como fazer"**.

---

## Documentos

| Documento | Conteúdo |
|-----------|----------|
| [PadroesRegulatorios.md](PadroesRegulatorios.md) | Mapa de obrigações legais brasileiras por MVP (RNDS, TISS, ICP-Brasil, LGPD, CFM). |
| [SchemaIntegracoes.md](SchemaIntegracoes.md) | Migrations faltantes para interoperabilidade + mapeamento de dados entre modelo interno e FHIR. |
| [MVP1.md](MVP1.md) | Especificação do MVP 1 (Laboratório): protocolo FHIR, modelo híbrido webhook + sync, fluxos detalhados. |
| [SegurancaAPIPublica.md](SegurancaAPIPublica.md) | Autenticação, scopes, rate limiting, auditoria e versionamento da API pública. |
| [ResilienciaOperacional.md](ResilienciaOperacional.md) | Circuit breaker, retry com backoff, idempotência, timeouts e fallbacks. |

---

## Relação com a documentação existente

```
docs/interoperabilidade/    ← O QUE e POR QUE (visão, produto, UX, personas)
         │
         ▼
execute/                    ← COMO (regulatório, schema, segurança, resiliência, MVP1)
```

Os documentos aqui referenciam e complementam a documentação estratégica em `docs/interoperabilidade/`.

---

## Ordem de leitura sugerida

1. **PadroesRegulatorios.md** — entender as obrigações legais antes de qualquer código
2. **SchemaIntegracoes.md** — preparar o banco de dados
3. **MVP1.md** — especificação do primeiro entregável
4. **SegurancaAPIPublica.md** — como proteger a API pública
5. **ResilienciaOperacional.md** — como operar em produção

---

*Criado em: março/2026.*
