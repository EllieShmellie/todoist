import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useTasksStore } from '~/stores/tasks'
import type { PaginatedResponse, ResourceResponse, Task, TaskPayload, TaskQuery } from '~/types/api'

const defaultQuery: TaskQuery = {
  search: '',
  status: '',
  sort: 'due_date',
  direction: 'asc',
  page: 1,
  per_page: 10,
}

function makeTask(overrides: Partial<Task> = {}): Task {
  return {
    id: 1,
    user_id: 7,
    user: {
      id: 7,
      name: 'Алексей Смирнов',
      email: 'user@example.com',
    },
    title: 'Обновить README',
    description: null,
    due_date: '2099-07-14',
    status: 'pending',
    created_at: '2026-07-10T10:00:00.000000Z',
    updated_at: '2026-07-10T10:00:00.000000Z',
    can: { update: true, delete: true },
    ...overrides,
  }
}

function deferred<T>() {
  let resolve!: (value: T) => void
  let reject!: (reason?: unknown) => void
  const promise = new Promise<T>((promiseResolve, promiseReject) => {
    resolve = promiseResolve
    reject = promiseReject
  })

  return { promise, resolve, reject }
}

describe('tasks store API behavior', () => {
  const api = vi.fn()

  beforeEach(() => {
    setActivePinia(createPinia())
    api.mockReset()
    vi.stubGlobal('useNuxtApp', () => ({ $api: api }))
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('normalizes filters and stores a paginated task response', async () => {
    const task = makeTask()
    const response: PaginatedResponse<Task> = {
      data: [task],
      links: { first: null, last: null, prev: null, next: null },
      meta: { current_page: 2, from: 11, last_page: 3, per_page: 10, to: 11, total: 21 },
      summary: { total: 21, pending: 8, in_progress: 7, completed: 6, overdue: 2 },
    }
    api.mockResolvedValue(response)
    const store = useTasksStore()

    await store.fetchTasks({
      ...defaultQuery,
      search: '  README  ',
      status: 'pending',
      page: 2,
    })

    expect(api).toHaveBeenCalledWith('/tasks', {
      query: {
        search: 'README',
        status: 'pending',
        sort: 'due_date',
        direction: 'asc',
        page: 2,
        per_page: 10,
      },
    })
    expect(store.items).toEqual([task])
    expect(store.meta).toEqual(response.meta)
    expect(store.summary).toEqual(response.summary)
    expect(store.loading).toBe(false)
    expect(store.error).toBe('')
  })

  it('exposes saving state while creating a task and returns the API resource', async () => {
    const payload: TaskPayload = {
      title: 'Новая задача',
      description: 'Детали',
      due_date: null,
      status: 'pending',
    }
    const created = makeTask({ id: 12, title: payload.title, description: payload.description })
    const request = deferred<ResourceResponse<Task>>()
    api.mockReturnValue(request.promise)
    const store = useTasksStore()

    const result = store.createTask(payload)

    expect(store.saving).toBe(true)
    expect(api).toHaveBeenCalledWith('/tasks', { method: 'POST', body: payload })

    request.resolve({ data: created })
    await expect(result).resolves.toEqual(created)
    expect(store.saving).toBe(false)
  })

  it('optimistically changes status and rolls it back when the API rejects', async () => {
    const task = makeTask()
    const request = deferred<ResourceResponse<Task>>()
    const apiError = { data: { message: 'Нет доступа' } }
    api.mockReturnValue(request.promise)
    const store = useTasksStore()
    store.items = [task]

    const result = store.updateStatus(task, 'completed')

    expect(store.items[0]?.status).toBe('completed')
    expect(store.saving).toBe(true)
    expect(store.updatingIds).toEqual([task.id])
    expect(api).toHaveBeenCalledWith(`/tasks/${task.id}`, {
      method: 'PATCH',
      body: { status: 'completed' },
    })

    request.reject(apiError)
    await expect(result).rejects.toBe(apiError)
    expect(store.items[0]?.status).toBe('pending')
    expect(store.saving).toBe(false)
    expect(store.updatingIds).toEqual([])
  })

  it('removes a task only after deletion succeeds and clears progress state', async () => {
    const first = makeTask()
    const second = makeTask({ id: 2, title: 'Остаться в списке' })
    const request = deferred<void>()
    api.mockReturnValue(request.promise)
    const store = useTasksStore()
    store.items = [first, second]

    const result = store.deleteTask(first.id)

    expect(store.deletingId).toBe(first.id)
    expect(store.items).toEqual([first, second])
    expect(api).toHaveBeenCalledWith(`/tasks/${first.id}`, { method: 'DELETE' })

    request.resolve()
    await result
    expect(store.items).toEqual([second])
    expect(store.deletingId).toBeNull()
  })
})
