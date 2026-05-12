# Feature Spec — Integração ICP-Brasil (MVP pragmático)

> Status: `review`  
> Autor: Tech Lead Agent · Data: 2026-05-09  
> Atualização MVP: 2026-05-09 — simplificação pós-crítica (menos arquitetura teórica, mais adoção pelo médico)

---

## Objetivo

Permitir que **prescrições**, **atestados** e **documentos médicos oficiais** gerados na plataforma tenham assinatura **ICP-Brasil** com **PAdES no PDF final**, vinculada ao **médico**, sem travar o projeto em desenho “perfeito” antes de haver uso real.

## Motivação

- Obrigatoriedade CFM (Res. 2.314/2022, Art. 8º) e pendências já documentadas no repositório.
- O **risco principal do MVP não é trocar de provedor no futuro** — é o **médico deixar de usar** a funcionalidade por atrito (fluxo longo a cada receita).
- O código legado (`DigitalSignatureDriver`, jobs de assinatura) pode ser **adaptado ou substituído** em favor de um contrato único e claro; a spec não prescreve manter todas as camadas antigas se atrapalharem o MVP.

---

## Estratégia MVP (o que vale para agora)

1. O sistema suportará assinatura **ICP-Brasil** vinculada ao **médico** (não basta “o sistema gerou”).
2. O **PDF final** será assinado em **PAdES** (pipeline: **gerar PDF → assinar PDF → persistir PDF assinado**).
3. O MVP utilizará **um único provedor homologado** escolhido e integrado de ponta a ponta. **Múltiplos provedores** e generalização “agnóstica” ficam **após validação do MVP**.
4. O fluxo de assinatura será **otimizado para baixo atrito operacional do médico** (ver seção UX).
5. O **contrato principal** de assinatura no domínio deve ser conceitualmente: **`signPdf(SignableDocument, Doctor)`** — um ponto de entrada que recebe o documento assinável (ex.: PDF gerado + metadados) e o médico titular. Implementação concreta (service, job) deriva disso; evitar proliferar drivers/adapters antes de precisar.
6. **Certificado A1** poderá ser aceito **no MVP** para facilitar adoção (muitos médicos já usam A1). **Certificado em nuvem** e refinamentos de custódia **não são pré-requisito do primeiro MVP** — avaliar na sequência.
7. **A3 (token físico)** fica como **talvez** / fase posterior, salvo demanda explícita.

---

## O que não fazer no MVP (anti-padrões)

- Desenhar **troca de provider** e **camadas genéricas** antes de **um** fluxo funcionando com **um** fornecedor.
- Impor ao médico, **por documento**, sequências do tipo: login + OTP + aprovação + redirecionamento + callback + espera longa — risco direto de abandono.
- Tratar login, 2FA e sessão da aplicação como substituto da **assinatura jurídica** do PDF.

---

## Regras de negócio (enxutas)

1. Documentos oficiais listados no objetivo exigem **PAdES** no arquivo entregue/persistido quando o ambiente for de produção com ICP ativo.
2. **e-CNPJ institucional** não substitui automaticamente a assinatura do **médico** sem parecer jurídico explícito (regra mantida, sem complicar o MVP).
3. Ambiente local/dev pode usar assinatura **sem validade legal** (ex.: driver atual `null` ou stub) — nunca confundir com produção.
4. **LGPD / segurança:** senha de A1, PKCS12 e respostas do provedor **não** entram em log.

---

## UX e produto (prioridade máxima)

| Princípio                     | Significado prático                                                                                                                     |
| ----------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Poucos passos                 | Quantidade mínima de interações entre “emitir” e “assinado”.                                                                            |
| Previsibilidade               | O médico entende o que vai acontecer antes de confirmar.                                                                                |
| Sem “um calvário por receita” | Evitar repetir jornada pesada documento a documento quando batch ou sessão assinada fizer sentido (a definir com o provedor escolhido). |
| Falha legível                 | Erro curto, acionável; detalhe técnico só para suporte/logs sanitizados.                                                                |

Critério de sucesso do MVP: **médico consegue assinar com facilidade** e o PDF resultante é **validável** como PAdES ICP-Brasil — não “arquitetura pronta para N provedores”.

---

## Provedor (MVP)

- **Um** contrato com **um** provedor (API/SDK que suporte PAdES e homologação).
- Requisitos mínimos: PAdES em PDF; ambiente sandbox; forma de verificar assinatura; documentação estável.
- **Pós-MVP:** segundo provedor, nuvem, multi-tenant avançado — só depois de métricas de uso.

---

## Certificado (MVP)

| Tipo                                   | MVP                        |
| -------------------------------------- | -------------------------- |
| **A1** (arquivo .pfx / senha ou cofre) | **Sim** — facilita adoção. |
| Nuvem / auth forte remota              | Depois, se produto exigir. |
| A3 token                               | Talvez / fase posterior.   |

Custódia de A1: tratar com **segurança** (cofre, não versionar arquivo, política de rotação) — a spec não detalha implementação do cofre aqui.

---

## Arquitetura (mínima necessária)

**Hoje (baseline):** jobs chamam serviço de assinatura sobre **payload canônico** antes do PDF final — **incompatível** com a decisão PAdES no arquivo.

**Alvo MVP:**

```
Emitir documento → gerar PDF → signPdf(SignableDocument, Doctor) → persistir PDF assinado + metadados
```

- Integração com o provedor escolhido fica **atrás** desse contrato (uma implementação concreta, não uma fábrica de N adapters no MVP).
- **RNDS:** certificado de mTLS **não** se reutiliza automaticamente para assinatura clínica (papéis separados).

---

## Backend (resumo)

- Ajustar jobs (`SignAndGenerateCertificatePdfJob`, `SignPrescriptionJob`, etc.) para ordem **PDF → assinatura PAdES → armazenamento**.
- Onde hoje existir `DigitalSignatureDriver` / `IcpBrasilSignatureDriver` stub: **convergir** para o fluxo acima ou encapsular `signPdf` sem obrigar o time a manter duas filosofias em paralelo por muito tempo.
- Testes em CI: **mock HTTP** do provedor (ou stub mínimo) — sem dependência de ICP real no pipeline.

---

## Frontend (resumo)

- Fluxo alinhado à tabela de UX; sem telas extras “porque o diagrama tinha callback”.
- Aviso claro quando a assinatura for apenas de desenvolvimento (sem ICP).

---

## Banco de dados

- Estender apenas o necessário para apontar para o **PDF assinado** e metadados de verificação, quando o formato do provedor estiver fechado.

---

## Riscos (priorizados)

| Risco                           | Mitigação                                                                       |
| ------------------------------- | ------------------------------------------------------------------------------- |
| **Médico não usar** (principal) | UX enxuta; A1 no MVP; medir abandono.                                           |
| Atrito do provedor escolhido    | Escolher fornecedor com API/SDK que permita fluxo curto; sandbox antes de prod. |
| Refatorar pipeline antigo       | Uma sprint focada em gerar → assinar → persistir.                               |

---

## Plano de implementação (ordem sugerida)

1. Escolher **um** provedor e validar sandbox PAdES.
2. Implementar **`signPdf(SignableDocument, Doctor)`** (ou equivalente nomeado no projeto) e pipeline PDF → assinado.
3. Fluxo médico mínimo viável + testes com mock.
4. Produção: secrets, monitoramento, runbook.
5. **Depois:** nuvem, segundo provedor, generalizações.

---

## Checklist

- [ ] Um provedor integrado com PAdES em PDF real
- [ ] `signPdf` (ou serviço equivalente único) como eixo do domínio
- [ ] Pipeline gerar → assinar → persistir
- [ ] UX com poucos passos; critério de aceite com médico/usuário chave
- [ ] CI com mock; sem segredos em log
- [ ] `/review-security` nos arquivos tocados

---

## Referências no repositório

- `app/Services/Signatures/DigitalSignatureService.php`
- `app/Services/Signatures/IcpBrasilSignatureDriver.php` (STUB)
- `app/Contracts/DigitalSignatureDriver.php`
- `config/telemedicine.php` — `signature`
- `app/Integrations/Rnds/Certificate/RndsCertificateManager.php` (mTLS RNDS apenas)
- `app/Jobs/SignAndGenerateCertificatePdfJob.php`, `app/Jobs/SignPrescriptionJob.php`
