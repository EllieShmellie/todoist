<script setup lang="ts">
import { ClipboardCheck, SearchX } from '@lucide/vue'

defineProps<{
  filtered: boolean
}>()

const emit = defineEmits<{
  create: []
  reset: []
}>()
</script>

<template>
  <div class="tasks-empty">
    <span class="tasks-empty__icon" aria-hidden="true">
      <SearchX v-if="filtered" :size="30" :stroke-width="1.7" />
      <ClipboardCheck v-else :size="30" :stroke-width="1.7" />
    </span>
    <h2>{{ filtered ? 'Ничего не найдено' : 'Задач пока нет' }}</h2>
    <p>
      {{ filtered
        ? 'Измените запрос или сбросьте фильтры, чтобы увидеть больше задач.'
        : 'Создайте первую задачу — она появится в этом списке.'
      }}
    </p>
    <button v-if="filtered" class="button button--secondary" type="button" @click="emit('reset')">Сбросить фильтры</button>
    <button v-else class="button button--primary" type="button" @click="emit('create')">Создать задачу</button>
  </div>
</template>
