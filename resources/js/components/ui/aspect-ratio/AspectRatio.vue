<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  ratio?: number
  class?: HTMLAttributes['class']
}>()

const aspectRatio = computed(() => {
  if (!props.ratio) return 'aspect-square'
  
  const ratioMap: Record<string, string> = {
    '1/1': 'aspect-square',
    '4/3': 'aspect-[4/3]',
    '3/2': 'aspect-[3/2]',
    '16/9': 'aspect-video',
    '21/9': 'aspect-[21/9]',
  }
  
  const ratioKey = `${Math.round(props.ratio * 1000) / 1000}`
  return ratioMap[ratioKey] || `aspect-[${props.ratio}]`
})
</script>

<template>
  <div
    :class="cn('relative w-full', aspectRatio, props.class)"
  >
    <slot />
  </div>
</template>
