# Feature Spec — Configuração de múltiplos storages para arquivos

> Status: `draft`
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

---

## Perguntas de refinamento (rodada única)

1. Em `config/filesystems.php` já existem `s3`, `s3_private` e `s3_public`; nesta feature vocês querem ativar armazenamento S3/MinIO em produção ou manter `local/public` por enquanto?
2. Em `config/telemedicine.php`, além de `storage.public_images_disk`, `medical_records.disk` e `medical_records.lgpd_exports_disk`, quais novos domínios precisam de disco dedicado (ex.: prescrições, certificados, anexos de chat, documentos de integração)?
3. O fluxo de `MedicalRecordDocumentController` hoje grava e lê via `telemedicine.medical_records.disk`; há necessidade de migração de arquivos já existentes para outro disco/path?
4. `AvatarService` resolve URL via `public_images_disk`; haverá CDN/domínio separado para assets públicos?
5. É necessário adicionar endpoint/rotina de health check de storage (permissão de escrita/leitura) para operação/devops?
6. Para documentos assinados (prescrição/atestado), a separação deve ser por disco distinto ou apenas por diretório dentro do mesmo disco privado?
7. Existe requisito formal de retenção/expurgo por tipo de arquivo (especialmente LGPD export) para orientar lifecycle e custo?

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

---

## Observabilidade

| O que logar                    | Nível      | Contexto incluído                                     |
| ------------------------------ | ---------- | ----------------------------------------------------- |
| Escrita de arquivo por domínio | `info`     | `user_id`, `entity_id`, `domain`, `disk`, `path_hash` |
| Falha de gravação/leitura      | `error`    | `domain`, `disk`, `exception_class`, `request_id`     |
| Disco inválido em config       | `critical` | `config_key`, `disk_name`, `env`                      |

> Não logar nome de paciente, conteúdo de arquivo, diagnóstico ou metadados sensíveis.

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
6. `[Backend]` Adicionar validação de configuração (disco referenciado deve existir).
7. `[Observabilidade]` Padronizar logs operacionais sem PII para operações de storage.
8. `[Testes]` Cobrir contratos de roteamento por domínio, falhas e proteção de disco sensível.
9. `[Operação]` Definir checklist de rollout por ambiente (web, queue workers, symlink público, envs).

---

## Checklist

### Backend

- [ ] Matriz de domínios de arquivo definida e revisada
- [ ] Resolvedor de disco central criado
- [ ] Fluxos existentes migrados para resolvedor
- [ ] Simetria upload/download validada
- [ ] Logs operacionais sem PII implementados

### Infra/Operação

- [ ] Variáveis `.env` por ambiente documentadas
- [ ] Discos privados/públicos validados por ambiente
- [ ] Healthcheck operacional de storage definido
- [ ] Plano de rollback de configuração validado

### Qualidade

- [ ] Testes de contrato de roteamento por domínio
- [ ] Testes de falha para disco inválido/inacessível
- [ ] Testes para evitar exposição de dados sensíveis em disco público
