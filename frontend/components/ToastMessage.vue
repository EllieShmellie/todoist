<script setup lang="ts">
import { AlertCircle, CheckCircle2, X } from '@lucide/vue'

defineProps<{
  message: string
  type: 'success' | 'error'
}>()

defineEmits<{ close: [] }>()
</script>

<template>
  <Transition name="toast-slide">
    <div v-if="message" :class="['toast', `toast--${type}`]" role="status">
      <CheckCircle2 v-if="type === 'success'" :size="20" aria-hidden="true" />
      <AlertCircle v-else :size="20" aria-hidden="true" />
      <span>{{ message }}</span>
      <button type="button" aria-label="Закрыть сообщение" @click="$emit('close')"><X :size="17" /></button>
    </div>
  </Transition>
</template>
