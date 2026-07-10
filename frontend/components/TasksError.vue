<script setup lang="ts">
import { AlertCircle, RotateCcw } from '@lucide/vue'

defineProps<{ message: string }>()
defineEmits<{ retry: [] }>()
</script>

<template>
  <div class="tasks-error" role="alert">
    <AlertCircle :size="21" aria-hidden="true" />
    <div>
      <strong>Не удалось загрузить задачи</strong>
      <p>{{ message }}</p>
    </div>
    <button class="button button--secondary button--small" type="button" @click="$emit('retry')">
      <RotateCcw :size="16" aria-hidden="true" />
      Повторить
    </button>
  </div>
</template>
