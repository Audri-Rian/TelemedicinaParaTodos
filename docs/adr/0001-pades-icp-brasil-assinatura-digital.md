# ADR-0001: Assinatura digital PAdES com certificado A1 local

**Status:** accepted  
**Data:** 2026-05-14  
**Decisores:** Tech Lead, Dev Backend  
**Spec de origem:** [`docs/specs/estrutura-integracao-icp-brasil.md`](../specs/estrutura-integracao-icp-brasil.md)  
**Relacionado:** CFM Res. 2.314/2022 Art. 8º, LGPD

---

## Contexto

O sistema emite prescrições e atestados médicos. A Resolução CFM 2.314/2022 (Art. 8º) exige que documentos clínicos emitidos em telemedicina tenham **assinatura ICP-Brasil** para validade legal. Sem isso, o médico não pode assinar receitas e atestados digitalmente com valor jurídico — risco regulatório direto.

O código existente antes desta decisão:

- Assinava um **payload canônico** (string JSON) antes de gerar o PDF — incompatível com PAdES, que exige assinar o **arquivo PDF final**
- O driver `IcpBrasilSignatureDriver` era um **stub** que lançava `RuntimeException`
- Não havia geração de PDF para prescrições (apenas para atestados)
- O pipeline estava na ordem errada: assinar → gerar PDF, quando deveria ser gerar PDF → assinar → persistir

O risco principal identificado na spec **não era técnico** (trocar de provider no futuro), mas **de adoção**: médico abandona o fluxo se cada receita exigir múltiplos passos ou longa espera.

---

## Decisão

**Implementar PAdES-BES com certificado A1 local (PFX/PKCS#12), sem dependência de API externa no MVP.**

Pipeline obrigatório para todos os documentos clínicos oficiais:

```
gerar PDF (DomPDF) → signPdf(pdfBytes, Doctor, reason) → persistir PDF assinado
```

O `PdfSigner` é o único ponto de entrada de assinatura no domínio. Drivers são plugáveis via `config('telemedicine.signature.driver')`.

---

## Alternativas consideradas

| Alternativa                                                                  | Por que descartada                                                                                                                                                                                                                                                    |
| ---------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Manter assinatura de payload canônico** (hash SHA-256 + verification code) | Não é PAdES. Não satisfaz CFM 2.314/2022 Art. 8º. PDF gerado após a assinatura não carrega a assinatura embutida — inválido para validadores ICP-Brasil.                                                                                                              |
| **Cloud signing API (Soluti BirdID, Certisign API)**                         | Requer credenciais de provedor antes de qualquer código funcionar. Adiciona latência de rede no pipeline crítico. Provedor não escolhido no momento da implementação. Spec explicitamente orienta não bloquear em "arquitetura perfeita" antes de um fluxo funcionar. |
| **TCPDF para geração e assinatura**                                          | Exigiria reescrever todos os templates PDF (DomPDF já em uso, Blade templates existentes). Dependência grande adicionada só para assinatura. DomPDF + PadesEmbedder separam responsabilidades corretamente.                                                           |
| **setasign/fpdi (comercial/pago)**                                           | Licença paga para funcionalidades avançadas de manipulação PDF. Não justificado para MVP com A1 local.                                                                                                                                                                |
| **A3 token físico**                                                          | Exige hardware presente no servidor ou integração com TSA remota. Adicionaria atrito severo ao médico. Spec classificou como "talvez / fase posterior".                                                                                                               |
| **Múltiplos provedores com abstração genérica desde o início**               | Anti-padrão explicitamente listado na spec. Generalizar antes de ter um fluxo funcionando desperdiça sprint e gera código morto.                                                                                                                                      |

---

## Implementação

### Novos componentes

| Arquivo                                      | Papel                                                                  |
| -------------------------------------------- | ---------------------------------------------------------------------- |
| `app/Contracts/PdfSigner.php`                | Interface do domínio: `signPdf(bytes, Doctor, reason): bytes`          |
| `app/Services/Signatures/NullPdfSigner.php`  | Driver dev/staging — retorna PDF inalterado, sem validade legal        |
| `app/Services/Signatures/A1PdfSigner.php`    | Driver produção — lê PFX via config, delega para PadesEmbedder         |
| `app/Support/Signatures/PadesEmbedder.php`   | Implementação PAdES-BES: incremental PDF update + `openssl_cms_sign()` |
| `resources/views/pdf/prescription.blade.php` | Template PDF de receita (não existia)                                  |

### Pipeline corrigido

**Antes (errado):**

```
issuePrescription() → SignPrescriptionJob → signCanonicalPayload() → [sem PDF]
issueCertificate()  → SignAndGenerateCertificatePdfJob → signCanonicalPayload() → generatePdf()
```

**Depois (correto):**

```
issuePrescription() → SignPrescriptionJob → buildPrescriptionPdfBytes() → signPdf() → persistSignedPdf()
issueCertificate()  → SignAndGenerateCertificatePdfJob → buildCertificatePdfBytes() → signPdf() → persistSignedPdf()
```

### Como `PadesEmbedder` funciona

1. Analisa PDF existente: encontra objeto catalog e último número de objeto
2. Appends incremental update (ISO 32000-1 §12.8) com:
    - Dicionário `/Sig` com placeholders para `/ByteRange` e `/Contents`
    - Widget de campo de assinatura
    - Catalog atualizado com `/AcroForm` referenciando o campo
3. Calcula `/ByteRange = [0, offsetBeforeContents, offsetAfterContents, afterLen]`
4. Assina os bytes designados via `openssl_cms_sign()` → DER output
5. Hex-encoda o DER, preenche o placeholder `/Contents`

Resultado: PDF original inalterado + update incremental com assinatura PAdES-BES (`adbe.pkcs7.detached`).

### Config

```env
SIGNATURE_DRIVER=null             # dev/staging
SIGNATURE_DRIVER=a1_local         # produção
SIGNATURE_A1_PFX_PATH=/path/to/cert.pfx
SIGNATURE_A1_PFX_PASSWORD=secret
SIGNATURE_VERIFICATION_URL_TEMPLATE=https://app.example/verify/{code}
```

---

## Consequências

### Positivas

- **Conformidade CFM**: pipeline gerar → assinar → persistir satisfaz obrigatoriedade ICP-Brasil no PDF final
- **Baixo atrito para o médico**: assinatura acontece em background (job na fila `documents`), sem interação extra por documento
- **Plugável**: trocar `NullPdfSigner` → `A1PdfSigner` só requer variável de ambiente; adicionar provider cloud no futuro é implementar a interface `PdfSigner`
- **Dev/staging funcional sem certificado**: `NullPdfSigner` mantém o fluxo completo operante sem PFX real
- **Sem nova dependência Composer**: `PadesEmbedder` usa `openssl_cms_sign()` nativo do PHP 8.0+

### Negativas / trade-offs

- **PadesEmbedder usa regex para parsear PDF**: funciona para PDFs DomPDF com xref table tradicional; PDFs com cross-reference streams (PDF 1.5+ compressed) podem falhar. Mitigação: DomPDF gera PDF 1.7 com xref tradicional por padrão.
- **Custódia do PFX é responsabilidade do operator**: arquivo `.pfx` e senha devem estar em cofre (Vault, AWS Secrets Manager). Não há gestão de certificados no sistema — intencionalmente fora do escopo MVP.
- **Um certificado A1 por instalação**: MVP usa um único PFX configurado na plataforma (provavelmente e-CNPJ da clínica). Validação jurídica de se e-CNPJ substitui e-CPF do médico **não foi feita** — pendência explícita.
- **`verification_code` gerado no job**: se dois workers processarem o mesmo job (falha exactly-once), códigos divergentes podem ser gerados. Mitigação: `ShouldBeUnique` no job com `uniqueFor = 3600`. Solução completa: gerar `verification_code` atomicamente em `issuePrescription()`.

### Riscos residuais

| Risco                                          | Mitigação atual                                 | Fase                                          |
| ---------------------------------------------- | ----------------------------------------------- | --------------------------------------------- |
| Médico abandona fluxo (principal risco do MVP) | UX assíncrona; A1 sem etapa extra por documento | Medir com analytics pós-lançamento            |
| PFX expirado em produção silencia assinatura   | `A1PdfSigner` lança exceção → job vai para DLQ  | Monitorar DLQ + alerta de expiração (pós-MVP) |
| e-CNPJ vs e-CPF — validade jurídica            | Pendência com jurídico                          | Antes do go-live                              |
| Cross-reference streams quebram PadesEmbedder  | DomPDF não gera xref streams por padrão         | Testar com validador ITI antes de produção    |

---

## O que vem depois (pós-MVP)

- [ ] Validação jurídica: e-CNPJ institucional vs e-CPF por médico
- [ ] Verificação pública de assinatura via endpoint `/verify/{code}`
- [ ] Certificado em nuvem (BirdID / SafeSign) como driver alternativo
- [ ] Gestão de rotação de certificado A1 (alerta de expiração, runbook)
- [ ] Gerar `verification_code` em `issuePrescription()` em vez de no job
- [ ] Testar PDF resultante com validadores ICP-Brasil (ITI, Certisign sandbox)
