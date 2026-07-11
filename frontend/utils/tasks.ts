import { TASK_STATUSES, type ApiErrorData, type TaskPayload, type TaskQuery, type TaskStatus } from '~/types/api'

export const STATUS_META: Record<TaskStatus, { label: string, color: string }> = {
  pending: { label: 'Ожидает', color: '#f59e0b' },
  in_progress: { label: 'В работе', color: '#0b5cff' },
  completed: { label: 'Готово', color: '#159447' },
}

export const DEFAULT_TASK_QUERY: TaskQuery = {
  search: '',
  status: '',
  user_id: null,
  sort: 'due_date',
  direction: 'asc',
  page: 1,
  per_page: 10,
}

export type TaskFormErrors = Partial<Record<keyof TaskPayload, string>>

export function isTaskStatus(value: unknown): value is TaskStatus {
  return typeof value === 'string' && TASK_STATUSES.includes(value as TaskStatus)
}

export function isSortField(value: unknown): value is TaskQuery['sort'] {
  return value === 'due_date' || value === 'status' || value === 'created_at' || value === 'user'
}

export function isSortDirection(value: unknown): value is TaskQuery['direction'] {
  return value === 'asc' || value === 'desc'
}

export function parsePositiveInt(value: unknown, fallback: number, maximum = Number.MAX_SAFE_INTEGER): number {
  const source = Array.isArray(value) ? value[0] : value
  const parsed = Number.parseInt(String(source ?? ''), 10)

  return Number.isFinite(parsed) && parsed > 0 ? Math.min(parsed, maximum) : fallback
}

export function parseTaskQuery(query: Record<string, unknown>): TaskQuery {
  const statusValue = Array.isArray(query.status) ? query.status[0] : query.status
  const sortValue = Array.isArray(query.sort) ? query.sort[0] : query.sort
  const directionValue = Array.isArray(query.direction) ? query.direction[0] : query.direction
  const searchValue = Array.isArray(query.search) ? query.search[0] : query.search
  const userId = parsePositiveInt(query.user_id, 0)

  return {
    search: typeof searchValue === 'string' ? searchValue.slice(0, 255) : '',
    status: isTaskStatus(statusValue) ? statusValue : '',
    user_id: userId || null,
    sort: isSortField(sortValue) ? sortValue : DEFAULT_TASK_QUERY.sort,
    direction: isSortDirection(directionValue) ? directionValue : DEFAULT_TASK_QUERY.direction,
    page: parsePositiveInt(query.page, 1),
    per_page: parsePositiveInt(query.per_page, DEFAULT_TASK_QUERY.per_page, 100),
  }
}

export function taskQueryToRoute(query: TaskQuery): Record<string, string> {
  const routeQuery: Record<string, string> = {
    sort: query.sort,
    direction: query.direction,
    per_page: String(query.per_page),
  }

  if (query.search.trim()) routeQuery.search = query.search.trim()
  if (query.status) routeQuery.status = query.status
  if (query.user_id) routeQuery.user_id = String(query.user_id)
  if (query.page > 1) routeQuery.page = String(query.page)

  return routeQuery
}

export function validateTaskPayload(payload: TaskPayload): TaskFormErrors {
  const errors: TaskFormErrors = {}
  const title = payload.title.trim()

  if (!title) {
    errors.title = 'Введите название задачи'
  } else if (title.length < 3) {
    errors.title = 'Название должно содержать минимум 3 символа'
  } else if (title.length > 255) {
    errors.title = 'Название не должно быть длиннее 255 символов'
  }

  if (payload.due_date && !isValidIsoDate(payload.due_date)) {
    errors.due_date = 'Укажите корректную дату'
  }

  if (!isTaskStatus(payload.status)) {
    errors.status = 'Выберите корректный статус'
  }

  return errors
}

export function isValidIsoDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(Date.UTC(year!, month! - 1, day))

  return date.getUTCFullYear() === year
    && date.getUTCMonth() === month! - 1
    && date.getUTCDate() === day
}

export function formatTaskDate(value: string | null): string {
  if (!value) return 'Без срока'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' }).format(date)
}

export function formatTaskWeekday(value: string | null): string {
  if (!value) return '—'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return '—'

  return new Intl.DateTimeFormat('ru-RU', { weekday: 'short' }).format(date).replace('.', '')
}

export function isTaskOverdue(dueDate: string | null, status: TaskStatus): boolean {
  if (!dueDate || status === 'completed') return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return new Date(`${dueDate}T00:00:00`).getTime() < today.getTime()
}

export function pagesAround(current: number, last: number): Array<number | 'ellipsis'> {
  if (last <= 7) return Array.from({ length: last }, (_, index) => index + 1)

  const pages = new Set([1, last, current - 1, current, current + 1])
  const sorted = [...pages].filter(page => page >= 1 && page <= last).sort((a, b) => a - b)
  const result: Array<number | 'ellipsis'> = []

  sorted.forEach((page, index) => {
    if (index > 0 && page - sorted[index - 1]! > 1) result.push('ellipsis')
    result.push(page)
  })

  return result
}

export function pageAfterDeletion(currentPage: number, remainingItems: number): number {
  return remainingItems === 0 && currentPage > 1 ? currentPage - 1 : currentPage
}

export function getApiErrorData(error: unknown): ApiErrorData {
  if (typeof error !== 'object' || error === null) return {}
  const candidate = error as { data?: ApiErrorData, response?: { _data?: ApiErrorData } }

  return candidate.data ?? candidate.response?._data ?? {}
}

export function getApiErrorMessage(error: unknown, fallback = 'Не удалось выполнить запрос'): string {
  return getApiErrorData(error).message || fallback
}

export function getApiFieldErrors(error: unknown): Record<string, string[]> {
  return getApiErrorData(error).errors ?? {}
}
