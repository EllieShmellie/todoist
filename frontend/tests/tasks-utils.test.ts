import { describe, expect, it } from 'vitest'
import type { TaskPayload } from '~/types/api'
import {
  getApiErrorMessage,
  isTaskOverdue,
  pagesAround,
  parseTaskQuery,
  taskQueryToRoute,
  validateTaskPayload,
} from '~/utils/tasks'

describe('task query utilities', () => {
  it('parses supported filters and clamps per_page', () => {
    expect(parseTaskQuery({
      search: 'docs',
      status: 'in_progress',
      user_id: '7',
      sort: 'created_at',
      direction: 'desc',
      page: '3',
      per_page: '999',
    })).toEqual({
      search: 'docs',
      status: 'in_progress',
      user_id: 7,
      sort: 'created_at',
      direction: 'desc',
      page: 3,
      per_page: 100,
    })
  })

  it('falls back for malformed query values', () => {
    expect(parseTaskQuery({ status: 'unknown', sort: 'title', direction: 'sideways', page: '-2' })).toEqual({
      search: '',
      status: '',
      user_id: null,
      sort: 'due_date',
      direction: 'asc',
      page: 1,
      per_page: 10,
    })
  })

  it('keeps URLs compact while preserving meaningful state', () => {
    expect(taskQueryToRoute({
      search: '  API  ',
      status: 'pending',
      user_id: 7,
      sort: 'status',
      direction: 'desc',
      page: 2,
      per_page: 25,
    })).toEqual({
      search: 'API',
      status: 'pending',
      user_id: '7',
      sort: 'status',
      direction: 'desc',
      page: '2',
      per_page: '25',
    })
  })
})

describe('task validation and presentation utilities', () => {
  const validPayload: TaskPayload = {
    title: 'Обновить README',
    description: null,
    due_date: '2026-07-14',
    status: 'pending',
  }

  it('accepts a valid task and rejects short titles and impossible dates', () => {
    expect(validateTaskPayload(validPayload)).toEqual({})
    expect(validateTaskPayload({ ...validPayload, title: 'ok', due_date: '2026-02-30' })).toEqual({
      title: 'Название должно содержать минимум 3 символа',
      due_date: 'Укажите корректную дату',
    })
  })

  it('builds an accessible compact pagination window', () => {
    expect(pagesAround(1, 3)).toEqual([1, 2, 3])
    expect(pagesAround(6, 12)).toEqual([1, 'ellipsis', 5, 6, 7, 'ellipsis', 12])
  })

  it('does not mark completed tasks overdue', () => {
    expect(isTaskOverdue('2000-01-01', 'completed')).toBe(false)
    expect(isTaskOverdue('2000-01-01', 'pending')).toBe(true)
  })

  it('reads Laravel error messages', () => {
    expect(getApiErrorMessage({ data: { message: 'Validation failed' } })).toBe('Validation failed')
    expect(getApiErrorMessage(new Error('network'), 'Fallback')).toBe('Fallback')
  })
})
