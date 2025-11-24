<script setup lang="ts">
import PatientMedicalRecord from '@/pages/Patient/MedicalRecord.vue';

type MedicalRecordProps = InstanceType<typeof PatientMedicalRecord>['$props'];

defineProps<MedicalRecordProps>();
</script>

<template>
    <PatientMedicalRecord v-bind="$props" />
</template>



