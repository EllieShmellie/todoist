<script setup lang="ts">
import { Check } from '@lucide/vue'

withDefaults(defineProps<{
  withMark?: boolean
  compact?: boolean
}>(), {
  withMark: false,
  compact: false,
})
</script>

<template>
  <NuxtLink class="brand-logo" to="/tasks" aria-label="To-do — задачи">
    <span v-if="withMark" class="brand-logo__mark" aria-hidden="true">
      <Check :size="22" :stroke-width="2.2" />
    </span>
    <span :class="['brand-logo__word', { 'brand-logo__word--compact': compact }]">To-do</span>
  </NuxtLink>
</template>
