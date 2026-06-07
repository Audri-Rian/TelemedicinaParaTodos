<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { SIGNATURE_STATUS_LABELS, type DoctorSignatureState } from '@/types/clinical-documents';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, FlaskConical, Loader2, PenLine, ShieldAlert, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    signatureStatus: DoctorSignatureState['status'];
    requireForIssuance: boolean;
    flashStatus?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Assinatura digital', href: '/settings/digital-signature' }];

const isActive = computed(() => props.signatureStatus === 'active');
const statusLabel = computed(() => SIGNATURE_STATUS_LABELS[props.signatureStatus] ?? props.signatureStatus);

const form = useForm({});

const activate = () => {
    form.post('/settings/digital-signature/activate', { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Assinatura digital" />
        <SettingsLayout>
            <div class="space-y-5">
                <div v-if="flashStatus" class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 p-3 text-sm text-teal-700">
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    {{ flashStatus }}
                </div>

                <section class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <p class="mb-4 text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Assinatura digital</p>

                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="flex items-center gap-3">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-full border shadow-xs"
                                :class="isActive ? 'border-teal-200 bg-teal-50 text-teal-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                            >
                                <ShieldCheck v-if="isActive" class="h-5 w-5" />
                                <ShieldAlert v-else class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">Status: {{ statusLabel }}</p>
                                <p class="text-xs text-slate-500">
                                    {{
                                        isActive
                                            ? 'Você está apto a emitir receitas, atestados e pedidos de exame.'
                                            : requireForIssuance
                                              ? 'A emissão de documentos clínicos está bloqueada até a integração da assinatura.'
                                              : 'A integração é recomendada para dar validade legal aos documentos emitidos.'
                                    }}
                                </p>
                            </div>
                        </div>

                        <Button v-if="!isActive" type="button" class="shrink-0 gap-1.5" :disabled="form.processing" @click="activate">
                            <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                            <PenLine v-else class="size-4" />
                            Integrar assinatura digital
                        </Button>
                    </div>

                    <div class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm text-slate-600">
                        <p>
                            A Resolução CFM nº 2.314/2022 exige que documentos clínicos emitidos em telemedicina (receitas, atestados e solicitações
                            de exame) sejam assinados digitalmente com certificado ICP-Brasil para terem validade legal.
                        </p>
                        <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-900">
                            <FlaskConical class="mt-0.5 h-4 w-4 shrink-0" />
                            <p class="text-xs leading-relaxed">
                                <strong>Integração simulada (homologação):</strong> nesta fase, o botão acima apenas habilita a emissão na plataforma.
                                Os documentos <strong>não possuem assinatura ICP-Brasil válida</strong> até a integração com o provedor de
                                certificação digital ser concluída.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
