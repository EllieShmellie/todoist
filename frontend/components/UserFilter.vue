<script setup lang="ts">
import { Check, ChevronDown, Search, Users } from '@lucide/vue'
import type { TaskOwner } from '~/types/api'

const props = defineProps<{
  modelValue: number | null
  users: TaskOwner[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

const root = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const open = ref(false)
const query = ref('')
const listboxId = 'task-user-filter-listbox'

const selectedUser = computed(() => props.users.find(user => user.id === props.modelValue) ?? null)
const filteredUsers = computed(() => {
  const needle = query.value.trim().toLocaleLowerCase('ru-RU')
  if (!needle) return props.users

  return props.users.filter((user) => {
    return `${user.name} ${user.email}`.toLocaleLowerCase('ru-RU').includes(needle)
  })
})

function close(): void {
  open.value = false
  query.value = ''
}

async function toggle(): Promise<void> {
  open.value = !open.value
  if (!open.value) {
    query.value = ''
    return
  }

  await nextTick()
  searchInput.value?.focus()
}

function selectUser(userId: number | null): void {
  emit('update:modelValue', userId)
  close()
}

function handleOutsideClick(event: PointerEvent): void {
  if (root.value && !root.value.contains(event.target as Node)) close()
}

function handleEscape(event: KeyboardEvent): void {
  if (event.key === 'Escape' && open.value) {
    event.stopPropagation()
    close()
  }
}

onMounted(() => document.addEventListener('pointerdown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('pointerdown', handleOutsideClick))
</script>

<template>
  <div ref="root" class="user-filter" @keydown="handleEscape">
    <button
      class="user-filter__trigger"
      type="button"
      aria-haspopup="listbox"
      :aria-controls="listboxId"
      :aria-expanded="open"
      :title="selectedUser?.email || 'Показать задачи всех пользователей'"
      @click="toggle"
    >
      <Users :size="18" :stroke-width="1.8" aria-hidden="true" />
      <span>{{ selectedUser?.name || 'Все пользователи' }}</span>
      <ChevronDown :class="['user-filter__chevron', { 'user-filter__chevron--open': open }]" :size="17" aria-hidden="true" />
    </button>

    <Transition name="user-filter-popover">
      <div v-if="open" class="user-filter__popover">
        <label class="user-filter__search">
          <Search :size="17" :stroke-width="1.8" aria-hidden="true" />
          <span class="sr-only">Найти пользователя</span>
          <input
            ref="searchInput"
            v-model="query"
            type="search"
            placeholder="Найти пользователя"
            autocomplete="off"
          >
        </label>

        <div :id="listboxId" class="user-filter__options" role="listbox" aria-label="Пользователь">
          <button
            type="button"
            class="user-filter__option"
            :class="{ 'user-filter__option--selected': modelValue === null }"
            role="option"
            :aria-selected="modelValue === null"
            @click="selectUser(null)"
          >
            <span class="user-filter__option-copy">
              <strong>Все пользователи</strong>
              <small>Задачи всей команды</small>
            </span>
            <Check v-if="modelValue === null" :size="17" aria-hidden="true" />
          </button>

          <button
            v-for="user in filteredUsers"
            :key="user.id"
            type="button"
            class="user-filter__option"
            :class="{ 'user-filter__option--selected': modelValue === user.id }"
            role="option"
            :aria-selected="modelValue === user.id"
            @click="selectUser(user.id)"
          >
            <span class="user-filter__option-copy">
              <strong>{{ user.name }}</strong>
              <small>{{ user.email }}</small>
            </span>
            <Check v-if="modelValue === user.id" :size="17" aria-hidden="true" />
          </button>

          <p v-if="filteredUsers.length === 0" class="user-filter__empty">
            Никого не нашли
          </p>
        </div>
      </div>
    </Transition>
  </div>
</template>
