# Feature Spec — Configuração de múltiplos storages para arquivos

> Status: `ready`
> Autor: Tech Lead Agent · Data: 2026-05-07

---

## Objetivo

Definir e padronizar uma estratégia de múltiplos storages por tipo de arquivo (público, clínico e LGPD), com configuração centralizada e previsível para upload, leitura e download.

## Motivação

O projeto já possui base de configuração de discos em `config/filesystems.php` e chaves de domínio em `config/telemedicine.php`, mas a política de segmentação por tipo de arquivo ainda está parcial, o que aumenta risco de uso inconsistente de disco para dados sensíveis.

---

## Regras de negócio

1. Arquivos de dados médicos e LGPD devem usar discos privados.
2. Arquivos de exibição pública (ex.: avatar) devem usar disco público dedicado.
3. A escolha de disco deve ser feita por chave de domínio em `config/telemedicine.php`, não hardcoded em controller/service.
4. Upload e download devem usar o mesmo resolvedor de disco para garantir simetria.
5. Falha por disco inválido/desconhecido deve ser explícita (sem fallback silencioso em runtime).
6. Todas as operações com documentos clínicos devem manter rastreabilidade de acesso (LGPD/auditoria já existente).
7. Documentos assinados (prescrição/atestado/certificado) devem ser separados por diretório no mesmo disco privado do domínio médico.
8. Cada domínio de arquivo deve definir `retention_days` (nullable), mesmo quando sem expurgo automático no MVP.

---

## Refinamentos confirmados

1. MinIO será configurado futuramente via Docker no desenvolvimento; a spec deve deixar URLs fictícias de referência até a infraestrutura subir.
2. A segmentação deve cobrir quase todos os domínios: prescrições, certificados, anexos de chat, documentos de integração e gravações de videochamada (já preparar agora).
3. Não haverá migração de arquivos legados; arquivos existentes podem ser excluídos.
4. Haverá CDN para assets públicos, porém ainda indisponível.
5. Health check de storage deve ser adicionado ao sistema de monitoramento.
6. Documentos assinados ficam separados por diretório, não por novo disco.
7. Política inicial de retenção:
    - `lgpd_exports`: expurgo automático em 7 dias.
    - `video_recordings`: sem expurgo automático no MVP (preparar configuração futura por tipo de consulta/contrato).
    - `medical_documents`, `prescriptions`, `certificates`: sem expurgo automático no MVP.
    - `public_images`: remoção apenas em troca/remoção manual da imagem.
    - `chat_attachments`: sem expurgo automático inicial, mantendo separação em domínio privado.

---

## Arquitetura proposta

```text
[Upload/Generate] → [Controller/Job] → [Service de domínio] → [StorageResolver por config]
                                                      ↓
                                              [Storage::disk(resolved)]
```

Padrões reutilizados:

- `AvatarService` — já consome `config('telemedicine.storage.public_images_disk')`.
- `MedicalRecordDocumentController` — já consome `config('telemedicine.medical_records.disk')` em upload/download.
- `config/filesystems.php` — já possui base multi-disco (`local`, `public`, `s3`, `s3_private`, `s3_public`).

---

## Backend

### Endpoints

- Sem criação obrigatória de novos endpoints para o MVP da feature.
- Ajustes previstos: apenas padronização de resolução de disco nos fluxos de upload/download existentes.

### Service

- Introduzir um resolvedor central de discos por domínio de arquivo (camada de configuração), reutilizado por services/controllers/jobs.
- Manter services de domínio como fonte da regra de roteamento de arquivos.
- Introduzir catálogo único por domínio com `disk`, `base_path`, `visibility`, `retention_days` e `healthcheck_enabled`.

### Jobs / Filas

- Sem novo job obrigatório para esta fase.
- Jobs existentes de geração de PDF/certificados devem consumir o mesmo resolvedor de disco para consistência.

### Validações (FormRequest/Config)

- Validar que cada chave de disco configurada em `telemedicine.php` referencia um disco existente em `filesystems.php`.
- Validar segmentação de visibilidade:
    - público: apenas assets não sensíveis
    - privado: documentos médicos/LGPD

### Autorização

- Middleware: mantém `auth` conforme rotas atuais.
- Policy: reaproveitar políticas existentes de acesso a prontuário/documentos; sem mudança de regra de autorização nesta feature.

---

## Infraestrutura

- **Storage:** ampliar matriz de mapeamento por domínio em `config/telemedicine.php`, apontando para discos de `config/filesystems.php`.
- **Fila:** RabbitMQ sem alteração estrutural; apenas garantir coerência de disco nos jobs que escrevem arquivo.
- **Cache:** não obrigatório para esta feature.
- **Ambiente:** parametrização via `.env` por ambiente (dev/staging/prod), evitando hardcode de disco.

### URLs fictícias de referência (DEV)

- `MINIO_ENDPOINT=http://minio.localhost:9000`
- `MINIO_PUBLIC_ENDPOINT=http://minio-public.localhost:9000`
- `MINIO_CONSOLE_URL=http://minio-console.localhost:9001`
- `CDN_PUBLIC_URL=https://cdn-dev.telemedicina.local`
- `AWS_URL=https://s3-public-dev.telemedicina.local`

### Matriz inicial de domínios de arquivo (MVP)

| Domínio                 | Disco recomendado                      | Path base                | Visibilidade | Retenção inicial                                     |
| ----------------------- | -------------------------------------- | ------------------------ | ------------ | ---------------------------------------------------- |
| `public_images`         | `s3_public` (ou `public` em dev local) | `public/images`          | Pública      | manual (sem dias)                                    |
| `medical_documents`     | `s3_private` (ou `local`)              | `medical/documents`      | Privada      | sem expurgo automático                               |
| `lgpd_exports`          | `s3_private` (ou `local`)              | `lgpd/exports`           | Privada      | 7 dias                                               |
| `prescriptions`         | `s3_private` (ou `local`)              | `medical/prescriptions`  | Privada      | sem expurgo automático                               |
| `certificates`          | `s3_private` (ou `local`)              | `medical/certificates`   | Privada      | sem expurgo automático                               |
| `chat_attachments`      | `s3_private` (ou `local`)              | `chat/attachments`       | Privada      | sem expurgo automático                               |
| `integration_documents` | `s3_private` (ou `local`)              | `integrations/documents` | Privada      | sem expurgo automático                               |
| `video_recordings`      | `s3_private` (ou `local`)              | `calls/recordings`       | Privada      | sem expurgo automático (preparado para regra futura) |

---

## Observabilidade

| O que logar                    | Nível      | Contexto incluído                                          |
| ------------------------------ | ---------- | ---------------------------------------------------------- |
| Escrita de arquivo por domínio | `info`     | `user_id`, `entity_id`, `domain`, `disk`, `path_hash`      |
| Falha de gravação/leitura      | `error`    | `domain`, `disk`, `exception_class`, `request_id`          |
| Disco inválido em config       | `critical` | `config_key`, `disk_name`, `env`                           |
| Falha no healthcheck storage   | `critical` | `domain`, `disk`, `operation`, `duration_ms`, `request_id` |
| Healthcheck storage OK         | `info`     | `domain`, `disk`, `duration_ms`                            |

> Não logar nome de paciente, conteúdo de arquivo, diagnóstico ou metadados sensíveis.

### Health check de storage

- Deve existir rotina de check por domínio com:
    - resolução de disco configurado,
    - escrita e remoção de arquivo temporário de teste,
    - leitura de metadados do objeto recém gravado.
- Resultado por domínio em `UP` / `DEGRADED` / `DOWN`.
- Integração com o sistema de monitoramento já utilizado no projeto.

---

## Segurança

- Validação de entrada: manter FormRequests de upload com mime/size e categoria.
- Proteção de dados sensíveis: separar estritamente discos públicos vs privados.
- Risco de exposição indevida: impedir uso de disco público para prontuário/LGPD.
- Auditoria: manter logging de acesso já existente nos fluxos de documentos clínicos.
- Configuração segura: bloquear inicialização/deploy com chave de disco inválida.

---

## Edge Cases

1. Disco configurado inexistente → falha explícita e observável, sem fallback implícito.
2. Upload concluído, persistência de metadado falha → executar compensação (delete do arquivo órfão).
3. Download de documento em disco diferente do esperado pós-migração → estratégia de compatibilidade temporária com busca controlada.
4. Ambiente sem link público (`storage:link`) para avatars → retornar erro operacional claro e healthcheck negativo.
5. Alteração de `.env` sem deploy coordenado → inconsistência entre workers/web, mitigada por checklist de rollout.
6. `retention_days` inválido em domínio com expurgo ativo → bloquear inicialização para evitar limpeza incorreta.

---

## Riscos técnicos

| Risco                                             | Probabilidade | Impacto | Mitigação                                                 |
| ------------------------------------------------- | ------------- | ------- | --------------------------------------------------------- |
| Configuração inconsistente entre ambientes        | Média         | Alto    | matriz de variáveis obrigatórias + validação em bootstrap |
| Uso acidental de disco público para dado sensível | Baixa         | Alto    | whitelist por domínio + testes de contrato                |
| Arquivos órfãos em falhas parciais                | Média         | Médio   | estratégia de compensação e rotina de reconciliação       |
| Divergência entre worker e web sobre disco ativo  | Média         | Médio   | rollout com restart coordenado e verificação pós-deploy   |

---

## Plano de implementação

Ordenado por dependência técnica:

1. `[Infra/Config]` Definir matriz de domínios de arquivo e discos alvo em `config/telemedicine.php`.
2. `[Infra/Config]` Revisar/normalizar discos disponíveis em `config/filesystems.php` por ambiente.
3. `[Backend]` Criar resolvedor central de disco por domínio (contrato único para controller/service/job).
4. `[Backend]` Adaptar fluxos existentes de upload/download para consumir o resolvedor.
5. `[Backend]` Garantir simetria de escrita/leitura em documentos clínicos e avatars.
6. `[Backend]` Adicionar validação de configuração (disco referenciado deve existir e `retention_days` deve ser válido quando aplicável).
7. `[Observabilidade]` Padronizar logs operacionais sem PII para operações de storage.
8. `[Monitoramento]` Implementar healthcheck de storage por domínio e integrar ao monitoramento.
9. `[Backend/Fila]` Preparar job/command de limpeza por `retention_days` (ativando inicialmente apenas `lgpd_exports=7`).
10. `[Testes]` Cobrir contratos de roteamento por domínio, falhas, retenção e proteção de disco sensível.
11. `[Operação]` Definir checklist de rollout por ambiente (web, queue workers, links públicos, envs e endpoints MinIO/CDN).

---

## Checklist

### Backend

- [ ] Matriz de domínios de arquivo definida e revisada
- [ ] Resolvedor de disco central criado
- [ ] Fluxos existentes migrados para resolvedor
- [ ] Simetria upload/download validada
- [ ] `retention_days` por domínio configurado
- [ ] Job/command de limpeza preparado (ativo em `lgpd_exports`)
- [ ] Logs operacionais sem PII implementados

### Infra/Operação

- [ ] Variáveis `.env` por ambiente documentadas
- [ ] Discos privados/públicos validados por ambiente
- [ ] Endpoints fictícios MinIO/CDN definidos para DEV
- [ ] Healthcheck operacional de storage implementado e integrado ao monitoramento
- [ ] Plano de rollback de configuração validado

### Qualidade

- [ ] Testes de contrato de roteamento por domínio
- [ ] Testes de falha para disco inválido/inacessível
- [ ] Testes de retenção para `lgpd_exports` (7 dias)
- [ ] Testes para evitar exposição de dados sensíveis em disco público
