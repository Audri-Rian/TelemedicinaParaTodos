# 📹 Videochamadas (SFU)

Módulo de videochamada com arquitetura **SFU (Selective Forwarding Unit)**. Ciclo de vida no Laravel + Reverb; mídia em servidor SFU (ex.: MediaSoup) com acesso via token JWT.

## Documentação do módulo

### Back-end (Laravel)

- **[Back-end SFU — Estrutura e funcionalidades](BackendSFU.md)** — Como o SFU está implementado no back-end: modelos Call e Room, CallManagerService, Media Gateway, eventos, migrations e fluxo. Explicação por arquivo, sem código desnecessário.

### Implementação e referências

- [IMPLEMENTACAO_SFU_MEDIASOUP.md](../../videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md) — Especificação da nova implementação (arquitetura, segurança, checklist).
- [REMOVER_E_MANTER_P2P.md](../../videocall/REMOVER_E_MANTER_P2P.md) — O que foi removido do modelo P2P.

### Documentação por camada (sinalização)

- [README (camada de sinalização)](../../layers/signaling/videocall/README.md)
- [VideoCallImplementation.md](../../layers/signaling/videocall/VideoCallImplementation.md)
- [VideoCallTasks.md](../../layers/signaling/videocall/VideoCallTasks.md)

Ver também: [docs/layers/README.md](../../layers/README.md) — visão geral das camadas.
