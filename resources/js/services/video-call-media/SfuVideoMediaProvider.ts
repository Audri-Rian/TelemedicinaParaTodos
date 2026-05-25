// TODO: Implementar wrapper sobre useSfu.ts com contrato VideoMediaProvider.
// Esta implementação será completada em docs/specs/mediasoup-integration.md.
// O contrato (VideoMediaProvider) é idêntico — sem descarte de código ao integrar.

import type { VideoMediaProvider } from './VideoMediaProvider';

export function createSfuVideoMediaProvider(): VideoMediaProvider {
    throw new Error('SfuVideoMediaProvider não implementado. Use MEDIA_GATEWAY_PROVIDER=stub.');
}
