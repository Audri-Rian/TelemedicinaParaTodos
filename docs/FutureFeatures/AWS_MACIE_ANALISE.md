# Análise: Amazon Macie e Proteção de Dados Sensíveis

## Contexto

Durante os estudos sobre serviços de segurança da AWS, surgiu o interesse em implementar no projeto algo semelhante ao **Amazon Macie** — um serviço que utiliza **machine learning** e **pattern matching** para descobrir, classificar e proteger dados sensíveis armazenados no S3.

### O que o Amazon Macie faz

- Analisa o conteúdo de arquivos no S3 (JSON, CSV, TXT, logs, documentos)
- Usa **regex/padrões** para detectar formatos conhecidos (CPF, cartão de crédito, email)
- Usa **machine learning** para entender o contexto dos dados e identificar PII mesmo sem padrão fixo
- Classifica automaticamente os dados como sensíveis (PII, financeiro, credencial)
- Gera alertas quando dados sensíveis estão expostos

## Conclusão da Análise

Após avaliar o projeto, concluiu-se que **não há necessidade de implementar um serviço similar ao Macie** pelos seguintes motivos:

1. **Dados já são estruturados** — O projeto usa PostgreSQL com schema bem definido. Os models (`Patient`, `MedicalRecord`, `Prescription`, etc.) já mapeiam exatamente onde estão os dados sensíveis.
2. **Complexidade desproporcional** — Construir ML para classificação de dados sensíveis é caro e complexo para o escopo do projeto.
3. **Tudo é sensível** — Em uma plataforma de telemedicina, praticamente todos os dados são sensíveis por natureza. Não é necessário ML para descobrir isso.
4. **Base de segurança já existente** — O projeto já possui LGPD compliance, audit logging, sanitização de input, security headers e controle de acesso.

## Sugestões para Implementação Futura

Em vez de replicar o Macie, foram identificadas abordagens mais pragmáticas e de alto impacto:

### 1. Data Classification nos Models

Anotar os models com níveis de sensibilidade (ex: `alto`, `médio`, `baixo`) e usar essa classificação para controlar automaticamente o comportamento de logging, caching e exports.

**Exemplo conceitual:**

```php
// Definir no model quais campos são sensíveis
protected array $sensitivityLevel = [
    'cpf' => 'alto',
    'email' => 'medio',
    'nome' => 'medio',
    'diagnostico' => 'alto',
];
```

### 2. Log Sanitizer para PII

Middleware que garante que dados como CPF, email, dados médicos e outras PII **nunca apareçam em logs de produção**. Previne vazamento acidental de dados sensíveis em stack traces, logs de erro e logs de aplicação.

### 3. Amazon Macie Real (se deploy na AWS)

Caso o deploy seja feito na AWS e os documentos médicos sejam armazenados no S3, simplesmente **habilitar o Amazon Macie** diretamente. É plug and play, já está testado e o custo é baixo para volumes pequenos.

### 4. DLP (Data Loss Prevention) nos Uploads

Scanner baseado em regex para documentos enviados por pacientes e médicos. Detecta dados sensíveis expostos em nomes de arquivos, metadados e conteúdo de uploads, sem necessidade de ML.

### 5. Validação de Anonimização

Após o exercício do "direito ao esquecimento" (LGPD), verificar automaticamente que os dados foram realmente removidos ou anonimizados em todas as tabelas e storages.

## Prioridade Sugerida

| Prioridade | Feature                        | Complexidade | Impacto   |
| ---------- | ------------------------------ | ------------ | --------- |
| 1          | Log Sanitizer para PII         | Baixa        | Alto      |
| 2          | Data Classification nos Models | Baixa        | Alto      |
| 3          | DLP nos Uploads                | Média        | Médio     |
| 4          | Validação de Anonimização      | Média        | Médio     |
| 5          | Amazon Macie (se AWS + S3)     | Baixa        | Situacional |

## Referências

- [Amazon Macie — AWS](https://aws.amazon.com/macie/)
- [LGPD — Lei 13.709/2018](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)
- [LGPDService.php](../app/Services/LGPDService.php) — Implementação atual de LGPD no projeto
