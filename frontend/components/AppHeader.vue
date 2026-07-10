<script setup lang="ts">
const auth = useAuthStore()
const loggingOut = ref(false)

const roleLabel = computed(() => auth.isAdmin ? 'Администратор' : 'Пользователь')

async function handleLogout(): Promise<void> {
  loggingOut.value = true
  try {
    await auth.logout()
    await navigateTo('/login', { replace: true })
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <header class="app-header">
    <div class="app-header__inner">
      <BrandLogo compact />

      <div class="app-header__account">
        <div class="app-header__identity">
          <span class="app-header__avatar" aria-hidden="true">{{ auth.initials }}</span>
          <span class="app-header__name">{{ auth.user?.name || auth.user?.email }}</span>
        </div>
        <span class="app-header__divider" aria-hidden="true" />
        <span class="app-header__role">{{ roleLabel }}</span>
        <span class="app-header__divider app-header__divider--last" aria-hidden="true" />
        <button class="app-header__logout" type="button" :disabled="loggingOut" @click="handleLogout">
          {{ loggingOut ? 'Выходим…' : 'Выйти' }}
        </button>
      </div>
    </div>
  </header>
</template>
