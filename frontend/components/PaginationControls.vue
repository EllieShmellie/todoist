<script setup lang="ts">
import { ChevronDown, ChevronLeft, ChevronRight } from '@lucide/vue'
import { pagesAround } from '~/utils/tasks'

const props = defineProps<{
  page: number
  lastPage: number
  perPage: number
  from: number | null
  to: number | null
  total: number
}>()

const emit = defineEmits<{
  'update:page': [page: number]
  'update:perPage': [perPage: number]
}>()

const pages = computed(() => pagesAround(props.page, props.lastPage))

function updatePerPage(event: Event): void {
  emit('update:perPage', Number((event.target as HTMLSelectElement).value))
}
</script>

<template>
  <nav class="pagination" aria-label="Пагинация задач">
    <div class="pagination__per-page">
      <label class="pagination__select">
        <span class="sr-only">Задач на странице</span>
        <select :value="perPage" @change="updatePerPage">
          <option :value="5">5 на странице</option>
          <option :value="10">10 на странице</option>
          <option :value="15">15 на странице</option>
          <option :value="25">25 на странице</option>
          <option :value="50">50 на странице</option>
        </select>
        <ChevronDown class="pagination__select-chevron" :size="15" aria-hidden="true" />
      </label>
      <span v-if="total" class="pagination__range">{{ from }}–{{ to }} из {{ total }}</span>
    </div>

    <div class="pagination__pages">
      <button
        class="pagination__arrow"
        type="button"
        :disabled="page <= 1"
        aria-label="Предыдущая страница"
        @click="emit('update:page', page - 1)"
      >
        <ChevronLeft :size="20" aria-hidden="true" />
      </button>

      <template v-for="(item, index) in pages" :key="`${item}-${index}`">
        <span v-if="item === 'ellipsis'" class="pagination__ellipsis" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          :class="['pagination__page', { 'pagination__page--active': item === page }]"
          :aria-current="item === page ? 'page' : undefined"
          :aria-label="`Страница ${item}`"
          @click="emit('update:page', item)"
        >
          {{ item }}
        </button>
      </template>

      <button
        class="pagination__arrow"
        type="button"
        :disabled="page >= lastPage"
        aria-label="Следующая страница"
        @click="emit('update:page', page + 1)"
      >
        <ChevronRight :size="20" aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>
