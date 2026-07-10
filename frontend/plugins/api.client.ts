const AUTH_TOKEN_KEY = 'todo.auth.token'

export default defineNuxtPlugin((nuxtApp) => {
  const config = useRuntimeConfig()

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    onRequest({ options }) {
      const token = localStorage.getItem(AUTH_TOKEN_KEY)
      const headers = new Headers(options.headers as HeadersInit | undefined)

      headers.set('Accept', 'application/json')
      if (token) headers.set('Authorization', `Bearer ${token}`)
      options.headers = headers
    },
    async onResponseError({ response }) {
      if (response.status !== 401) return

      localStorage.removeItem(AUTH_TOKEN_KEY)

      await nuxtApp.runWithContext(async () => {
        const auth = useAuthStore()
        auth.clearSession(false)

        if (useRoute().path !== '/login') {
          await navigateTo('/login', { replace: true })
        }
      })
    },
  })

  return {
    provide: {
      api: api as typeof $fetch,
    },
  }
})
