import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { AuthResponse, User } from '~/types/api'

export const AUTH_TOKEN_KEY = 'todo.auth.token'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<User | null>(null)
  const hydrated = ref(false)
  const loading = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value))
  const isAdmin = computed(() => user.value?.role === 'admin')
  const initials = computed(() => {
    const source = user.value?.name || user.value?.email || 'П'
    return source
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map(part => part[0]?.toUpperCase())
      .join('')
  })

  function hydrate(): void {
    if (hydrated.value) return
    token.value = typeof window !== 'undefined' ? window.localStorage.getItem(AUTH_TOKEN_KEY) : null
    hydrated.value = true
  }

  function persistToken(value: string): void {
    token.value = value
    if (typeof window !== 'undefined') window.localStorage.setItem(AUTH_TOKEN_KEY, value)
  }

  function clearSession(removeStoredToken = true): void {
    token.value = null
    user.value = null
    hydrated.value = true
    if (removeStoredToken && typeof window !== 'undefined') window.localStorage.removeItem(AUTH_TOKEN_KEY)
  }

  async function login(credentials: { email: string, password: string }): Promise<void> {
    loading.value = true
    try {
      const { $api } = useNuxtApp()
      const response = await $api<AuthResponse>('/auth/login', {
        method: 'POST',
        body: credentials,
      })

      persistToken(response.token)
      user.value = response.user
      hydrated.value = true
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(): Promise<void> {
    hydrate()
    if (!token.value) return

    const { $api } = useNuxtApp()
    user.value = await $api<User>('/user')
  }

  async function logout(): Promise<void> {
    loading.value = true
    try {
      if (token.value) {
        const { $api } = useNuxtApp()
        await $api('/auth/logout', { method: 'POST' })
      }
    } finally {
      clearSession()
      loading.value = false
    }
  }

  return {
    token,
    user,
    hydrated,
    loading,
    isAuthenticated,
    isAdmin,
    initials,
    hydrate,
    clearSession,
    login,
    fetchUser,
    logout,
  }
})
