## 📚 Arquitetura em Camadas da Documentação

Esta pasta organiza a documentação técnica do **Telemedicina Para Todos** em **camadas arquiteturais**, facilitando onboarding, manutenção e evolução do sistema.

As camadas aqui documentadas não substituem os módulos existentes (como `modules/`, `Architecture/`, `aws/`), mas criam uma **visão transversal** que mapeia cada área do sistema para uma camada clara.

- **[Camada de Domínio & Aplicação](domain/README.md)** (`domain/`)  
  Regras de negócio, fluxos funcionais e casos de uso por módulo: Auth, Appointments, Mensagens, Videochamada, Prontuários. Índice que aponta para `docs/modules/` e para as camadas técnicas.

- **[Camada de Sinalização](signaling/README.md)** (`signaling/`)  
  Controle de sessões em tempo real, presença, autenticação de peers, eventos WebSocket e integração com Laravel Reverb. Videochamadas (sinalização) e mensagens.

- **[Camada de Mídia](media/README.md)** (`media/`)  
  Transmissão de áudio/vídeo/dados em tempo real (WebRTC/PeerJS), topologias P2P/SFU/MCU e controle de qualidade de mídia.

- **[Camada de Persistência](persistence/README.md)** (`persistence/`)  
  Banco de dados relacional, Redis, estrutura de dados, modelos, logs e estratégias de cache.

- **[Camada de Infraestrutura](infrastructure/README.md)** (`infrastructure/`)  
  Deploy, rede, DNS/CDN, servidores, escalabilidade, observabilidade, backup e hardening de produção.

- **[Camada de Arquitetura & Governança](architecture-governance/README.md)** (`architecture-governance/`)  
  Decisões arquiteturais, padrões de código, diagramas, regras de sistema, requisitos e roadmap técnico.

### 🔗 Como Navegar

- **Por negócio/funcionalidade:** comece por `domain/README.md` para ver todos os módulos (Auth, Consultas, Mensagens, Videochamada, Prontuários) e suas regras.
- **Por visão geral do sistema:** use `architecture-governance/README.md`.
- **Por camada técnica:**
  - `signaling/` — tempo real / eventos.
  - `media/` — WebRTC / videoconferência.
  - `persistence/` — dados e modelo de informação.
  - `infrastructure/` — deploy, AWS, Cloudflare, CI/CD.

Cada camada referencia documentos já existentes em `docs/` para evitar duplicação de conteúdo.

