# TASK — Swagger / OpenAPI: documentação da API

## Intenção da feature

Implementar **documentação da API** no projeto Telemedicina para Todos usando **Swagger (OpenAPI)**, permitindo que desenvolvedores e parceiros consultem e testem os endpoints de forma padronizada.

---

## Objetivo

- Expor uma **documentação interativa** da API (Swagger UI) acessível no próprio ambiente (ex.: `/api/documentation`).
- Manter a spec em **OpenAPI 3.x** (YAML ou JSON) versionada e gerada/atualizada a partir do código ou anotações.
- Alinhar com a feature de **interoperabilidade** ([TASK_INTEROPERABILIDADE_FEATURE.md](./TASK_INTEROPERABILIDADE_FEATURE.md)), que prevê “Documentar a API (ex.: OpenAPI/Swagger)”.

---

## Motivação

- Facilitar integração por parceiros e por equipe interna.
- Reduzir erros de consumo da API e tempo de onboarding.
- Atender requisito de documentação mencionado na validação corporativa e na task de interoperabilidade.

---

## Escopo (visão de alto nível)

- Instalar e configurar um pacote Swagger/OpenAPI no Laravel (ex.: **DarkaOnLine/L5-Swagger** ou equivalente compatível com Laravel 12).
- Expor a UI do Swagger em uma rota protegida ou restrita por ambiente (ex.: apenas em local/staging).
- Documentar as rotas de API existentes (e futuras da API pública de interoperabilidade) com resumos, parâmetros, body e respostas.
- Garantir que a geração da spec (e da UI) faça parte do fluxo de desenvolvimento (comandos artisan, opcionalmente CI).

Detalhes de segurança (quem pode acessar a doc em produção), autenticação na UI (Bearer, API Key) e separação entre API interna e API pública ficam para o planejamento da interoperabilidade.

---

## Status

- **Estado**: Implementação **concluída** (L5-Swagger 10.x, PHP 8 Attributes).
- **Próximos passos**: (Opcional) passo no CI para gerar/validar spec; documentar API pública quando existir.

---

## Checklist

### T SW.1 — Pacote e configuração base
- [x] Instalar pacote Swagger/OpenAPI para Laravel (ex.: `darkaonline/l5-swagger`)
- [x] Publicar config e assets (`php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"`)
- [x] Configurar rota da documentação (ex.: `/api/documentation`) e ambiente (disponível em local/staging; produção conforme política)
- [x] Gerar spec inicial (OpenAPI 3.x) e validar acesso à Swagger UI no browser

### T SW.2 — Documentar rotas API existentes
- [x] Listar todas as rotas que exponham JSON (ex.: em `routes/web.php`, `routes/auth.php` ou futura `routes/api.php`)
- [x] Adicionar anotações OpenAPI nos controllers (ou arquivo base de paths) com: summary, parameters, requestBody, responses
- [x] Incluir descrição de autenticação (ex.: Bearer, sanctum) na spec
- [x] Regerar documentação e revisar exemplos na UI

### T SW.3 — Integração com o fluxo de desenvolvimento
- [x] Documentar no README: como gerar/atualizar a doc (comando artisan) e onde acessar a UI
- [ ] (Opcional) Adicionar passo no CI ou em scripts de deploy para validar/gerar a spec

### T SW.4 — Preparação para API pública (interoperabilidade)
- [x] Estruturar na spec tags/grouping para “API interna” vs “API pública” quando a segunda for criada
- [x] Deixar configurado basePath/servers para facilitar inclusão futura de versão (ex.: `/api/v1`)

---

## T SW.1 — Instalar e configurar Swagger/OpenAPI no Laravel

### O que fazer

1. Escolher e instalar um pacote compatível com Laravel 12 (ex.: **DarkaOnLine/L5-Swagger**).
2. Publicar configuração e, se necessário, views/assets.
3. Definir em `config/l5-swagger.php` (ou equivalente):
   - Rota da documentação (ex.: `api/documentation`).
   - Restrição por ambiente (ex.: não publicar em produção ou só com flag).
4. Gerar a spec OpenAPI inicial (mesmo mínima) e abrir a Swagger UI no navegador para validar.

### Por que existe

Sem ferramenta integrada, a documentação fica desatualizada e dispersa. Um pacote padrão mantém a spec próxima do código e oferece UI pronta.

### Saída

- Swagger UI acessível na rota definida.
- Arquivo de spec OpenAPI (ex.: `storage/api-docs/api-docs.json`) gerado pelo comando do pacote.

### Status

> **Status:** Pendente.

---

## T SW.2 — Documentar rotas API existentes

### O que fazer

1. Mapear todas as rotas que retornam JSON e devem ser documentadas (incluindo as usadas pelo front Inertia, se fizerem parte da “API” documentada).
2. Para cada controller/rota relevante:
   - Adicionar anotações OpenAPI (ou equivalente em YAML/JSON centralizado) com:
     - `summary` e `description`;
     - `parameters` (path, query, header);
     - `requestBody` quando aplicável;
     - `responses` (200, 401, 422, etc.).
3. Documentar o esquema de autenticação na spec (ex.: Bearer token, Sanctum).
4. Executar o comando de geração da doc e revisar a Swagger UI (exemplos, try-it-out).

### Por que existe

A documentação só é útil se refletir os endpoints reais e os contratos (parâmetros e respostas).

### Saída

- Spec OpenAPI com paths preenchidos para as rotas escolhidas.
- Swagger UI utilizável para testes manuais.

### Status

> **Status:** Pendente.

---

## T SW.3 — Integração com o fluxo de desenvolvimento

### O que fazer

1. No README (ou em `docs/`):
   - Explicar como gerar/atualizar a documentação (ex.: `php artisan l5-swagger:generate`).
   - Indicar a URL da Swagger UI por ambiente (local/staging).
2. (Opcional) Incluir no pipeline ou em scripts:
   - Passo que gera a spec (e falha se o comando falhar).
   - Ou validação da spec (ex.: com ferramenta OpenAPI validator).

### Por que existe

Garantir que a doc seja parte do processo de desenvolvimento e não seja esquecida em mudanças de API.

### Saída

- README (ou doc) atualizado.
- Opcional: passo de CI/script para gerar ou validar a spec.

### Status

> **Status:** Pendente.

---

## T SW.4 — Preparação para API pública (interoperabilidade)

### O que fazer

1. Na spec e na configuração do Swagger:
   - Usar **tags** (ou agrupamento) para separar conceitualmente “API interna” e “API pública” (a pública pode ser criada depois).
2. Configurar **servers** e **basePath** (ou path prefix) de forma a facilitar futura versão (ex.: `/api/v1`), alinhado à [TASK_INTEROPERABILIDADE_FEATURE.md](./TASK_INTEROPERABILIDADE_FEATURE.md).

### Por que existe

Quando a API pública for implementada, a documentação já estará estruturada para receber os novos endpoints sem retrabalho.

### Saída

- Spec com tags/servers preparados para expansão.
- Base para documentar a API pública quando existir.

### Status

> **Status:** Pendente.

---

## Resumo

Esta feature adiciona **Swagger/OpenAPI** ao projeto para:

1. Oferecer documentação interativa da API.
2. Manter a spec próxima do código e do fluxo de desenvolvimento.
3. Preparar o terreno para a documentação da API pública de interoperabilidade.

---

## Priorização sugerida

| Subtask   | Prioridade | Dependências        |
|-----------|------------|---------------------|
| T SW.1    | Alta       | Nenhuma             |
| T SW.2    | Alta       | T SW.1              |
| T SW.3    | Média      | T SW.1              |
| T SW.4    | Média      | T SW.1 (opcionalmente T SW.2) |

---

## Referências

- [TASK_INTEROPERABILIDADE_FEATURE.md](./TASK_INTEROPERABILIDADE_FEATURE.md) — Documentar a API (OpenAPI/Swagger)
- [OpenAPI Specification](https://spec.openapis.org/oas/latest.html)
- Pacote sugerido: [DarkaOnLine/L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) (verificar compatibilidade com Laravel 12)

---

*Documento criado para a feature Swagger. Última atualização: março/2025.*
