<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

interface Props {
    reverb: {
        key: string;
        host: string;
        port: number;
        scheme: string;
    } | null;
}

defineProps<Props>();
</script>

<template>
    <Head title="Teste de Videoconferência" />
    <div class="min-h-screen bg-gray-50 p-6">
        <Card class="max-w-2xl mx-auto">
            <CardHeader>
                <CardTitle>Teste de Videoconferência</CardTitle>
                <CardDescription>
                    Página de teste de videochamada. A estrutura P2P (PeerJS) foi removida.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-gray-600">
                    A videoconferência está em migração para <strong>MediaSoup (SFU)</strong>. Quando a nova implementação estiver disponível, esta página poderá ser usada para testar o fluxo.
                </p>
                <p class="text-sm text-gray-500">
                    Ver documentação: <code class="bg-gray-100 px-1 rounded">docs/videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md</code>
                </p>
            </CardContent>
        </Card>
    </div>
</template>
