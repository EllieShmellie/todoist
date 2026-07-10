<script setup lang="ts">
import { Edit3, LoaderCircle, Trash2 } from '@lucide/vue'
import type { Task, TaskStatus } from '~/types/api'
import { formatTaskDate, formatTaskWeekday, isTaskOverdue } from '~/utils/tasks'

const props = defineProps<{
  task: Task
  isAdmin: boolean
  selected: boolean
  updating: boolean
  deleting: boolean
}>()

const emit = defineEmits<{
  edit: [task: Task]
  delete: [task: Task]
  status: [task: Task, status: TaskStatus]
  select: [taskId: number, selected: boolean]
}>()

const overdue = computed(() => isTaskOverdue(props.task.due_date, props.task.status))

function updateStatus(status: TaskStatus): void {
  emit('status', props.task, status)
}

function updateSelected(event: Event): void {
  emit('select', props.task.id, (event.target as HTMLInputElement).checked)
}
</script>

<template>
  <div
    :class="['task-row', { 'task-row--selected': selected, 'task-row--busy': deleting }]"
    role="row"
  >
    <div class="task-row__select" role="cell">
      <label v-if="task.can.update" class="check-control">
        <span class="sr-only">Выбрать задачу «{{ task.title }}»</span>
        <input type="checkbox" :checked="selected" @change="updateSelected">
        <span aria-hidden="true" />
      </label>
    </div>

    <div class="task-row__main" role="cell">
      <strong>{{ task.title }}</strong>
      <p v-if="task.description">{{ task.description }}</p>
      <p v-else class="task-row__description-empty">Без описания</p>
      <p v-if="isAdmin" class="task-row__owner">
        Автор: <span>{{ task.user.name }}</span> · {{ task.user.email }}
      </p>
    </div>

    <div class="task-row__due" role="cell" data-label="Срок">
      <strong :class="{ 'task-row__overdue': overdue }">{{ formatTaskDate(task.due_date) }}</strong>
      <span>{{ formatTaskWeekday(task.due_date) }}</span>
      <small v-if="overdue">Просрочено</small>
    </div>

    <div class="task-row__status" role="cell" data-label="Статус">
      <StatusSelect
        v-if="task.can.update"
        :model-value="task.status"
        :disabled="updating"
        @update:model-value="updateStatus"
      />
      <span v-else class="task-row__readonly">Только просмотр</span>
    </div>

    <div class="task-row__actions" role="cell" data-label="Действия">
      <button
        v-if="task.can.update"
        class="icon-button"
        type="button"
        :disabled="updating || deleting"
        :aria-label="`Редактировать задачу «${task.title}»`"
        title="Редактировать"
        @click="emit('edit', task)"
      >
        <Edit3 :size="20" :stroke-width="1.9" aria-hidden="true" />
      </button>
      <button
        v-if="task.can.delete"
        class="icon-button icon-button--danger"
        type="button"
        :disabled="updating || deleting"
        :aria-label="`Удалить задачу «${task.title}»`"
        title="Удалить"
        @click="emit('delete', task)"
      >
        <LoaderCircle v-if="deleting" class="spin" :size="20" aria-hidden="true" />
        <Trash2 v-else :size="20" :stroke-width="1.9" aria-hidden="true" />
      </button>
      <span v-if="!task.can.update && !task.can.delete" class="task-row__no-actions">—</span>
    </div>
  </div>
</template>
