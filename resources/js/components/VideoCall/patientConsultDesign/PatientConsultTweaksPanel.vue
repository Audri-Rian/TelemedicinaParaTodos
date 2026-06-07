<script setup lang="ts">
defineProps<{
    open: boolean;
    panelWidth: number;
    showCaptions: boolean;
    stageView: 'doctor-main' | 'patient-main';
    accent: string;
}>();

const emit = defineEmits<{
    close: [];
    'update:panelWidth': [value: number];
    'update:showCaptions': [value: boolean];
    'update:stageView': [value: 'doctor-main' | 'patient-main'];
    'update:accent': [value: string];
}>();

const ACCENT_OPTIONS = ['#0f766e', '#1d4ed8', '#7c3aed', '#0e7490'];
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="twk-panel">
            <div class="twk-hd">
                <b>Tweaks</b>
                <button type="button" class="twk-x" @click="emit('close')">✕</button>
            </div>
            <div class="twk-body">
                <div class="twk-sect">Layout</div>
                <div class="twk-row">
                    <div class="twk-lbl">
                        <span>Largura do painel</span><span class="twk-val">{{ panelWidth }}px</span>
                    </div>
                    <input
                        type="range"
                        class="twk-slider"
                        min="340"
                        max="520"
                        step="10"
                        :value="panelWidth"
                        @input="emit('update:panelWidth', Number(($event.target as HTMLInputElement).value))"
                    />
                </div>
                <div class="twk-row">
                    <div class="twk-lbl"><span>Vídeo principal</span></div>
                    <div class="twk-seg">
                        <button
                            type="button"
                            :style="{ fontWeight: stageView === 'doctor-main' ? '700' : '500' }"
                            @click="emit('update:stageView', 'doctor-main')"
                        >
                            Médico
                        </button>
                        <button
                            type="button"
                            :style="{ fontWeight: stageView === 'patient-main' ? '700' : '500' }"
                            @click="emit('update:stageView', 'patient-main')"
                        >
                            Você
                        </button>
                    </div>
                </div>

                <div class="twk-sect">Estado</div>
                <div class="twk-row twk-row-h">
                    <div class="twk-lbl"><span>Legendas ao vivo</span></div>
                    <button type="button" class="twk-toggle" :data-on="showCaptions ? '1' : '0'" @click="emit('update:showCaptions', !showCaptions)">
                        <i />
                    </button>
                </div>
                <div class="twk-sect">Cor de destaque</div>
                <div class="twk-chips">
                    <button
                        v-for="c in ACCENT_OPTIONS"
                        :key="c"
                        type="button"
                        class="twk-chip"
                        :data-on="accent === c ? '1' : '0'"
                        :style="{ background: c }"
                        @click="emit('update:accent', c)"
                    />
                </div>
            </div>
        </div>
    </Teleport>
</template>
