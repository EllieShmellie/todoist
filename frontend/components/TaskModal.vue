<script setup lang="ts">
import { AlertCircle, LoaderCircle, X } from '@lucide/vue'
import type { Task, TaskPayload, TaskStatus } from '~/types/api'
import { getApiErrorMessage, getApiFieldErrors, validateTaskPayload, type TaskFormErrors } from '~/utils/tasks'

const props = defineProps<{
  open: boolean
  task: Task | null
  saving: boolean
  requestError?: unknown
}>()

const emit = defineEmits<{
  close: []
  submit: [payload: TaskPayload]
}>()

const dialogRef = ref<HTMLElement | null>(null)
const titleInputRef = ref<HTMLInputElement | null>(null)
const title = ref('')
const description = ref('')
const dueDate = ref('')
const status = ref<TaskStatus>('pending')
const errors = ref<TaskFormErrors>({})
let returnFocus: HTMLElement | null = null

const isEditing = computed(() => Boolean(props.task))
const heading = computed(() => isEditing.value ? 'Редактировать задачу' : 'Новая задача')
const submitLabel = computed(() => isEditing.value ? 'Сохранить изменения' : 'Создать задачу')
const serverFields = computed(() => getApiFieldErrors(props.requestError))
const generalError = computed(() => {
  if (!props.requestError || Object.keys(serverFields.value).length) return ''
  return getApiErrorMessage(props.requestError, 'Не удалось сохранить задачу. Попробуйте ещё раз.')
})

watch(() => props.open, async (open) => {
  if (!open) {
    document.body.classList.remove('modal-open')
    returnFocus?.focus()
    return
  }

  returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null
  resetForm()
  document.body.classList.add('modal-open')
  await nextTick()
  titleInputRef.value?.focus()
})

watch(() => props.task, () => {
  if (props.open) resetForm()
})

onBeforeUnmount(() => document.body.classList.remove('modal-open'))

function resetForm(): void {
  title.value = props.task?.title ?? ''
  description.value = props.task?.description ?? ''
  dueDate.value = props.task?.due_date ?? ''
  status.value = props.task?.status ?? 'pending'
  errors.value = {}
}

function close(): void {
  if (!props.saving) emit('close')
}

function submit(): void {
  const payload: TaskPayload = {
    title: title.value.trim(),
    description: description.value.trim() || null,
    due_date: dueDate.value || null,
    status: status.value,
  }
  errors.value = validateTaskPayload(payload)
  if (Object.keys(errors.value).length) return
  emit('submit', payload)
}

function clearError(field: keyof TaskFormErrors): void {
  if (!errors.value[field]) return
  errors.value = { ...errors.value, [field]: undefined }
}

function handleKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    event.preventDefault()
    close()
    return
  }

  if (event.key !== 'Tab' || !dialogRef.value) return
  const focusable = [...dialogRef.value.querySelectorAll<HTMLElement>(
    'button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
  )]
  if (!focusable.length) return
  const first = focusable[0]!
  const last = focusable.at(-1)!

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function firstError(field: keyof TaskPayload): string | undefined {
  return errors.value[field] || serverFields.value[field]?.[0]
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="open" class="modal-backdrop" @mousedown.self="close">
        <section
          ref="dialogRef"
          class="task-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="task-modal-title"
          @keydown="handleKeydown"
        >
          <form novalidate @submit.prevent="submit">
            <header class="task-modal__header">
              <h2 id="task-modal-title">{{ heading }}</h2>
              <button class="modal-close" type="button" :disabled="saving" aria-label="Закрыть окно" @click="close">
                <X :size="25" :stroke-width="1.8" aria-hidden="true" />
              </button>
            </header>

            <div class="task-modal__body">
              <div v-if="generalError" class="auth-alert task-modal__alert" role="alert">
                <AlertCircle :size="18" aria-hidden="true" />
                <span>{{ generalError }}</span>
              </div>
              <label class="form-field" :class="{ 'form-field--error': firstError('title') }">
                <span>Название</span>
                <input
                  ref="titleInputRef"
                  v-model="title"
                  type="text"
                  maxlength="255"
                  placeholder="Например, обновить документацию"
                  :aria-invalid="Boolean(firstError('title'))"
                  :aria-describedby="firstError('title') ? 'title-error' : undefined"
                  @input="clearError('title')"
                >
                <small v-if="firstError('title')" id="title-error" class="form-error">
                  <AlertCircle :size="16" aria-hidden="true" />
                  {{ firstError('title') }}
                </small>
              </label>

              <label class="form-field" :class="{ 'form-field--error': firstError('description') }">
                <span>Описание <em>необязательно</em></span>
                <textarea
                  v-model="description"
                  rows="4"
                  placeholder="Добавьте детали задачи"
                  :aria-invalid="Boolean(firstError('description'))"
                  @input="clearError('description')"
                />
                <small v-if="firstError('description')" class="form-error">
                  <AlertCircle :size="16" aria-hidden="true" />
                  {{ firstError('description') }}
                </small>
              </label>

              <label class="form-field" :class="{ 'form-field--error': firstError('due_date') }">
                <span>Срок <em>необязательно</em></span>
                <input
                  v-model="dueDate"
                  type="date"
                  :aria-invalid="Boolean(firstError('due_date'))"
                  @input="clearError('due_date')"
                >
                <small v-if="firstError('due_date')" class="form-error">
                  <AlertCircle :size="16" aria-hidden="true" />
                  {{ firstError('due_date') }}
                </small>
              </label>

              <label class="form-field" :class="{ 'form-field--error': firstError('status') }">
                <span>Статус</span>
                <select v-model="status" :aria-invalid="Boolean(firstError('status'))" @change="clearError('status')">
                  <option value="pending">Ожидает</option>
                  <option value="in_progress">В работе</option>
                  <option value="completed">Готово</option>
                </select>
                <small v-if="firstError('status')" class="form-error">
                  <AlertCircle :size="16" aria-hidden="true" />
                  {{ firstError('status') }}
                </small>
              </label>
            </div>

            <footer class="task-modal__footer">
              <button class="button button--secondary" type="button" :disabled="saving" @click="close">Отмена</button>
              <button class="button button--primary" type="submit" :disabled="saving">
                <LoaderCircle v-if="saving" class="spin" :size="18" aria-hidden="true" />
                {{ saving ? 'Сохраняем…' : submitLabel }}
              </button>
            </footer>
          </form>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>
