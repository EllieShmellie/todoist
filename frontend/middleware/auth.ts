export default defineNuxtRouteMiddleware(async () => {
  if (import.meta.server) return

  const auth = useAuthStore()
  auth.hydrate()

  if (!auth.token) return navigateTo('/login', { replace: true })

  if (!auth.user) {
    try {
      await auth.fetchUser()
    } catch {
      auth.clearSession()
      return navigateTo('/login', { replace: true })
    }
  }
})
