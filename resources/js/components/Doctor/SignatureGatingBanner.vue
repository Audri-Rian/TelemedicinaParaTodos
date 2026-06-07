<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { DoctorSignatureState } from '@/types/clinical-documents';
import { Link, usePage } from '@inertiajs/vue3';
import { PenLine, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });

const page = usePage();
const signature = computed(() => (page.props.auth as { signature?: DoctorSignatureState | null })?.signature ?? null);

const visible = computed(() => signature.value !== null && !signature.value.active);
const blocking = computed(() => visible.value && signature.value!.required);
</script>

<template>
    <p
        v-if="visible && compact"
        class="flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900"
    >
        <ShieldAlert class="size-3.5 shrink-0" />
        <span class="min-w-0 flex-1">
            {{ blocking ? 'Assinatura digital não integrada — emissão bloqueada.' : 'Assinatura digital não integrada.' }}
            <Link href="/settings/digital-signature" class="font-semibold underline underline-offset-2">Integrar</Link>
        </span>
    </p>
    <div
        v-else-if="visible"
        class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
        <ShieldAlert class="size-5 shrink-0" />
        <div class="min-w-0 flex-1">
            <p class="font-semibold">Assinatura digital não integrada</p>
            <p class="text-amber-800/90">
                {{
                    blocking
                        ? 'A emissão de documentos clínicos está bloqueada até você integrar sua assinatura digital (CFM Res. 2.314/2022).'
                        : 'Integre sua assinatura digital para que os documentos emitidos tenham validade legal (CFM Res. 2.314/2022).'
                }}
            </p>
        </div>
        <Button as-child type="button" size="sm" class="shrink-0 gap-1.5">
            <Link href="/settings/digital-signature">
                <PenLine class="size-4" />
                Integrar assinatura digital
            </Link>
        </Button>
    </div>
</template>
