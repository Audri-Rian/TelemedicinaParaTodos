<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Input } from '@/components/ui/input'
import { useVModel } from '@vueuse/core'

const props = defineProps<{
  modelValue?: string | number
  defaultValue?: string | number
  class?: HTMLAttributes['class']
  placeholder?: string
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <Input
    v-model="modelValue"
    data-slot="sidebar-input"
    data-sidebar="input"
    :placeholder="props.placeholder"
    :class="cn(
      'bg-background h-8 w-full shadow-none',
      props.class,
    )"
  >
    <slot />
  </Input>
</template>
