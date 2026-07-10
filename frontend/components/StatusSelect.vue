<script setup lang="ts">
import { LoaderCircle } from '@lucide/vue'
import type { TaskStatus } from '~/types/api'
import { STATUS_META } from '~/utils/tasks'

const props = defineProps<{
  modelValue: TaskStatus
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: TaskStatus]
}>()

function update(event: Event): void {
  emit('update:modelValue', (event.target as HTMLSelectElement).value as TaskStatus)
}
</script>

<template>
  <div class="status-select" :class="{ 'status-select--loading': disabled }">
    <LoaderCircle v-if="disabled" class="status-select__spinner spin" :size="15" aria-hidden="true" />
    <span v-else class="status-dot" :style="{ backgroundColor: STATUS_META[modelValue].color }" aria-hidden="true" />
    <select
      :value="modelValue"
      :disabled="disabled"
      :aria-label="`Статус: ${STATUS_META[modelValue].label}`"
      @change="update"
    >
      <option value="pending">Ожидает</option>
      <option value="in_progress">В работе</option>
      <option value="completed">Готово</option>
    </select>
  </div>
</template>
