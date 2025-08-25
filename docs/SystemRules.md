Sempre que abrir o Cursor para uma task, dê contexto:

“Considere as regras em docs/SystemRules.md e o guia em docs/DevGuide.md para criar este código.”

👉 Serve para alinhar as regras de negócio e as restrições técnicas.
Exemplo de tópicos:

Domínio: o que o sistema faz, quem são os usuários, quais processos ele resolve.

Regras de negócio:

SKU deve ser único e gerado automaticamente.

Um pedido só pode ser finalizado se tiver pagamento aprovado.

Soft delete deve ser usado em todas as entidades.

Políticas de segurança: autenticação, autorização, logs de auditoria.

📌 Benefício: quando você pedir ao Cursor “crie um endpoint de criação de produto”, ele já sabe que precisa respeitar essas regras.

Exemplos:
# 📜 Regras do Sistema

## 🎯 Objetivo
O sistema visa informatizar a administração de uma loja física/virtual, centralizando o controle de **produtos, estoque, clientes, fornecedores e vendas**.

---

## ⚖️ Regras de Negócio
1. **Produtos**
   - Cada produto deve ter um **SKU único**.
   - O SKU deve ser **gerado automaticamente** no momento do cadastro.
   - Produtos devem suportar **soft delete**.

2. **Usuários**
   - Autenticação via **Laravel Sanctum**.
   - Perfis de acesso: **Admin, Gerente, Usuário comum**.
   - Apenas Admin pode excluir usuários.

3. **Pedidos**
   - Um pedido só pode ser finalizado se o **pagamento for aprovado**.
   - Cancelamentos devem gerar **registro de auditoria**.

---

## 🔐 Segurança
- Todos os inputs devem ser validados e sanitizados.
- Proteger contra SQL Injection, XSS e CSRF.
- Nunca exibir mensagens de erro internas para o cliente final.

---

## 📦 Padrões de Dados
- Datas no formato **YYYY-MM-DD**.
- Valores monetários em **centavos (inteiro)** no banco de dados.
