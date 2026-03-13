# 📘 Contexto e Motivação --- Simulação de Arquitetura AWS em Servidor Caseiro

Este documento explica o contexto, os objetivos, as decisões técnicas e
a motivação por trás da criação da simulação de serviços AWS em um
servidor local, com foco em um sistema de telemedicina.

------------------------------------------------------------------------

# 🎯 1. Contexto Atual do Projeto

O projeto consiste em uma plataforma de telemedicina, onde usuários
poderão:

-   Realizar videochamadas médicas (WebRTC + SFU)
-   Enviar mensagens em tempo real
-   Armazenar exames, imagens e documentos
-   Utilizar backend Laravel com Reverb
-   Operar em um ambiente escalável e seguro

Inicialmente, a infraestrutura foi implantada na AWS EC2, utilizando
diversos serviços cloud.\
Porém, mesmo em camadas gratuitas, os custos começaram a crescer devido
a:

-   Tráfego de rede
-   Storage (S3)
-   Logs
-   Serviços gerenciados
-   Uso contínuo de instâncias

------------------------------------------------------------------------

# 💡 2. Motivação para Simular a AWS Localmente

A simulação local da AWS tem três motivações principais:

## ✅ 2.1 Aprendizado Profundo de Cloud e DevOps

Rodar a infraestrutura localmente permite compreender:

-   Arquitetura distribuída
-   Orquestração de containers
-   Networking
-   Load balancing
-   Segurança de servidores
-   Observabilidade
-   Infraestrutura como código

Esse conhecimento é diretamente aplicável ao mercado profissional.

------------------------------------------------------------------------

## 💰 2.2 Redução de Custos

Ao migrar a infraestrutura para um servidor caseiro:

-   Não há cobrança por tráfego
-   Não há cobrança por storage
-   Não há cobrança por instâncias
-   Apenas custo de energia elétrica

Isso permite testar e aprender sem limitações financeiras.

------------------------------------------------------------------------

## 🧪 2.3 Ambiente de Laboratório Controlado

O servidor caseiro funciona como um laboratório de engenharia,
permitindo:

-   Testar arquiteturas complexas
-   Simular falhas
-   Realizar experimentos sem risco
-   Preparar deploy futuro em cloud real

------------------------------------------------------------------------

# ☁️ 3. Por que Simular Serviços da AWS?

A AWS fornece uma arquitetura moderna composta por múltiplos serviços
especializados.\
Simular esses serviços localmente permite aprender como grandes sistemas
são construídos.

Exemplos de serviços simulados:

  Serviço AWS      Simulação Local
  ---------------- --------------------------
  S3               MinIO
  EC2              Docker Containers
  RDS              PostgreSQL / MySQL
  ElastiCache      Redis
  SQS              RabbitMQ
  ALB              Nginx Reverse Proxy
  CloudFront       Nginx Cache + Cloudflare
  ACM              Certbot + Let's Encrypt
  Media Services   Janus / Mediasoup

------------------------------------------------------------------------

# 🧠 4. Importância para o Projeto de Telemedicina

A telemedicina exige uma infraestrutura crítica:

-   Baixa latência
-   Alta disponibilidade
-   Segurança de dados médicos
-   Escalabilidade
-   Comunicação em tempo real

Mesmo em ambiente de aprendizado, replicar essa arquitetura proporciona:

-   Conhecimento real de sistemas hospitalares
-   Base para produção futura
-   Experiência prática de engenheiro de sistemas

------------------------------------------------------------------------

# 🐳 5. Por que Docker?

Docker foi escolhido porque:

-   Permite isolamento de serviços
-   Facilita replicação em qualquer máquina
-   Simula microserviços
-   Permite orquestração com docker-compose
-   É padrão industrial

Cada serviço AWS é representado por um container independente, simulando
a arquitetura real de cloud.

------------------------------------------------------------------------

# 🖥️ 6. Arquitetura Distribuída Caseira

A arquitetura foi planejada para múltiplas máquinas:

## 🧱 Storage Node

-   MinIO (S3)
-   Muito armazenamento

## ⚙️ Application Node

-   Laravel
-   PostgreSQL
-   Redis
-   RabbitMQ
-   Nginx

## 🎥 Media Server

-   Janus / Mediasoup (WebRTC SFU)
-   Alta CPU e RAM

Essa separação simula um mini data center real.

------------------------------------------------------------------------

# ⚠️ 7. Limitações do Ambiente Caseiro

Apesar do aprendizado, existem limitações:

-   IP residencial e NAT
-   Upload limitado
-   Latência variável
-   Sem redundância real
-   Sem SLA

Este ambiente é laboratório e estudo, não produção hospitalar.

------------------------------------------------------------------------

# 🚀 8. Roadmap de Implementação

## Fase 1 --- Base Linux

-   Ubuntu Server LTS
-   SSH
-   Firewall
-   Usuário seguro

## Fase 2 --- Docker

-   Docker Engine
-   Docker Compose
-   Containers básicos

## Fase 3 --- Simulação AWS

-   MinIO
-   PostgreSQL
-   Redis
-   Nginx
-   Certbot

## Fase 4 --- Telemedicina

-   Laravel + Reverb
-   WebRTC SFU
-   Mensageria
-   Observabilidade

------------------------------------------------------------------------

# 🎓 9. Benefícios Educacionais

Este projeto proporciona aprendizado em:

-   Cloud Architecture
-   DevOps
-   Linux Server
-   Networking
-   Security
-   Distributed Systems
-   Real-time communication
-   High availability concepts

------------------------------------------------------------------------

# 🧾 10. Conclusão

A simulação da AWS em servidor caseiro é uma estratégia poderosa para:

-   Aprender infraestrutura cloud de verdade
-   Desenvolver um sistema de telemedicina robusto
-   Reduzir custos
-   Construir conhecimento profissional avançado

Este documento serve como base técnica e conceitual para guiar a
implementação e evolução da infraestrutura.
