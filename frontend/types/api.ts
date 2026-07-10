export const TASK_STATUSES = ['pending', 'in_progress', 'completed'] as const

export type TaskStatus = (typeof TASK_STATUSES)[number]
export type UserRole = 'admin' | 'user'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole | string
  created_at?: string
}

export interface TaskOwner {
  id: number
  name: string
  email: string
}

export interface TaskPermissions {
  update: boolean
  delete: boolean
}

export interface Task {
  id: number
  user_id: number
  user: TaskOwner
  title: string
  description: string | null
  due_date: string | null
  status: TaskStatus
  created_at: string
  updated_at: string
  can: TaskPermissions
}

export interface TaskPayload {
  title: string
  description: string | null
  due_date: string | null
  status: TaskStatus
}

export interface AuthResponse {
  token: string
  token_type: 'Bearer' | string
  user: User
}

export interface ResourceResponse<T> {
  data: T
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  links: PaginationLinks
  meta: PaginationMeta
  summary?: TaskSummaryStats
  filter_options?: {
    users: TaskOwner[]
  }
}

export interface TaskSummaryStats {
  total: number
  pending: number
  in_progress: number
  completed: number
  overdue: number
}

export interface TaskQuery {
  search: string
  status: TaskStatus | ''
  user_id: number | null
  sort: 'due_date' | 'status' | 'created_at' | 'user'
  direction: 'asc' | 'desc'
  page: number
  per_page: number
}

export interface ApiErrorData {
  message?: string
  errors?: Record<string, string[]>
}
