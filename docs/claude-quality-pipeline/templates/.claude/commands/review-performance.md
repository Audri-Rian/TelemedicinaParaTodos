---
description: Executa performance-reviewer nos arquivos staged (ou nos arquivos passados como argumento)
argument-hint: [arquivos opcionais]
---

Execute o subagent `performance-reviewer` focado no escopo abaixo.

**Escopo**:

- Se houver argumentos em `$ARGUMENTS`, analise apenas esses arquivos.
- Caso contrário, rode `git diff --cached --name-only` para pegar os arquivos staged.
- Se não houver staged, rode `git diff --name-only HEAD`.

**Instruções**:

1. Liste os arquivos que serão analisados
2. Invoque o subagent `performance-reviewer` passando os arquivos
3. Retorne o relatório para o usuário
