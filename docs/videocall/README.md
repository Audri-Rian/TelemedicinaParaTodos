# Documentação de Videochamadas

Este diretório concentra a documentação do módulo de **videochamadas**, incluindo o plano de migração de P2P para MediaSoup.

## Documentos

| Documento | Descrição |
|-----------|-----------|
| **[MIGRACAO_P2P_PARA_MEDIASOUP.md](MIGRACAO_P2P_PARA_MEDIASOUP.md)** | Índice da migração: remoção do P2P e adoção do MediaSoup (SFU); aponta para os dois documentos abaixo. |
| **[REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md)** | Apenas inventário: o que **remover** e o que **manter** ao sair do P2P (backend, frontend, avaliar). |
| **[IMPLEMENTACAO_SFU_MEDIASOUP.md](IMPLEMENTACAO_SFU_MEDIASOUP.md)** | Nova implementação do SFU: arquitetura, fluxo, o que construir (Laravel, MediaSoup Node, Vue) e checklist. |

## Contexto

- **Estado atual (antes da migração):** Videochamada 1:1 via PeerJS (P2P) e sinalização via Laravel Reverb (`RequestVideoCall`, `RequestVideoCallStatus` com `peerId`).
- **Estado alvo:** MediaSoup como SFU; sinalização de mídia por WebSocket próprio do servidor MediaSoup; Laravel/Reverb apenas para ciclo de vida da chamada (solicitar, aceitar, rejeitar, encerrar) e regras de negócio (appointment, políticas). Backend e frontend de videochamada recriados.

## Outros documentos relacionados

- [Camada de Mídia](../layers/media/README.md) — responsabilidades de transporte de mídia (WebRTC/SFU).
- [Camada de Sinalização — Videochamada](../layers/signaling/videocall/README.md) — implementação atual (P2P) e tarefas.
- [Visão Geral do Projeto](../index/VisaoGeral.md) — índice geral da documentação.
