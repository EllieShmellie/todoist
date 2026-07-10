<script setup lang="ts">
import type { Task, TaskStatus } from '~/types/api'
import { CheckCheck, LoaderCircle, X } from '@lucide/vue'

const props = defineProps<{
  tasks: Task[]
  isAdmin: boolean
  updatingIds: number[]
  deletingId: number | null
  bulkUpdating: boolean
}>()

const emit = defineEmits<{
  edit: [task: Task]
  delete: [task: Task]
  status: [task: Task, status: TaskStatus]
  bulkStatus: [tasks: Task[], status: TaskStatus]
}>()

const selectedIds = ref<number[]>([])
const bulkStatus = ref<TaskStatus>('in_progress')
const selectableTasks = computed(() => props.tasks.filter(task => task.can.update))
const selectedTasks = computed(() => selectableTasks.value.filter(task => selectedIds.value.includes(task.id)))
const allSelected = computed(() => selectableTasks.value.length > 0 && selectableTasks.value.every(task => selectedIds.value.includes(task.id)))

watch(() => props.tasks.map(task => task.id), (ids) => {
  selectedIds.value = selectedIds.value.filter(id => ids.includes(id))
})

function toggleAll(event: Event): void {
  const selected = (event.target as HTMLInputElement).checked
  selectedIds.value = selected ? selectableTasks.value.map(task => task.id) : []
}

function applyBulkStatus(): void {
  if (!selectedTasks.value.length || props.bulkUpdating) return
  emit('bulkStatus', selectedTasks.value, bulkStatus.value)
}

watch(() => props.bulkUpdating, (active, previous) => {
  if (previous && !active) selectedIds.value = []
})

function toggleTask(taskId: number, selected: boolean): void {
  selectedIds.value = selected
    ? [...new Set([...selectedIds.value, taskId])]
    : selectedIds.value.filter(id => id !== taskId)
}
</script>

<template>
  <div class="task-list" role="table" aria-label="Список задач">
    <div class="task-list__header" role="row">
      <div role="columnheader">
        <label v-if="selectableTasks.length" class="check-control">
          <span class="sr-only">Выбрать все задачи на странице</span>
          <input type="checkbox" :checked="allSelected" @change="toggleAll">
          <span aria-hidden="true" />
        </label>
      </div>
      <div role="columnheader">Задача</div>
      <div role="columnheader">Срок</div>
      <div role="columnheader">Статус</div>
      <div role="columnheader">Действия</div>
    </div>


    <div v-if="selectedTasks.length" class="task-list__bulk" role="toolbar" aria-label="Действия с выбранными задачами">
      <span><CheckCheck :size="18" aria-hidden="true" /> Выбрано: {{ selectedTasks.length }}</span>
      <label>
        <span class="sr-only">Новый статус выбранных задач</span>
        <select v-model="bulkStatus" :disabled="bulkUpdating">
          <option value="pending">Ожидает</option>
          <option value="in_progress">В работе</option>
          <option value="completed">Готово</option>
        </select>
      </label>
      <button class="button button--primary button--small" type="button" :disabled="bulkUpdating" @click="applyBulkStatus">
        <LoaderCircle v-if="bulkUpdating" class="spin" :size="16" aria-hidden="true" />
        {{ bulkUpdating ? 'Обновляем…' : 'Применить' }}
      </button>
      <button class="task-list__bulk-clear" type="button" :disabled="bulkUpdating" aria-label="Снять выделение" @click="selectedIds = []">
        <X :size="18" aria-hidden="true" />
      </button>
    </div>

    <div class="task-list__body" role="rowgroup">
      <TaskRow
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        :is-admin="isAdmin"
        :selected="selectedIds.includes(task.id)"
        :updating="updatingIds.includes(task.id)"
        :deleting="deletingId === task.id"
        @edit="emit('edit', $event)"
        @delete="emit('delete', $event)"
        @status="(task, status) => emit('status', task, status)"
        @select="toggleTask"
      />
    </div>
  </div>
</template>
