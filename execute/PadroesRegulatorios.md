# Padrões Regulatórios — Mapa de Obrigações Legais por MVP

Este documento mapeia as **obrigações regulatórias brasileiras** que impactam diretamente a implementação da interoperabilidade, organizadas por MVP.

---

## 1. Visão geral dos padrões

| Padrão | Órgão | Obrigatoriedade | Impacto no projeto |
|--------|-------|-----------------|-------------------|
| **RNDS** (Rede Nacional de Dados em Saúde) | Ministério da Saúde | Obrigatório para estabelecimentos de saúde | Envio de registros clínicos ao barramento federal |
| **TISS** (Troca de Informação em Saúde Suplementar) | ANS | Obrigatório para operadoras e prestadores conveniados | Comunicação com convênios/planos de saúde |
| **ICP-Brasil** (Infraestrutura de Chaves Públicas) | ITI | Obrigatório para documentos com validade jurídica | Assinatura digital de prescrições e atestados |
| **LGPD** (Lei Geral de Proteção de Dados) | ANPD | Obrigatório | Consentimento, anonimização, portabilidade |
| **CFM** (Conselho Federal de Medicina) | CFM | Obrigatório para exercício da medicina | Resoluções sobre telemedicina, prontuário eletrônico |
| **FHIR R4** | HL7 International | Recomendado (obrigatório via RNDS) | Formato padrão para troca de dados clínicos |

---

## 2. Obrigações por MVP

### 2.1 MVP 1 — Integração com Laboratório

| Obrigação | Padrão | O que fazer | Prioridade |
|-----------|--------|-------------|-----------|
| Enviar Registro de Atendimento Clínico (RAC) à RNDS | RNDS / FHIR | Após consulta com pedido de exame, enviar Bundle FHIR com Encounter + ServiceRequest ao barramento RNDS | **Alta** |
| Receber resultados em formato FHIR | FHIR R4 | DiagnosticReport + Observation como formato de recebimento de resultados laboratoriais | **Alta** |
| Consentimento do paciente para compartilhamento | LGPD | Antes de enviar dados ao laboratório, registrar consentimento explícito (tabela `consents` já existe) | **Alta** |
| Autenticação no barramento RNDS | RNDS | Certificado digital do estabelecimento (e-CNPJ) + token OAuth2 do RNDS | **Alta** |
| Identificação do paciente via CNS | RNDS | Cartão Nacional de Saúde obrigatório para interação com RNDS — campo `cns` necessário na tabela `patients` | **Alta** |
| Rastreabilidade de dados recebidos | LGPD / CFM | Registrar origem, data e responsável por cada dado externo incorporado ao prontuário | **Média** |

**Resolução CFM aplicável:** Resolução CFM 1.821/2007 (prontuário eletrônico) — exige que dados integrados ao prontuário tenham identificação de origem e sejam armazenados por no mínimo 20 anos.

---

### 2.2 MVP 2 — Integração com Farmácia

| Obrigação | Padrão | O que fazer | Prioridade |
|-----------|--------|-------------|-----------|
| Assinatura digital da prescrição | ICP-Brasil | Prescrição digital só tem validade jurídica com assinatura via certificado ICP-Brasil (e-CPF do médico). Resolução CFM 2.299/2021 | **Crítica** |
| Validação da prescrição pela farmácia | ICP-Brasil + CFM | Farmácia valida assinatura digital + CRM + data de validade. API deve expor endpoint de verificação | **Alta** |
| Registro de dispensação | SNGPC (Anvisa) | Para medicamentos controlados, a dispensação deve ser reportada ao SNGPC. Se a farmácia registrar dispensação via API, garantir conformidade | **Média** |
| RNDS — Registro de prescrição | RNDS / FHIR | MedicationRequest enviado à RNDS após emissão da prescrição digital | **Alta** |
| Consentimento para compartilhamento com farmácia | LGPD | Consentimento específico para que dados da prescrição sejam acessíveis pela farmácia parceira | **Alta** |

**Alerta crítico:** Sem ICP-Brasil, a prescrição digital é apenas informativa — **não tem validade legal**. Isso bloqueia o MVP 2 como fluxo formal. Alternativa: começar com prescrições informativas (PDF com QR code de verificação) enquanto a integração ICP-Brasil é implementada.

---

### 2.3 MVP 3 — Exportação para Hospital

| Obrigação | Padrão | O que fazer | Prioridade |
|-----------|--------|-------------|-----------|
| Envio de Sumário de Alta / RAC | RNDS / FHIR | Quando hospital solicita dados, responder com Bundle FHIR (Patient, Encounter, Condition, DiagnosticReport) | **Alta** |
| Consentimento explícito do paciente | LGPD | Consentimento granular: quais dados, para qual hospital, por quanto tempo. Registro auditável | **Crítica** |
| Escopo contratual com o hospital | LGPD / Contrato | Contrato de compartilhamento de dados definindo finalidade, escopo e responsabilidades (Data Processing Agreement) | **Alta** |
| TISS para internações conveniadas | TISS / ANS | Se a internação é via convênio, as guias TISS (SADT, internação) devem ser geradas ou referenciadas | **Média** |
| Anonimização para pesquisa | LGPD | Se hospital solicita dados para pesquisa (não atendimento), dados devem ser anonimizados | **Média** |

---

### 2.4 Futuro — Integração com Convênios

| Obrigação | Padrão | O que fazer | Prioridade |
|-----------|--------|-------------|-----------|
| Comunicação via padrão TISS | TISS 4.x (ANS) | Guias TISS (consulta, SADT, honorários) para autorização e faturamento. XML conforme schema ANS | **Obrigatória** |
| Elegibilidade do beneficiário | TISS | Verificação de cobertura via webservice TISS da operadora | **Alta** |
| Autorização prévia | TISS | Para procedimentos que exigem autorização, submeter guia TISS e aguardar resposta | **Alta** |
| Faturamento eletrônico | TISS | Lotes de faturamento no formato TISS para envio à operadora | **Média** |

---

## 3. RNDS — Detalhamento técnico

A RNDS é o barramento nacional de saúde e usa **FHIR R4** como formato de dados.

### 3.1 Fluxo de autenticação RNDS

```
Estabelecimento de saúde
         │
         │ (1) Certificado e-CNPJ (ICP-Brasil)
         ▼
   Portal de Serviços DATASUS
         │
         │ (2) Solicitar credenciamento
         ▼
   CNES validado + Aplicação registrada
         │
         │ (3) Client Credentials OAuth2
         ▼
   Token de acesso (Bearer)
         │
         │ (4) Chamadas autenticadas ao barramento
         ▼
   Endpoints RNDS (FHIR R4)
```

### 3.2 Pré-requisitos para integração RNDS

| Requisito | Status no projeto | Ação necessária |
|-----------|-------------------|-----------------|
| CNES do estabelecimento | Não implementado | Adicionar campo `cnes` na configuração do sistema |
| Certificado e-CNPJ (A1 ou A3) | Não implementado | Configurar armazenamento seguro do certificado |
| CNS do paciente | Não existe no schema | Adicionar campo `cns` na tabela `patients` |
| CNS do profissional | Não existe no schema | Adicionar campo `cns` na tabela `doctors` |
| Aplicação registrada no DATASUS | Não feito | Registrar via Portal de Serviços |
| Ambiente de homologação RNDS | Não configurado | Configurar endpoints de staging/produção |

### 3.3 Recursos FHIR exigidos pela RNDS

| Recurso FHIR | Quando enviar | MVP |
|---------------|---------------|-----|
| `Bundle` | Container para envio de múltiplos recursos | Todos |
| `Patient` | Identificação do paciente (CNS obrigatório) | Todos |
| `Practitioner` | Identificação do profissional (CNS + CRM) | Todos |
| `Organization` | Identificação do estabelecimento (CNES) | Todos |
| `Encounter` | Registro de atendimento | MVP 1, 3 |
| `ServiceRequest` | Pedido de exame | MVP 1 |
| `DiagnosticReport` | Resultado de exame | MVP 1 |
| `Observation` | Valores individuais do exame | MVP 1 |
| `MedicationRequest` | Prescrição de medicamento | MVP 2 |
| `Condition` | Diagnóstico (CID-10) | MVP 1, 3 |
| `Composition` | Sumário de atendimento (RAC) | MVP 1, 3 |

---

## 4. ICP-Brasil — Detalhamento técnico

### 4.1 Tipos de certificado necessários

| Certificado | Uso | MVP |
|------------|-----|-----|
| **e-CNPJ A1** (arquivo .pfx) | Autenticação do estabelecimento na RNDS e assinatura de documentos institucionais | MVP 1, 2, 3 |
| **e-CPF A1/A3** (do médico) | Assinatura digital de prescrições e atestados com validade jurídica | MVP 2 |

### 4.2 Implementação sugerida

- **Assinatura CAdES ou XAdES** conforme exigência do destino (RNDS aceita ambos)
- **Biblioteca sugerida:** `php-signer` ou integração com serviço externo de assinatura digital (BirdID, Soluti, etc.)
- **Armazenamento:** certificados A1 em cofre seguro (AWS KMS, HashiCorp Vault); certificados A3 requerem hardware token e interação do médico

---

## 5. LGPD — Requisitos específicos para interoperabilidade

A tabela `consents` já existe no projeto. Para interoperabilidade, são necessários tipos adicionais de consentimento:

| Tipo de consentimento | Quando solicitar | Granularidade |
|----------------------|-----------------|---------------|
| `data_sharing_lab` | Antes de enviar pedido de exame ao laboratório parceiro | Por parceiro + por evento |
| `data_sharing_pharmacy` | Antes de disponibilizar prescrição à farmácia | Por parceiro + por evento |
| `data_sharing_hospital` | Antes de exportar dados ao hospital | Por hospital + escopo de dados + período |
| `data_sharing_insurance` | Antes de consultar/enviar dados ao convênio | Por operadora |
| `rnds_submission` | Antes de enviar dados à RNDS | Uma vez (pode ser no cadastro) |

**Direito de revogação:** o paciente deve poder revogar consentimento a qualquer momento. Após revogação, o sistema deve cessar o compartilhamento (não retroativamente — dados já enviados seguem a legislação do receptor).

**Portabilidade (Art. 18, V — LGPD):** o paciente pode solicitar portabilidade dos dados em formato estruturado. FHIR como formato interno facilita atender essa exigência.

---

## 6. CFM — Resoluções aplicáveis

| Resolução | Tema | Impacto |
|-----------|------|---------|
| **2.314/2022** | Telemedicina | Define regras para teleconsulta, telediagnóstico, telemonitoramento. Exige registro em prontuário e termo de consentimento |
| **2.299/2021** | Prescrição eletrônica | Prescrição digital com assinatura ICP-Brasil tem mesma validade que receita física |
| **1.821/2007** | Prontuário eletrônico | Requisitos de armazenamento (20 anos), segurança, backup e controle de acesso |
| **2.217/2018** | Código de Ética Médica | Sigilo profissional — compartilhamento de dados exige consentimento ou determinação legal |

---

## 7. Checklist regulatório antes de implementar

### Antes do MVP 1 (Laboratório)
- [ ] Adicionar campo `cns` em `patients` e `doctors`
- [ ] Configurar campo `cnes` do estabelecimento
- [ ] Registrar aplicação no Portal de Serviços DATASUS (homologação)
- [ ] Obter certificado e-CNPJ A1 do estabelecimento
- [ ] Implementar consentimento `data_sharing_lab` e `rnds_submission`
- [ ] Validar que o armazenamento de dados atende CFM 1.821/2007 (20 anos)

### Antes do MVP 2 (Farmácia)
- [ ] Integrar assinatura digital ICP-Brasil (e-CPF do médico)
- [ ] Implementar endpoint de verificação de prescrição
- [ ] Implementar consentimento `data_sharing_pharmacy`
- [ ] Avaliar obrigações SNGPC para medicamentos controlados

### Antes do MVP 3 (Hospital)
- [ ] Implementar consentimento granular `data_sharing_hospital`
- [ ] Preparar Data Processing Agreement (modelo de contrato)
- [ ] Implementar filtro de escopo por contrato (hospital X só vê dados Y)
- [ ] Avaliar necessidade de guias TISS para internações conveniadas

---

## 8. Documentos relacionados

- [SchemaIntegracoes.md](SchemaIntegracoes.md) — tabelas de banco que implementam os campos regulatórios
- [MVP1.md](MVP1.md) — especificação técnica do MVP 1 (laboratório)
- [SegurancaAPIPublica.md](SegurancaAPIPublica.md) — autenticação e autorização da API pública
- [docs/interoperabilidade/](../docs/interoperabilidade/) — documentação estratégica da feature

---

*Criado em: março/2026.*
