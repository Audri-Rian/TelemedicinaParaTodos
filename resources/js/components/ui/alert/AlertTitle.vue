<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {})

const alertTitleClasses = computed(() => 
  cn('mb-0.5 text-sm font-medium leading-none tracking-tight', props.class)
)
</script>

<template>
  <h5 :class="alertTitleClasses">
    <slot />
  </h5>
</template>
