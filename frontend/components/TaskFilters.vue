<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronDown, Search } from '@lucide/vue'
import type { TaskOwner, TaskQuery, TaskStatus } from '~/types/api'

const props = defineProps<{
  search: string
  status: TaskStatus | ''
  userId: number | null
  sort: TaskQuery['sort']
  direction: TaskQuery['direction']
  isAdmin: boolean
  users: TaskOwner[]
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:status': [value: TaskStatus | '']
  'update:userId': [value: number | null]
  'update:sort': [value: TaskQuery['sort']]
  'update:direction': [value: TaskQuery['direction']]
}>()

const searchInput = ref<HTMLInputElement | null>(null)

const statusTabs: Array<{ value: TaskStatus | '', label: string }> = [
  { value: '', label: 'Все' },
  { value: 'pending', label: 'Ожидает' },
  { value: 'in_progress', label: 'В работе' },
  { value: 'completed', label: 'Готово' },
]

function updateSearch(event: Event): void {
  emit('update:search', (event.target as HTMLInputElement).value)
}

function updateSort(event: Event): void {
  emit('update:sort', (event.target as HTMLSelectElement).value as TaskQuery['sort'])
}

function toggleDirection(): void {
  emit('update:direction', props.direction === 'asc' ? 'desc' : 'asc')
}

function handleShortcut(event: KeyboardEvent): void {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    searchInput.value?.focus()
  }
}

onMounted(() => window.addEventListener('keydown', handleShortcut))
onBeforeUnmount(() => window.removeEventListener('keydown', handleShortcut))
</script>

<template>
  <section :class="['task-filters', { 'task-filters--admin': isAdmin }]" aria-label="Поиск, фильтрация и сортировка">
    <label class="search-control">
      <Search :size="20" :stroke-width="1.9" aria-hidden="true" />
      <span class="sr-only">Найти задачу</span>
      <input
        ref="searchInput"
        type="search"
        :value="search"
        placeholder="Найти задачу"
        autocomplete="off"
        @input="updateSearch"
      >
      <kbd aria-hidden="true">⌘ K</kbd>
    </label>

    <div class="status-tabs" role="group" aria-label="Фильтр по статусу">
      <button
        v-for="tab in statusTabs"
        :key="tab.value || 'all'"
        type="button"
        :class="['status-tabs__item', { 'status-tabs__item--active': status === tab.value }]"
        :aria-pressed="status === tab.value"
        @click="emit('update:status', tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div class="filter-actions">
      <UserFilter
        v-if="isAdmin"
        :model-value="userId"
        :users="users"
        @update:model-value="emit('update:userId', $event)"
      />

      <div class="sort-controls">
        <label class="select-control">
          <span class="sr-only">Сортировать задачи</span>
          <select :value="sort" @change="updateSort">
            <option value="due_date">Сначала ближайшие</option>
            <option value="created_at">По дате создания</option>
            <option value="status">По статусу</option>
            <option v-if="isAdmin" value="user">По пользователю</option>
          </select>
          <ChevronDown class="select-control__chevron" :size="16" aria-hidden="true" />
        </label>
        <button
          class="direction-button"
          type="button"
          :aria-label="direction === 'asc' ? 'Сортировать по убыванию' : 'Сортировать по возрастанию'"
          :title="direction === 'asc' ? 'Сейчас по возрастанию' : 'Сейчас по убыванию'"
          @click="toggleDirection"
        >
          <ArrowUp v-if="direction === 'asc'" :size="20" aria-hidden="true" />
          <ArrowDown v-else :size="20" aria-hidden="true" />
        </button>
      </div>
    </div>
  </section>
</template>
