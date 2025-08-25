Sempre que abrir o Cursor para uma task, dê contexto:

“Considere as regras em docs/SystemRules.md e o guia em docs/DevGuide.md para criar este código.”



👉 Funciona como um manual de como o código deve ser escrito.
Exemplo:

Arquitetura padrão: Controller → Service → Repository.

Padrões de código: PSR-12, nomes em inglês, migrations com timestamps.

Testes: todo método crítico deve ter teste unitário.

Tratamento de erros: nunca deixar try/catch vazio, sempre logar exceções.

Segurança: sempre validar inputs, usar prepared statements, nunca expor stack trace em produção.

📌 Benefício: você pede “refatore este código seguindo meu guia de desenvolvimento” e o Cursor aplica suas próprias regras.

Exemplos:

# 🛠️ Guia de Desenvolvimento

## 🔹 Arquitetura
- Padrão **Controller → Service → Repository**.
- Camada de **DTOs** para transferir dados entre camadas.
- Uso de **Form Requests** para validações no Laravel.

---

## 🔹 Estilo de Código
- Seguir **PSR-12**.
- Código em inglês (variáveis, funções, classes).
- Migrations sempre com **timestamps**.

---

## 🔹 Boas Práticas
- Aplicar **SOLID** e **DRY**.
- Evitar duplicação de código.
- Usar **Dependency Injection** sempre que possível.

---

## 🔹 Testes
- Testes unitários obrigatórios para Services.
- Testes de integração para endpoints críticos.
- Usar PHPUnit + Pest.

---

## 🔹 Tratamento de Erros
- Nunca deixar `try/catch` vazio.
- Registrar exceções no **Laravel Log**.
- Mostrar mensagens genéricas para usuários finais.

---

## 🔹 Segurança
- Sempre usar **prepared statements** no Eloquent.
- Desabilitar stack trace em produção.
- Encriptação de senhas com `bcrypt`.


