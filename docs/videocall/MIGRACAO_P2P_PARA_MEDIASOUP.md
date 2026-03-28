# Migração: Remoção do P2P e Adoção do MediaSoup (SFU)

## Sobre esta migração

Plano de **remoção completa da estrutura P2P** (PeerJS) de videochamadas e **adoção do MediaSoup** como SFU, com **WebSocket próprio** para sinalização de mídia e recriação do backend e frontend de videochamada.

**Decisões:**
- Remover por completo a estrutura P2P (PeerJS e tudo que depende de `peerId`).
- Usar **WebSocket próprio** do servidor MediaSoup para sinalização de mídia (não Reverb como proxy).
- Recriar o **frontend** de videochamada (novos composables, sem reaproveitar lógica P2P).
- Recriar do zero o **backend** de videochamada; manter regras de negócio, políticas e integração com Appointments.

A documentação da migração está dividida em **dois documentos**:

---

## 1. O que remover e o que manter

**[REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md)** — Inventário apenas: o que **remover** (controller, eventos, rotas, PeerJS, composable atual, etc.) e o que **manter** (regras de negócio, Appointments, políticas, canais de outras features, Reverb). Inclui itens a **avaliar** (VideoCallPolicy, models, jobs, canal `video-call.{id}`).

Use este documento para a fase de **limpeza** e para não quebrar o que não é videochamada P2P.

---

## 2. Nova implementação do SFU

**[IMPLEMENTACAO_SFU_MEDIASOUP.md](IMPLEMENTACAO_SFU_MEDIASOUP.md)** — Nova implementação com MediaSoup: arquitetura, fluxo da chamada, o que construir no **backend** (Laravel), no **servidor MediaSoup** (Node.js) e no **frontend** (Vue), com checklist de implementação.

Use este documento para **implementar** o SFU (API, eventos, composables, servidor de mídia).

---

## Ordem sugerida

1. Ler [REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md) para ter claro o que sai e o que fica.
2. Seguir [IMPLEMENTACAO_SFU_MEDIASOUP.md](IMPLEMENTACAO_SFU_MEDIASOUP.md) para desenhar e implementar a nova solução.
3. Fazer a remoção (itens do doc 1) quando a nova implementação estiver estável, para evitar período sem videochamada funcional.
