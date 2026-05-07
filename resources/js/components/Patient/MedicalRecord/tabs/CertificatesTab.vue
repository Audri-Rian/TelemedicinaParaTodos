<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { MedicalCertificate } from '@/types/medical-records';
import { Link } from '@inertiajs/vue3';
import { FileCheck2 } from 'lucide-vue-next';

defineProps<{
    medicalCertificates: MedicalCertificate[];
    emptyText: string;
}>();

const { formatDate, formatStatus, isSafeUrl } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="certificate in medicalCertificates" :key="certificate.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">{{ formatStatus(certificate.type) }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ certificate.doctor.name }} · {{ formatDate(certificate.created_at) }}</p>
            <p class="mt-3 text-sm font-medium text-gray-600">{{ certificate.reason }}</p>
            <p class="mt-2 text-xs font-black text-gray-500">Código: {{ certificate.verification_code }}</p>
            <Link
                v-if="isSafeUrl(certificate.pdf_url)"
                :href="certificate.pdf_url!"
                target="_blank"
                class="mt-4 inline-flex items-center font-black text-[#0f6e78] hover:underline"
            >
                <FileCheck2 class="mr-1 h-4 w-4" />
                Ver PDF
            </Link>
        </article>
        <EmptyBlock v-if="medicalCertificates.length === 0" :text="emptyText" />
    </div>
</template>
