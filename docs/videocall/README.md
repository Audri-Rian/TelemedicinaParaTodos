# Documentação de Videochamadas

Este diretório concentra documentos de apoio do módulo de **videochamadas**. A implementação vigente está em **MediaSoup/SFU**; os documentos de P2P são históricos de migração.

## Documentos

| Documento                                                            | Descrição                                                                                                                                               |
| -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **[TESTE_SFU_MEDIASOUP.md](TESTE_SFU_MEDIASOUP.md)**                 | Guia de teste integrado/standalone do SFU.                                                                                                              |
| **[MIGRACAO_P2P_PARA_MEDIASOUP.md](MIGRACAO_P2P_PARA_MEDIASOUP.md)** | Histórico da migração: remoção do P2P e adoção do MediaSoup.                                                                                            |
| **[REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md)**               | Inventário histórico do que saiu do modelo P2P.                                                                                                         |
| **[IMPLEMENTACAO_SFU_MEDIASOUP.md](IMPLEMENTACAO_SFU_MEDIASOUP.md)** | Plano original da implementação SFU; a implementação corrente e mais fiel ao código está em `../layers/signaling/videocall/VideoCallImplementation.md`. |

## Contexto

- **Estado atual:** MediaSoup como SFU; sinalização de mídia por WebSocket próprio do SFU; Laravel/Reverb apenas para ciclo de vida da chamada e regras de negócio.
- **Modelo atual:** `calls` para negócio, `rooms` para sala no SFU, JWT curto emitido pelo Laravel.

## Outros documentos relacionados

- [Camada de Mídia](../layers/media/README.md) — responsabilidades de transporte de mídia (WebRTC/SFU).
- [Camada de Sinalização — Videochamada](../layers/signaling/videocall/README.md) — implementação atual SFU e tarefas.
- [Visão Geral do Projeto](../index/VisaoGeral.md) — índice geral da documentação.
