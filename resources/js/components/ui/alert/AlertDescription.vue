<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {})

const alertDescriptionClasses = computed(() => 
  cn('text-xs [&_p]:leading-relaxed', props.class)
)
</script>

<template>
  <div :class="alertDescriptionClasses">
    <slot />
  </div>
</template>
