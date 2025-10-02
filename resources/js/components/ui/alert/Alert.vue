<script setup lang="ts">
import { computed } from 'vue'
import { cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const alertVariants = cva(
  'relative w-full rounded-md border p-2 [&>svg~*]:pl-5 [&>svg+div]:translate-y-[-1px] [&>svg]:absolute [&>svg]:left-2 [&>svg]:top-2 [&>svg]:text-foreground',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground',
        destructive:
          'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
)

interface Props {
  variant?: 'default' | 'destructive'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const alertClasses = computed(() => 
  cn(alertVariants({ variant: props.variant }), props.class)
)
</script>

<template>
  <div :class="alertClasses" role="alert">
    <slot />
  </div>
</template>
