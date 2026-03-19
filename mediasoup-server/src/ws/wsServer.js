const { WebSocketServer } = require('ws');
const { config } = require('../config');
const { logger } = require('../logger');
const { validateRoomToken } = require('../auth/roomToken');
const { createRoom, cancelAutoClose, scheduleAutoCloseIfEmpty } = require('../rooms/rooms');
const { Peer } = require('../rooms/peer');
const { createWebRtcTransport } = require('../mediasoup/transports');

function safeSend(ws, obj) {
  try {
    ws.send(JSON.stringify(obj));
  } catch {
    // ignore
  }
}

function asError(err) {
  const msg = err && typeof err.message === 'string' ? err.message : String(err);
  return { message: msg };
}

function startHeartbeat(wss) {
  const interval = setInterval(() => {
    const now = Date.now();
    for (const ws of wss.clients) {
      if (ws.readyState !== ws.OPEN) continue;
      if (ws.__lastPongAt && now - ws.__lastPongAt > config.ws.heartbeatTimeoutMs) {
        try {
          ws.terminate();
        } catch {
          // ignore
        }
        continue;
      }
      safeSend(ws, { action: 'ping', ts: now });
    }
  }, config.ws.heartbeatIntervalMs);

  wss.on('close', () => clearInterval(interval));
}

async function startWsServer() {
  const wss = new WebSocketServer({ port: config.ws.port });
  logger.info({ wsPort: config.ws.port }, 'WS iniciado');

  startHeartbeat(wss);

  wss.on('connection', (ws) => {
    ws.__lastPongAt = Date.now();
    ws.__peer = null;
    ws.__room = null;

    ws.on('message', async (raw) => {
      let msg;
      try {
        msg = JSON.parse(String(raw));
      } catch {
        return safeSend(ws, { ok: false, error: { message: 'JSON inválido.' } });
      }

      // Heartbeat
      if (msg.action === 'pong') {
        ws.__lastPongAt = Date.now();
        return;
      }

      const reqId = msg.id ?? null;
      const reply = (payload) => safeSend(ws, { id: reqId, ...payload });

      try {
        // 1) Autenticação / join (exige token)
        if (msg.action === 'join') {
          const token = msg.token;
          const identity = validateRoomToken(token);
          const room = await createRoom(identity.roomId);
          cancelAutoClose(room);

          // Reconexão simples: se já existir peer deste userId na mesma room, fechar o anterior.
          for (const existingPeer of room.peers.values()) {
            if (existingPeer.userId === identity.userId) {
              try {
                existingPeer.close('reconnect');
              } catch {
                // ignore
              }
            }
          }

          const peerId = `${identity.userId}_${Date.now()}`;
          const peer = new Peer({
            id: peerId,
            userId: identity.userId,
            callId: identity.callId,
            role: identity.role,
            room,
            onClose: () => {
              scheduleAutoCloseIfEmpty(room, config.rooms.emptyGraceMs);
            }
          });
          room.peers.set(peer.id, peer);

          ws.__peer = peer;
          ws.__room = room;

          logger.info(
            { roomId: identity.roomId, callId: identity.callId, userId: identity.userId, peerId },
            'PEER_JOINED'
          );

          const participants = Array.from(room.peers.values()).map((p) => ({
            peerId: p.id,
            userId: p.userId
          }));

          reply({
            ok: true,
            data: {
              peerId,
              roomId: room.id,
              rtpCapabilities: room.router.rtpCapabilities,
              participantsCount: participants.length,
              participants
            }
          });

          // Notificar os peers já na sala que este usuário entrou.
          for (const otherWs of wss.clients) {
            if (otherWs.readyState !== otherWs.OPEN) continue;
            if (!otherWs.__peer || !otherWs.__room || otherWs.__room.id !== room.id) continue;
            if (otherWs === ws) continue;
            safeSend(otherWs, {
              action: 'peerJoined',
              data: { peerId, userId: identity.userId, participantsCount: participants.length, participants }
            });
          }

          // Enviar ao novo peer os producers já existentes na sala no próximo tick,
          // para o cliente receber primeiro a resposta do join e criar os transports.
          setImmediate(() => {
            if (ws.readyState !== ws.OPEN) return;
            let count = 0;
            for (const otherPeer of room.peers.values()) {
              if (otherPeer.id === peer.id) continue;
              for (const [producerId, producer] of otherPeer.producers) {
                safeSend(ws, {
                  action: 'newProducer',
                  data: { producerId, peerId: otherPeer.id, kind: producer.kind }
                });
                count++;
              }
            }
            if (count > 0) {
              logger.info(
                { roomId: room.id, toPeerId: peer.id, existingProducersCount: count },
                'EXISTING_PRODUCERS_SENT'
              );
            }
          });

          return;
        }

        // Exigir join para qualquer outra ação
        if (!ws.__peer || !ws.__room) {
          return reply({ ok: false, error: { message: 'Não autenticado. Envie join com token.' } });
        }

        const peer = ws.__peer;
        const room = ws.__room;

        if (msg.action === 'getRouterRtpCapabilities') {
          return reply({ ok: true, data: room.router.rtpCapabilities });
        }

        // 2) createWebRtcTransport (2 transports por peer: send/recv)
        if (msg.action === 'createWebRtcTransport') {
          const direction = msg.direction;
          if (direction !== 'send' && direction !== 'recv') {
            return reply({ ok: false, error: { message: 'direction inválido (send|recv).' } });
          }

          const transport = await createWebRtcTransport(room.router);
          transport.__direction = direction;

          transport.on('dtlsstatechange', (dtlsState) => {
            if (dtlsState === 'closed') {
              try {
                transport.close();
              } catch {
                // ignore
              }
            }
          });

          if (direction === 'send') peer.sendTransport = transport;
          else peer.recvTransport = transport;

          return reply({
            ok: true,
            data: {
              id: transport.id,
              iceParameters: transport.iceParameters,
              iceCandidates: transport.iceCandidates,
              dtlsParameters: transport.dtlsParameters
            }
          });
        }

        // 3) connectWebRtcTransport
        if (msg.action === 'connectWebRtcTransport') {
          const transportId = msg.transportId;
          const dtlsParameters = msg.dtlsParameters;
          const transport =
            (peer.sendTransport && peer.sendTransport.id === transportId ? peer.sendTransport : null) ||
            (peer.recvTransport && peer.recvTransport.id === transportId ? peer.recvTransport : null);

          if (!transport) return reply({ ok: false, error: { message: 'Transport não encontrado.' } });
          await transport.connect({ dtlsParameters });
          return reply({ ok: true, data: { connected: true } });
        }

        // 4) produce (apenas sendTransport)
        if (msg.action === 'produce') {
          const transport = peer.sendTransport;
          if (!transport) return reply({ ok: false, error: { message: 'sendTransport não existe.' } });

          const { kind, rtpParameters, appData } = msg;
          const producer = await transport.produce({ kind, rtpParameters, appData });
          peer.producers.set(producer.id, producer);

          producer.on('transportclose', () => {
            peer.producers.delete(producer.id);
          });

          logger.info(
            { roomId: room.id, peerId: peer.id, producerId: producer.id, kind },
            'PRODUCER_CREATED'
          );

          // Notificar outros peers sobre novo producer
          for (const otherWs of wss.clients) {
            if (otherWs.readyState !== otherWs.OPEN) continue;
            if (!otherWs.__peer || !otherWs.__room) continue;
            if (otherWs.__room.id !== room.id) continue;
            if (otherWs === ws) continue;
            safeSend(otherWs, {
              action: 'newProducer',
              data: { producerId: producer.id, peerId: peer.id, kind }
            });
          }

          return reply({ ok: true, data: { id: producer.id } });
        }

        // 4b) pauseProducer / resumeProducer
        if (msg.action === 'pauseProducer') {
          const producer = peer.producers.get(msg.producerId);
          if (!producer) return reply({ ok: false, error: { message: 'Producer não encontrado.' } });
          await producer.pause();
          return reply({ ok: true, data: { paused: true } });
        }

        if (msg.action === 'resumeProducer') {
          const producer = peer.producers.get(msg.producerId);
          if (!producer) return reply({ ok: false, error: { message: 'Producer não encontrado.' } });
          await producer.resume();
          return reply({ ok: true, data: { resumed: true } });
        }

        // 4c) closeProducer (quando o cliente desliga câmera/mic sem sair da sala)
        if (msg.action === 'closeProducer') {
          const { producerId } = msg;
          const producer = peer.producers.get(producerId);
          if (!producer) return reply({ ok: false, error: { message: 'Producer não encontrado.' } });
          try {
            producer.close();
          } catch {
            // ignore
          }
          peer.producers.delete(producerId);
          logger.info({ roomId: room.id, peerId: peer.id, producerId }, 'PRODUCER_CLOSED_BY_CLIENT');
          return reply({ ok: true, data: { closed: true } });
        }

        // 5) consume (apenas recvTransport)
        if (msg.action === 'consume') {
          const transport = peer.recvTransport;
          if (!transport) return reply({ ok: false, error: { message: 'recvTransport não existe.' } });

          const { producerId, rtpCapabilities } = msg;

          if (!room.router.canConsume({ producerId, rtpCapabilities })) {
            return reply({ ok: false, error: { message: 'Não é possível consumir este producer.' } });
          }

          const consumer = await transport.consume({
            producerId,
            rtpCapabilities,
            paused: true
          });

          peer.consumers.set(consumer.id, consumer);

          consumer.on('transportclose', () => {
            peer.consumers.delete(consumer.id);
          });
          consumer.on('producerclose', () => {
            peer.consumers.delete(consumer.id);
            reply({ ok: true, data: { producerClosed: true, consumerId: consumer.id } });
          });

          return reply({
            ok: true,
            data: {
              id: consumer.id,
              producerId,
              kind: consumer.kind,
              rtpParameters: consumer.rtpParameters,
              type: consumer.type
            }
          });
        }

        if (msg.action === 'pauseConsumer') {
          const { consumerId } = msg;
          const consumer = peer.consumers.get(consumerId);
          if (!consumer) return reply({ ok: false, error: { message: 'Consumer não encontrado.' } });
          await consumer.pause();
          return reply({ ok: true, data: { paused: true } });
        }

        if (msg.action === 'resumeConsumer') {
          const { consumerId } = msg;
          const consumer = peer.consumers.get(consumerId);
          if (!consumer) return reply({ ok: false, error: { message: 'Consumer não encontrado.' } });
          await consumer.resume();
          return reply({ ok: true, data: { resumed: true } });
        }

        if (msg.action === 'requestKeyFrame') {
          const { consumerId } = msg;
          const consumer = peer.consumers.get(consumerId);
          if (!consumer) return reply({ ok: false, error: { message: 'Consumer não encontrado.' } });
          if (consumer.kind !== 'video') return reply({ ok: false, error: { message: 'Apenas consumers de vídeo.' } });
          try {
            await consumer.requestKeyFrame();
            return reply({ ok: true, data: { requested: true } });
          } catch (err) {
            return reply({ ok: false, error: asError(err) });
          }
        }

        if (msg.action === 'getConsumerStats') {
          const { consumerId } = msg;
          const consumer = peer.consumers.get(consumerId);
          if (!consumer) return reply({ ok: false, error: { message: 'Consumer não encontrado.' } });
          try {
            const stats = await consumer.getStats();
            return reply({ ok: true, data: { kind: consumer.kind, stats } });
          } catch (err) {
            return reply({ ok: false, error: asError(err) });
          }
        }

        if (msg.action === 'leave') {
          try {
            peer.close('leave');
          } catch (e) {
            logger.warn({ err: e }, 'Erro ao fechar peer no leave');
          }
          room.peers.delete(peer.id);
          ws.__peer = null;
          ws.__room = null;

          // Notificar os outros peers
          const remaining = Array.from(room.peers.values()).map(p => ({ peerId: p.id, userId: p.userId }));
          for (const otherWs of wss.clients) {
            if (otherWs.readyState !== otherWs.OPEN) continue;
            if (!otherWs.__peer || !otherWs.__room) continue;
            if (otherWs.__room.id !== room.id) continue;
            safeSend(otherWs, {
              action: 'peerLeft',
              data: {
                peerId: peer.id,
                userId: peer.userId,
                participantsCount: remaining.length,
                participants: remaining,
              }
            });
          }

          return reply({ ok: true, data: { left: true } });
        }

        return reply({ ok: false, error: { message: 'Ação desconhecida.' } });
      } catch (err) {
        logger.warn({ err, action: msg.action }, 'Erro ao processar mensagem WS');
        return safeSend(ws, { id: reqId, ok: false, error: asError(err) });
      }
    });

    ws.on('close', () => {
      const peer = ws.__peer;
      const room = ws.__room;
      if (peer && room) {
        try {
          peer.close('ws_close');
        } catch (e) {
          logger.warn({ err: e }, 'Erro ao fechar peer no close');
        }
        room.peers.delete(peer.id);

        // Notificar os outros peers que este saiu
        const remaining = Array.from(room.peers.values()).map(p => ({ peerId: p.id, userId: p.userId }));
        for (const otherWs of wss.clients) {
          if (otherWs.readyState !== otherWs.OPEN) continue;
          if (!otherWs.__peer || !otherWs.__room) continue;
          if (otherWs.__room.id !== room.id) continue;
          safeSend(otherWs, {
            action: 'peerLeft',
            data: {
              peerId: peer.id,
              userId: peer.userId,
              participantsCount: remaining.length,
              participants: remaining,
            }
          });
        }
      }
    });
  });

  return wss;
}

module.exports = { startWsServer };

