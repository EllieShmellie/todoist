<script setup lang="ts">
import { Plus } from '@lucide/vue'
import type { Task, TaskPayload, TaskQuery, TaskStatus } from '~/types/api'
import { DEFAULT_TASK_QUERY, getApiErrorMessage, pageAfterDeletion, parseTaskQuery, taskQueryToRoute } from '~/utils/tasks'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Мои задачи' })

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const tasks = useTasksStore()

const initialQuery = parseTaskQuery(route.query)
const search = ref(initialQuery.search)
const status = ref<TaskStatus | ''>(initialQuery.status)
const userId = ref<number | null>(initialQuery.user_id)
const sort = ref<TaskQuery['sort']>(initialQuery.sort)
const direction = ref<TaskQuery['direction']>(initialQuery.direction)
const page = ref(initialQuery.page)
const perPage = ref(initialQuery.per_page)

const modalOpen = ref(false)
const editingTask = ref<Task | null>(null)
const modalRequestError = ref<unknown>()
const deletingTask = ref<Task | null>(null)
const toast = ref<{ message: string, type: 'success' | 'error' }>({ message: '', type: 'success' })
const bulkUpdating = ref(false)
let searchTimer: ReturnType<typeof setTimeout> | undefined
let applyingRoute = false
let replacingRoute = false
let toastTimer: ReturnType<typeof setTimeout> | undefined

const currentQuery = computed<TaskQuery>(() => ({
  search: search.value,
  status: status.value,
  user_id: auth.isAdmin ? userId.value : null,
  sort: sort.value,
  direction: direction.value,
  page: page.value,
  per_page: perPage.value,
}))
const hasFilters = computed(() => Boolean(search.value.trim() || status.value || (auth.isAdmin && userId.value)))

onMounted(() => tasks.fetchTasks(currentQuery.value))

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
  if (toastTimer) clearTimeout(toastTimer)
})

watch(search, () => {
  if (applyingRoute) return
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    void syncAndFetch()
  }, 350)
})

watch([status, userId, sort, direction, perPage], () => {
  if (applyingRoute) return
  page.value = 1
  void syncAndFetch()
})

watch(page, () => {
  if (applyingRoute) return
  void syncAndFetch()
})

watch(() => route.query, async (query) => {
  if (replacingRoute) return
  applyingRoute = true
  const parsed = parseTaskQuery(query)
  search.value = parsed.search
  status.value = parsed.status
  userId.value = parsed.user_id
  sort.value = parsed.sort
  direction.value = parsed.direction
  page.value = parsed.page
  perPage.value = parsed.per_page
  await nextTick()
  applyingRoute = false
  await tasks.fetchTasks(currentQuery.value)
}, { deep: true })

async function syncAndFetch(): Promise<void> {
  replacingRoute = true
  try {
    await router.replace({ query: taskQueryToRoute(currentQuery.value) })
  } finally {
    replacingRoute = false
  }
  await tasks.fetchTasks(currentQuery.value)
}

function openCreate(): void {
  editingTask.value = null
  modalRequestError.value = undefined
  modalOpen.value = true
}

function openEdit(task: Task): void {
  editingTask.value = task
  modalRequestError.value = undefined
  modalOpen.value = true
}

function closeModal(): void {
  if (tasks.saving) return
  modalOpen.value = false
  editingTask.value = null
  modalRequestError.value = undefined
}

async function saveTask(payload: TaskPayload): Promise<void> {
  modalRequestError.value = undefined
  try {
    if (editingTask.value) {
      await tasks.updateTask(editingTask.value.id, payload)
      showToast('Изменения сохранены')
    } else {
      await tasks.createTask(payload)
      showToast('Задача создана')
    }
    closeModal()
    await tasks.fetchTasks(currentQuery.value)
  } catch (request) {
    modalRequestError.value = request
  }
}

async function changeStatus(task: Task, nextStatus: TaskStatus): Promise<void> {
  try {
    await tasks.updateStatus(task, nextStatus)
    showToast('Статус обновлён')
    await tasks.fetchTasks(currentQuery.value)
  } catch (request) {
    showToast(getApiErrorMessage(request, 'Не удалось обновить статус'), 'error')
  }
}

async function changeBulkStatus(selectedTasks: Task[], nextStatus: TaskStatus): Promise<void> {
  bulkUpdating.value = true
  try {
    for (const task of selectedTasks) {
      await tasks.updateStatus(task, nextStatus)
    }
    await tasks.fetchTasks(currentQuery.value)
    showToast(`Обновлено задач: ${selectedTasks.length}`)
  } catch (request) {
    showToast(getApiErrorMessage(request, 'Не удалось обновить выбранные задачи'), 'error')
  } finally {
    bulkUpdating.value = false
  }
}

async function confirmDelete(): Promise<void> {
  if (!deletingTask.value) return
  const task = deletingTask.value
  try {
    await tasks.deleteTask(task.id)
    deletingTask.value = null
    const nextPage = pageAfterDeletion(page.value, tasks.items.length)
    if (nextPage !== page.value) page.value = nextPage
    else await tasks.fetchTasks(currentQuery.value)
    showToast('Задача удалена')
  } catch (request) {
    showToast(getApiErrorMessage(request, 'Не удалось удалить задачу'), 'error')
  }
}

async function resetFilters(): Promise<void> {
  applyingRoute = true
  search.value = ''
  status.value = ''
  userId.value = null
  sort.value = DEFAULT_TASK_QUERY.sort
  direction.value = DEFAULT_TASK_QUERY.direction
  page.value = 1
  await nextTick()
  applyingRoute = false
  await syncAndFetch()
}

function showToast(message: string, type: 'success' | 'error' = 'success'): void {
  toast.value = { message, type }
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.message = '' }, 4000)
}
</script>

<template>
  <div class="tasks-page">
    <AppHeader />

    <main class="tasks-main">
      <header class="tasks-hero">
        <div>
          <h1>Мои задачи</h1>
          <p>{{ auth.isAdmin ? 'Контролируйте задачи команды без лишнего шума.' : 'Планируйте спокойно. Делайте вовремя.' }}</p>
        </div>
        <button class="button button--primary tasks-hero__create" type="button" @click="openCreate">
          <Plus :size="22" :stroke-width="1.8" aria-hidden="true" />
          Новая задача
        </button>
      </header>

      <TaskFilters
        v-model:search="search"
        v-model:status="status"
        v-model:user-id="userId"
        v-model:sort="sort"
        v-model:direction="direction"
        :is-admin="auth.isAdmin"
        :users="tasks.availableUsers"
      />

      <div class="tasks-workspace">
        <TaskSummary :tasks="tasks.items" :summary="tasks.summary" />

        <section class="tasks-content" aria-live="polite">
          <TasksError v-if="tasks.error && !tasks.loading" :message="tasks.error" @retry="tasks.fetchTasks(currentQuery)" />
          <TasksSkeleton v-else-if="tasks.loading" />
          <TasksEmpty
            v-else-if="tasks.isEmpty"
            :filtered="hasFilters"
            @create="openCreate"
            @reset="resetFilters"
          />
          <template v-else>
            <TaskList
              :tasks="tasks.items"
              :is-admin="auth.isAdmin"
              :updating-ids="tasks.updatingIds"
              :deleting-id="tasks.deletingId"
              :bulk-updating="bulkUpdating"
              @edit="openEdit"
              @delete="deletingTask = $event"
              @status="changeStatus"
              @bulk-status="changeBulkStatus"
            />
            <PaginationControls
              :page="page"
              :last-page="tasks.meta.last_page"
              :per-page="perPage"
              :from="tasks.meta.from"
              :to="tasks.meta.to"
              :total="tasks.meta.total"
              @update:page="page = $event"
              @update:per-page="perPage = $event"
            />
          </template>
        </section>
      </div>
    </main>

    <TaskModal
      :open="modalOpen"
      :task="editingTask"
      :saving="tasks.saving"
      :request-error="modalRequestError"
      @close="closeModal"
      @submit="saveTask"
    />
    <ConfirmDialog
      :open="Boolean(deletingTask)"
      title="Удалить задачу?"
      :message="`«${deletingTask?.title || ''}» будет удалена без возможности восстановления.`"
      :confirming="tasks.deletingId === deletingTask?.id"
      @cancel="deletingTask = null"
      @confirm="confirmDelete"
    />
    <ToastMessage :message="toast.message" :type="toast.type" @close="toast.message = ''" />
  </div>
</template>
