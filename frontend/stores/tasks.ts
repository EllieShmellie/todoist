import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type {
  PaginatedResponse,
  PaginationMeta,
  ResourceResponse,
  Task,
  TaskOwner,
  TaskPayload,
  TaskQuery,
  TaskSummaryStats,
  TaskStatus,
} from '~/types/api'
import { getApiErrorMessage, isTaskOverdue } from '~/utils/tasks'

const EMPTY_META: PaginationMeta = {
  current_page: 1,
  from: null,
  last_page: 1,
  per_page: 10,
  to: null,
  total: 0,
}

export const useTasksStore = defineStore('tasks', () => {
  const items = ref<Task[]>([])
  const availableUsers = ref<TaskOwner[]>([])
  const meta = ref<PaginationMeta>({ ...EMPTY_META })
  const summary = ref<TaskSummaryStats>({ total: 0, pending: 0, in_progress: 0, completed: 0, overdue: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const deletingId = ref<number | null>(null)
  const updatingIds = ref<number[]>([])
  const error = ref('')
  let latestRequest = 0

  const isEmpty = computed(() => !loading.value && items.value.length === 0)

  async function fetchTasks(query: TaskQuery): Promise<void> {
    const requestId = ++latestRequest
    loading.value = true
    error.value = ''

    try {
      const { $api } = useNuxtApp()
      const response = await $api<PaginatedResponse<Task>>('/tasks', {
        query: {
          search: query.search.trim() || undefined,
          status: query.status || undefined,
          user_id: query.user_id || undefined,
          sort: query.sort,
          direction: query.direction,
          page: query.page,
          per_page: query.per_page,
        },
      })

      if (requestId !== latestRequest) return
      items.value = response.data
      availableUsers.value = response.filter_options?.users ?? []
      meta.value = response.meta
      summary.value = response.summary ?? {
        total: response.meta.total,
        pending: response.data.filter(task => task.status === 'pending').length,
        in_progress: response.data.filter(task => task.status === 'in_progress').length,
        completed: response.data.filter(task => task.status === 'completed').length,
        overdue: response.data.filter(task => isTaskOverdue(task.due_date, task.status)).length,
      }
    } catch (request) {
      if (requestId !== latestRequest) return
      error.value = getApiErrorMessage(request, 'Не удалось загрузить задачи')
    } finally {
      if (requestId === latestRequest) loading.value = false
    }
  }

  async function createTask(payload: TaskPayload): Promise<Task> {
    saving.value = true
    try {
      const { $api } = useNuxtApp()
      const response = await $api<ResourceResponse<Task>>('/tasks', {
        method: 'POST',
        body: payload,
      })
      return response.data
    } finally {
      saving.value = false
    }
  }

  async function updateTask(taskId: number, payload: Partial<TaskPayload>): Promise<Task> {
    saving.value = true
    markUpdating(taskId, true)
    try {
      const { $api } = useNuxtApp()
      const response = await $api<ResourceResponse<Task>>(`/tasks/${taskId}`, {
        method: 'PATCH',
        body: payload,
      })
      replaceTask(response.data)
      return response.data
    } finally {
      saving.value = false
      markUpdating(taskId, false)
    }
  }

  async function updateStatus(task: Task, status: TaskStatus): Promise<void> {
    if (task.status === status) return
    const previous = task.status
    replaceTask({ ...task, status })

    try {
      await updateTask(task.id, { status })
    } catch (request) {
      replaceTask({ ...task, status: previous })
      throw request
    }
  }

  async function deleteTask(taskId: number): Promise<void> {
    deletingId.value = taskId
    try {
      const { $api } = useNuxtApp()
      await $api(`/tasks/${taskId}`, { method: 'DELETE' })
      items.value = items.value.filter(task => task.id !== taskId)
    } finally {
      deletingId.value = null
    }
  }

  function replaceTask(task: Task): void {
    const index = items.value.findIndex(item => item.id === task.id)
    if (index >= 0) items.value.splice(index, 1, task)
  }

  function markUpdating(taskId: number, active: boolean): void {
    updatingIds.value = active
      ? [...new Set([...updatingIds.value, taskId])]
      : updatingIds.value.filter(id => id !== taskId)
  }

  return {
    items,
    availableUsers,
    meta,
    summary,
    loading,
    saving,
    deletingId,
    updatingIds,
    error,
    isEmpty,
    fetchTasks,
    createTask,
    updateTask,
    updateStatus,
    deleteTask,
  }
})
