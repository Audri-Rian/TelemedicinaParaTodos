<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'

const props = withDefaults(defineProps<{
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes['class']
  placeholder?: string
  disabled?: boolean
  readonly?: boolean
  rows?: number
}>(), {
  rows: 4,
})

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :placeholder="props.placeholder"
    :disabled="props.disabled"
    :readonly="props.readonly"
    :rows="props.rows"
    :class="cn(
      'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input flex min-h-[60px] w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
      'resize-y',
      props.class,
    )"
  />
</template>

