# Feature Spec — Fix Storage S3/MinIO

> Status: `draft`
> Autor: Tech Lead Agent · Data: 2026-05-07

---

## Contexto / Problema

- Há uso hardcoded de `Storage::disk('local')` em fluxos de prontuário, documentos médicos, geração de PDF e exportação LGPD.
- Em ambientes com `FILESYSTEM_DISK=s3` (AWS S3 ou MinIO), os arquivos continuam sendo gravados/lidos no disco local do servidor.
- Isso quebra a expectativa de infraestrutura já disponível em `config/filesystems.php` e `.env` (suporte a `s3`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`).

## Objetivo

Padronizar acessos de arquivos médicos sensíveis para usar o disco padrão configurável por ambiente via `Storage::disk()` (sem argumento), garantindo compatibilidade com local, MinIO e S3.

## Não-objetivos

- Não alterar comportamento de `AvatarService` (permanece `disk('public')` por requisito funcional de arquivos públicos).
- Não introduzir mudanças arquiteturais, migrations, novos serviços ou refactors amplos.
- Não modificar política de permissões, rotas ou contrato de API/UX.

---

## Regras de negócio

1. Toda operação de `put/get/exists/download` em documentos médicos/LGPD deste escopo deve respeitar `FILESYSTEM_DISK`.
2. A seleção de disco não deve depender de fallback manual `local/public` para documentos médicos privados.
3. O nome do arquivo no download deve ser preservado como hoje.
4. O comportamento de erro para arquivo ausente deve permanecer equivalente ao atual.

---

## Contexto encontrado

- `config/filesystems.php` já define `default` como `env('FILESYSTEM_DISK', 'local')` e suporta S3/MinIO com `use_path_style_endpoint`.
- Rotas relevantes seguem padrão por domínio (`routes/web/doctor.php` e `routes/web/lgpd.php`), sem necessidade de alteração para esta feature.
- `DoctorDocumentsController` possui fallback hardcoded `local/public` no download e é o único ponto com lógica condicional de disco dentro do escopo informado.

---

## Escopo detalhado por arquivo

### Arquivos em escopo (alteração obrigatória)

| Arquivo                                                                | Pontos                 | Ação                                                                      |
| ---------------------------------------------------------------------- | ---------------------- | ------------------------------------------------------------------------- |
| `app/Http/Controllers/MedicalRecordDocumentController.php`             | linhas 121, 132        | Trocar `disk('local')` por `disk()` em `exists` e `download`              |
| `app/Http/Controllers/MedicalRecordDocumentController.php`             | upload                 | Remover hardcode de disco no `store(..., 'local')` para usar disco padrão |
| `app/Services/MedicalRecordService.php`                                | linhas 977, 1017, 1065 | Trocar inicialização de disco local por disco padrão                      |
| `app/Jobs/GenerateMedicalRecordPDF.php`                                | linha 43               | Trocar `disk('local')` por `disk()`                                       |
| `app/Http/Controllers/Doctor/DoctorDocumentsController.php`            | linhas 141, 143        | Remover fallback `local/public` e usar `Storage::disk()->download(...)`   |
| `app/Http/Controllers/Doctor/DoctorPatientMedicalRecordController.php` | linhas 97, 189         | Trocar `disk('local')` por `disk()` em downloads                          |
| `app/Services/LGPDService.php`                                         | linha 165              | Trocar `disk('local')->put(...)` por `disk()->put(...)`                   |
| `app/Http/Controllers/LGPD/DataPortabilityController.php`              | leitura de exportação  | Trocar `disk('local')->get(...)` por `disk()->get(...)`                   |

### Fora de escopo (não alterar)

| Arquivo                          | Motivo                                                             |
| -------------------------------- | ------------------------------------------------------------------ |
| `app/Services/AvatarService.php` | Arquivos públicos; uso de `disk('public')` é comportamento correto |

## Estratégia de implementação

1. Aplicar substituições cirúrgicas somente nos pontos listados em escopo.
2. Em `DoctorDocumentsController`, eliminar seleção condicional de disco e delegar totalmente ao disco padrão.
3. Garantir que `use Illuminate\Support\Facades\Storage;` permaneça consistente nos arquivos alterados.
4. Validar comportamento em dois perfis de ambiente:
    - `FILESYSTEM_DISK=local`
    - `FILESYSTEM_DISK=s3` com MinIO (`AWS_ENDPOINT` e `AWS_USE_PATH_STYLE_ENDPOINT=true`)
5. Executar testes de regressão focados em upload/download/export de documentos.

Fluxo esperado:

```
Controller/Service/Job → Storage::disk() → driver default (local|s3) → local FS ou MinIO/S3
```

---

## Critérios de aceite

1. Com `FILESYSTEM_DISK=local`, fluxos atuais continuam funcionais sem regressão.
2. Com `FILESYSTEM_DISK=s3` + MinIO, arquivos médicos e export LGPD deste escopo são gravados/lidos no bucket, não em `storage/app/private`.
3. Downloads de documentos médicos funcionam sem fallback manual `local/public`.
4. `AvatarService` permanece inalterado.
5. Não há alteração de contratos HTTP, payloads ou rotas.
6. Diff final é pequeno e limitado ao escopo (substituições de disk + remoção de fallback específico).

---

## Plano de testes (local + MinIO)

### Cenário A — Local

- Configurar `FILESYSTEM_DISK=local`.
- Testar upload de documento médico (fluxo do prontuário).
- Testar download em:
    - `MedicalRecordDocumentController`
    - `DoctorPatientMedicalRecordController`
    - `DoctorDocumentsController`
- Testar geração de PDF de prontuário (`GenerateMedicalRecordPDF`).
- Testar exportação LGPD (`LGPDService`).
- Validar persistência em `storage/app/private` (comportamento esperado para local).

### Cenário B — MinIO (S3 compatível)

- Configurar:
    - `FILESYSTEM_DISK=s3`
    - `AWS_ACCESS_KEY_ID=minioadmin`
    - `AWS_SECRET_ACCESS_KEY=minioadmin`
    - `AWS_DEFAULT_REGION=us-east-1`
    - `AWS_BUCKET=telemedicina`
    - `AWS_ENDPOINT=http://localhost:9000`
    - `AWS_USE_PATH_STYLE_ENDPOINT=true`
- Reexecutar os mesmos fluxos do cenário local.
- Validar objetos no bucket `telemedicina`.
- Confirmar ausência de novos arquivos gerados em `storage/app/private` para os fluxos testados.

### Regressão complementar

- Validar avatar upload/download para garantir que `disk('public')` foi preservado.
- Validar respostas de erro em arquivo inexistente (mesma semântica prévia).

---

## Riscos e rollback

| Risco                                                  | Probabilidade | Impacto | Mitigação                                                                     |
| ------------------------------------------------------ | ------------- | ------- | ----------------------------------------------------------------------------- |
| Configuração S3/MinIO incompleta em ambiente           | Média         | Alto    | Checklist de variáveis e smoke test pós-deploy                                |
| Caminhos antigos já gravados em disco local            | Média         | Médio   | Plano de contingência: manter `FILESYSTEM_DISK=local` até migração de objetos |
| Divergência em comportamento de exceções entre drivers | Baixa         | Médio   | Teste de arquivo inexistente em local e MinIO                                 |

### Rollback

1. Reverter commit da feature.
2. Definir temporariamente `FILESYSTEM_DISK=local` no ambiente impactado.
3. Reexecutar smoke tests de upload/download críticos.

---

## Checklist de execução

- [ ] Atualizar 7 arquivos do escopo com substituições `disk('local')` -> `disk()`
- [ ] Remover fallback manual `local/public` em `DoctorDocumentsController`
- [ ] Confirmar `AvatarService` inalterado
- [ ] Rodar testes funcionais dos fluxos médicos e LGPD em `local`
- [ ] Rodar testes funcionais dos fluxos médicos e LGPD em `s3` (MinIO)
- [ ] Validar smoke test em ambiente equivalente a produção (`FILESYSTEM_DISK=s3`)
- [ ] Preparar nota de deploy com variáveis obrigatórias de storage

---

## Suposições explícitas

1. O bucket S3/MinIO já está provisionado e acessível pelo app.
2. Não há requisito de migração retroativa de arquivos já existentes no disco local.
3. O objetivo é corrigir apenas os pontos mapeados no escopo informado (mudança cirúrgica).
4. O provider S3 em produção seguirá contrato compatível com adapter Laravel Filesystem.

---

## Decisões do solicitante

1. Incluir nesta mesma entrega o ajuste em `app/Http/Controllers/LGPD/DataPortabilityController.php`.
2. Não haverá migração de arquivos legados de `storage/app/private` para S3/MinIO.
3. Rollout em produção com corte total para `FILESYSTEM_DISK=s3`, sem fase híbrida.
