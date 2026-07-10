<script setup lang="ts">
import { AlertCircle, Eye, EyeOff, Info, LoaderCircle, Minus, MoveDownRight } from '@lucide/vue'
import { getApiErrorMessage } from '~/utils/tasks'

definePageMeta({ middleware: 'guest' })
useHead({ title: 'Вход' })

const auth = useAuthStore()
const email = ref('user@example.com')
const password = ref('password')
const showPassword = ref(false)
const errors = ref<{ email?: string, password?: string }>({})
const requestError = ref('')

function validate(): boolean {
  const nextErrors: typeof errors.value = {}
  const normalizedEmail = email.value.trim()

  if (!normalizedEmail) nextErrors.email = 'Введите email'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) nextErrors.email = 'Введите корректный email'
  if (!password.value) nextErrors.password = 'Введите пароль'

  errors.value = nextErrors
  return !Object.keys(nextErrors).length
}

async function submit(): Promise<void> {
  requestError.value = ''
  if (!validate()) return

  try {
    await auth.login({ email: email.value.trim(), password: password.value })
    await navigateTo('/tasks', { replace: true })
  } catch (request) {
    requestError.value = getApiErrorMessage(request, 'Неверный email или пароль')
  }
}

function fillAccount(account: 'user' | 'admin'): void {
  email.value = `${account}@example.com`
  password.value = 'password'
  errors.value = {}
  requestError.value = ''
}
</script>

<template>
  <main class="auth-page">
    <header class="auth-page__header">
      <BrandLogo with-mark />
    </header>

    <section class="auth-page__content">
      <div class="auth-promise" aria-hidden="true">
        <p>
          Меньше<br>
          шума.<br>
          <strong>Больше<br>сделанного.</strong>
        </p>
        <div class="auth-promise__stroke">
          <Minus :size="188" :stroke-width="1.4" />
          <MoveDownRight :size="80" :stroke-width="1.5" />
        </div>
      </div>

      <div class="auth-form-wrap">
        <form class="auth-form" novalidate @submit.prevent="submit">
          <div class="auth-form__heading">
            <h1>С возвращением</h1>
            <p>Войдите, чтобы продолжить работу<br>с задачами.</p>
          </div>

          <div v-if="requestError" class="auth-alert" role="alert">
            <AlertCircle :size="18" aria-hidden="true" />
            <span>{{ requestError }}</span>
          </div>

          <label class="form-field" :class="{ 'form-field--error': errors.email }">
            <span>Email</span>
            <input
              v-model="email"
              type="email"
              autocomplete="email"
              placeholder="user@example.com"
              :aria-invalid="Boolean(errors.email)"
              @input="errors.email = undefined; requestError = ''"
            >
            <small v-if="errors.email" class="form-error">
              <AlertCircle :size="16" aria-hidden="true" />
              {{ errors.email }}
            </small>
          </label>

          <label class="form-field" :class="{ 'form-field--error': errors.password }">
            <span>Пароль</span>
            <span class="password-control">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Введите пароль"
                :aria-invalid="Boolean(errors.password)"
                @input="errors.password = undefined; requestError = ''"
              >
              <button
                type="button"
                :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" :size="21" aria-hidden="true" />
                <Eye v-else :size="21" aria-hidden="true" />
              </button>
            </span>
            <small v-if="errors.password" class="form-error">
              <AlertCircle :size="16" aria-hidden="true" />
              {{ errors.password }}
            </small>
          </label>

          <button class="button button--primary auth-form__submit" type="submit" :disabled="auth.loading">
            <LoaderCircle v-if="auth.loading" class="spin" :size="20" aria-hidden="true" />
            {{ auth.loading ? 'Входим…' : 'Войти' }}
          </button>

          <div class="auth-form__divider"><span>или</span></div>

          <div class="demo-accounts">
            <Info :size="18" aria-hidden="true" />
            <div>
              <p>Тестовые аккаунты, пароль <strong>password</strong></p>
              <button type="button" @click="fillAccount('user')">user@example.com</button>
              <span>·</span>
              <button type="button" @click="fillAccount('admin')">admin@example.com</button>
            </div>
          </div>
        </form>
      </div>
    </section>

    <footer class="auth-page__footer">© {{ new Date().getFullYear() }} Фокус. Все права защищены.</footer>
  </main>
</template>
