<script setup lang="ts">
// @ts-expect-error - route helper from Ziggy
declare const route: (name: string, params?: unknown) => string;

import type { ConsultationPatient, RecentConsultation } from '@/types/consultation-detail';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    patient: ConsultationPatient;
    recentConsultations: RecentConsultation[];
    collapsed: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();

const initials = computed(() => {
    const parts = props.patient.name.trim().split(/\s+/);
    const first = parts[0]?.[0] ?? '';
    const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '';
    return (first + last).toUpperCase();
});

const genderLabel = computed(() => {
    const g = (props.patient.gender ?? '').toLowerCase();
    if (g === 'male' || g === 'masculino' || g === 'm') return 'Masculino';
    if (g === 'female' || g === 'feminino' || g === 'f') return 'Feminino';
    return props.patient.gender ?? '';
});

const formatPhone = (phone: string) => {
    const digits = phone.replace(/\D/g, '');
    if (digits.length === 11) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }
    if (digits.length === 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }
    return phone;
};
</script>

<template>
    <!-- Collapsed state: icon strip -->
    <aside v-if="collapsed" class="cp-sb-collapsed" aria-label="Prontuário resumido (recolhido)">
        <button class="cp-sb-icon-btn" title="Expandir" @click="emit('toggle')">
            <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
            </svg>
        </button>
        <button v-if="patient.allergies.length > 0" class="cp-sb-icon-btn has-alert" title="Alergias">
            <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
        </button>
        <button class="cp-sb-icon-btn" title="Medicações">
            <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" />
                <path d="m8.5 8.5 7 7" />
            </svg>
        </button>
        <button class="cp-sb-icon-btn" title="Histórico">
            <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M3 3v5h5" />
                <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
                <polyline points="12 7 12 12 16 14" />
            </svg>
        </button>
        <button class="cp-sb-icon-btn" title="Contato">
            <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17z"
                />
            </svg>
        </button>
    </aside>

    <!-- Expanded sidebar -->
    <aside v-else class="cp-sb" aria-label="Prontuário resumido do paciente">
        <!-- Tab header -->
        <div class="cp-sb-tab">
            <span>Prontuário resumido</span>
            <button class="cp-sb-tab-btn" title="Recolher" @click="emit('toggle')">
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M9 3v18" />
                </svg>
            </button>
        </div>

        <!-- Patient head -->
        <div class="cp-sb-head">
            <div class="cp-sb-avatar" aria-hidden="true">{{ initials }}</div>
            <div class="cp-sb-head-info">
                <h3 class="cp-sb-name">{{ patient.name }}</h3>
                <div class="cp-sb-sub">{{ patient.age }} anos · {{ genderLabel }}</div>
                <span v-if="patient.insurance_provider" class="cp-sb-pill">
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    {{ patient.insurance_provider }}
                </span>
            </div>
        </div>

        <!-- Allergies -->
        <section v-if="patient.allergies.length > 0" class="cp-sb-section">
            <p class="cp-sb-label">
                <span>
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#DC2626"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 5px"
                    >
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    Alergias
                </span>
                <span class="count">{{ patient.allergies.length }}</span>
            </p>
            <div class="cp-allergy-list">
                <div v-for="allergy in patient.allergies" :key="allergy" class="cp-allergy">
                    {{ allergy }}
                </div>
            </div>
        </section>

        <!-- Medications -->
        <section v-if="patient.current_medications" class="cp-sb-section">
            <p class="cp-sb-label">
                <span>
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 5px"
                    >
                        <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" />
                        <path d="m8.5 8.5 7 7" />
                    </svg>
                    Medicações em uso
                </span>
            </p>
            <p class="cp-sb-med-text">{{ patient.current_medications }}</p>
        </section>

        <!-- Vitals grid -->
        <section v-if="patient.blood_type || patient.bmi || patient.height || patient.weight" class="cp-sb-section">
            <p class="cp-sb-label">
                <span>
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 5px"
                    >
                        <line x1="12" y1="20" x2="12" y2="10" />
                        <line x1="18" y1="20" x2="18" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="16" />
                    </svg>
                    Dados clínicos
                </span>
            </p>
            <div class="cp-vitals-grid">
                <div v-if="patient.blood_type" class="cp-vital">
                    <div class="vl">Tipo sanguíneo</div>
                    <div class="vv blood">{{ patient.blood_type }}</div>
                </div>
                <div v-if="patient.bmi" class="cp-vital">
                    <div class="vl">IMC</div>
                    <div class="vv">{{ patient.bmi.toFixed(1) }}</div>
                </div>
                <div v-if="patient.height" class="cp-vital">
                    <div class="vl">Altura</div>
                    <div class="vv">{{ patient.height }}<span class="u"> cm</span></div>
                </div>
                <div v-if="patient.weight" class="cp-vital">
                    <div class="vl">Peso</div>
                    <div class="vv">{{ patient.weight }}<span class="u"> kg</span></div>
                </div>
            </div>
        </section>

        <!-- Contact -->
        <section v-if="patient.phone || patient.email || patient.emergency_contact" class="cp-sb-section">
            <p class="cp-sb-label">
                <span>
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 5px"
                    >
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17z"
                        />
                    </svg>
                    Contato
                </span>
            </p>
            <div class="cp-contact">
                <a v-if="patient.phone" :href="`tel:${patient.phone.replace(/\D/g, '')}`" class="cp-contact-row">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17z"
                        />
                    </svg>
                    {{ formatPhone(patient.phone) }}
                </a>
                <a v-if="patient.email" :href="`mailto:${patient.email}`" class="cp-contact-row">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    {{ patient.email }}
                </a>
                <div v-if="patient.emergency_contact" class="cp-emergency">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 6px"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    Contato de emergência
                    <b
                        >{{ patient.emergency_contact
                        }}<template v-if="patient.emergency_phone"> — {{ formatPhone(patient.emergency_phone) }}</template></b
                    >
                </div>
            </div>
        </section>

        <!-- History -->
        <section v-if="recentConsultations.length > 0" class="cp-sb-section">
            <p class="cp-sb-label">
                <span>
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="display: inline; vertical-align: -1px; margin-right: 5px"
                    >
                        <path d="M3 3v5h5" />
                        <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
                        <polyline points="12 7 12 12 16 14" />
                    </svg>
                    Últimas consultas
                </span>
            </p>
            <div class="cp-history-list">
                <button
                    v-for="c in recentConsultations"
                    :key="c.id"
                    class="cp-history-item"
                    type="button"
                    @click="router.get(route('doctor.consultations.detail', c.id))"
                >
                    <span class="hdate">{{ c.date }}</span>
                    <div class="hbody">
                        <div class="hdiag">{{ c.diagnosis ?? 'Consulta' }}</div>
                        <div class="hcid">
                            <template v-if="c.cid10">{{ c.cid10 }}</template>
                            <template v-if="c.cid10 && c.doctor"> · </template>
                            <template v-if="c.doctor">{{ c.doctor }}</template>
                        </div>
                    </div>
                </button>
            </div>
        </section>

        <!-- Link to full record -->
        <a :href="`/doctor/patients/${patient.id}/medical-record`" class="cp-sb-link">
            <span>Ver prontuário completo</span>
            <svg
                width="13"
                height="13"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
            </svg>
        </a>
    </aside>
</template>

<style scoped>
.cp-sb,
.cp-sb-collapsed {
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

/* ── Expanded ── */
.cp-sb {
    position: sticky;
    top: 100px;
    height: fit-content;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    background: var(--cp-surface, #fff);
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: var(--cp-r-lg, 14px);
    box-shadow: var(--cp-shadow-card, 0 1px 2px rgba(15, 41, 39, 0.05));
    scrollbar-width: thin;
}
.cp-sb::-webkit-scrollbar {
    width: 5px;
}
.cp-sb::-webkit-scrollbar-thumb {
    background: var(--cp-ink-200, #d7dfde);
    border-radius: 4px;
}

.cp-sb-tab {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--cp-line-2, #eef2f1);
    font-size: 11px;
    color: var(--cp-ink-500, #5a726f);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 600;
}
.cp-sb-tab-btn {
    appearance: none;
    background: transparent;
    border: 0;
    width: 22px;
    height: 22px;
    border-radius: 5px;
    color: var(--cp-ink-500, #5a726f);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.cp-sb-tab-btn:hover {
    background: var(--cp-surface-2, #fafbfb);
    color: var(--cp-ink-800, #14302e);
}

.cp-sb-head {
    padding: 16px 18px 14px;
    border-bottom: 1px solid var(--cp-line-2, #eef2f1);
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.cp-sb-avatar {
    width: 52px;
    height: 52px;
    border-radius: 13px;
    flex-shrink: 0;
    background: linear-gradient(135deg, #2a6358 0%, #14b8a6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 19px;
    letter-spacing: -0.02em;
    box-shadow: 0 4px 12px -4px rgba(13, 148, 136, 0.4);
}

.cp-sb-head-info {
    flex: 1;
    min-width: 0;
}

.cp-sb-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--cp-ink-900, #0a1f1e);
    letter-spacing: -0.015em;
    margin: 0 0 2px;
    line-height: 1.25;
}

.cp-sb-sub {
    font-size: 12.5px;
    color: var(--cp-ink-500, #5a726f);
}

.cp-sb-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 2px 8px;
    background: var(--cp-teal-50, #ecfdf8);
    border: 1px solid var(--cp-teal-100, #ccfbf1);
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: var(--cp-teal-800, #0b5953);
}

/* ── Section ── */
.cp-sb-section {
    padding: 14px 18px;
    border-bottom: 1px solid var(--cp-line-2, #eef2f1);
}
.cp-sb-section:last-of-type {
    border-bottom: 0;
}

.cp-sb-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--cp-ink-400, #8fa2a0);
    margin: 0 0 9px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cp-sb-label .count {
    background: var(--cp-ink-200, #d7dfde);
    color: var(--cp-ink-700, #1f3a38);
    padding: 1px 6px;
    border-radius: 999px;
    font-size: 10px;
    letter-spacing: 0;
}

/* ── Allergies ── */
.cp-allergy-list {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.cp-allergy {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    font-size: 12.5px;
    color: #b91c1c;
    font-weight: 600;
}

/* ── Medications ── */
.cp-sb-med-text {
    font-size: 12.5px;
    color: var(--cp-ink-700, #1f3a38);
    line-height: 1.55;
    margin: 0;
    white-space: pre-wrap;
}

/* ── Vitals ── */
.cp-vitals-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
    background: var(--cp-line-2, #eef2f1);
    border: 1px solid var(--cp-line-2, #eef2f1);
    border-radius: 8px;
    overflow: hidden;
}
.cp-vital {
    background: var(--cp-surface, #fff);
    padding: 8px 10px;
}
.vl {
    font-size: 10px;
    color: var(--cp-ink-400, #8fa2a0);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}
.vv {
    font-size: 14px;
    color: var(--cp-ink-900, #0a1f1e);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    margin-top: 1px;
    letter-spacing: -0.01em;
}
.vv .u {
    font-size: 10.5px;
    color: var(--cp-ink-400, #8fa2a0);
    font-weight: 500;
    margin-left: 2px;
}
.vv.blood {
    color: #dc2626;
    font-family: var(--cp-font-mono, monospace);
}

/* ── Contact ── */
.cp-contact {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.cp-contact-row {
    font-size: 12.5px;
    color: var(--cp-ink-700, #1f3a38);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 3px 0;
}
.cp-contact-row:hover {
    color: var(--cp-teal-700, #0f766e);
}

.cp-emergency {
    margin-top: 6px;
    padding: 8px 10px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.5;
}
.cp-emergency b {
    display: block;
    font-weight: 700;
    margin-top: 2px;
}

/* ── History ── */
.cp-history-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.cp-history-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 7px 8px;
    margin: 0 -8px;
    border-radius: 8px;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
    width: calc(100% + 16px);
    transition: background 100ms;
    font-family: var(--cp-font-sans, sans-serif);
}
.cp-history-item:hover {
    background: var(--cp-surface-2, #fafbfb);
}
.hdate {
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
    font-variant-numeric: tabular-nums;
    width: 54px;
    flex-shrink: 0;
    padding-top: 1px;
}
.hbody {
    flex: 1;
    min-width: 0;
}
.hdiag {
    font-size: 12.5px;
    color: var(--cp-ink-800, #14302e);
    font-weight: 500;
    line-height: 1.35;
}
.hcid {
    font-family: var(--cp-font-mono, monospace);
    font-size: 10.5px;
    color: var(--cp-teal-700, #0f766e);
    margin-top: 2px;
}

/* ── Footer link ── */
.cp-sb-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    font-size: 13px;
    color: var(--cp-ink-700, #1f3a38);
    font-weight: 600;
    text-decoration: none;
    border-top: 1px solid var(--cp-line-2, #eef2f1);
    transition:
        color 120ms,
        background 120ms;
}
.cp-sb-link:hover {
    color: var(--cp-teal-700, #0f766e);
    background: var(--cp-teal-50, #ecfdf8);
}

/* ── Collapsed ── */
.cp-sb-collapsed {
    position: sticky;
    top: 100px;
    background: var(--cp-surface, #fff);
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: var(--cp-r-lg, 14px);
    padding: 10px 6px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}

.cp-sb-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 0;
    background: transparent;
    color: var(--cp-ink-600, #355551);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
}
.cp-sb-icon-btn:hover {
    background: var(--cp-surface-2, #fafbfb);
    color: var(--cp-ink-900, #0a1f1e);
}
.cp-sb-icon-btn.has-alert {
    color: #dc2626;
}
.cp-sb-icon-btn.has-alert::after {
    content: '';
    position: absolute;
    top: 8px;
    right: 8px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #dc2626;
}
</style>
