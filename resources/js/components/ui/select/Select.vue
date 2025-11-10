<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes['class']
  placeholder?: string
  disabled?: boolean
  readonly?: boolean
  name?: string
  id?: string
}>(), {
  placeholder: 'Selecione...',
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
  <div class="relative">
    <select
      v-model="modelValue"
      data-slot="select"
      :id="props.id"
      :name="props.name"
      :placeholder="props.placeholder"
      :disabled="props.disabled"
      :readonly="props.readonly"
      :class="cn(
        'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 pr-8 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm appearance-none',
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
        props.class,
      )"
    >
      <slot />
    </select>
    <ChevronDown
      class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 opacity-50"
    />
  </div>
</template>

