# Configuração de Domínio e EC2

*Documento em: `docs/layers/infrastructure/` (Camada de Infraestrutura)*

> ⚠️ **Segurança**: Este arquivo não deve conter chaves privadas (PEM) no repositório. Armazene a chave em local seguro (ex.: gerenciador de senhas ou AWS Secrets Manager) e adicione `domainconfig.md` ao `.gitignore` se guardar dados sensíveis. Rotacione a chave na AWS se ela tiver sido exposta.

## Nome do domínio

- **Domínio**: telemedicinaparatodos.com

## DNS

Para criar o DNS é necessário o **IPv4 do servidor EC2** (servidor de hospedagem na AWS).

## Criando o EC2

1. Criar instância EC2 na AWS.
2. Gerar ou usar um par de chaves (key pair) para SSH.
3. **Chave privada (PEM)**: guardar em local seguro **fora do repositório**. Não versionar no Git.

## Conexão SSH com a instância EC2

```bash
ssh -i telemedicine-key.pem ubuntu@<IP_PUBLICO_EC2>
```

Substitua `<IP_PUBLICO_EC2>` pelo IP público da sua instância (ex.: 18.205.104.175 ou o IP atual da EC2).

O arquivo da chave (ex.: `telemedicine-key.pem`) deve estar em um diretório seguro (ex.: `~/.ssh/` ou Downloads), com permissões restritas:

```bash
chmod 400 telemedicine-key.pem
```

---

*Para detalhes da infraestrutura atual (EC2, Nginx, DNS, deploy), veja [Infraestrutura.md](./Infraestrutura.md).*
