import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { AUTH_TOKEN_KEY, useAuthStore } from '~/stores/auth'

describe('auth store session behavior', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    window.localStorage.clear()
  })

  it('hydrates a bearer token from localStorage once', () => {
    window.localStorage.setItem(AUTH_TOKEN_KEY, 'token-123')
    const store = useAuthStore()

    store.hydrate()

    expect(store.token).toBe('token-123')
    expect(store.isAuthenticated).toBe(true)
    expect(store.hydrated).toBe(true)
  })

  it('clears both reactive and persisted session state', () => {
    window.localStorage.setItem(AUTH_TOKEN_KEY, 'token-123')
    const store = useAuthStore()
    store.hydrate()
    store.user = {
      id: 7,
      name: 'Алексей Смирнов',
      email: 'user@example.com',
      role: 'user',
    }

    expect(store.initials).toBe('АС')
    store.clearSession()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(window.localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull()
  })
})
