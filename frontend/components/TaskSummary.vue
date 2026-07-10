<script setup lang="ts">
import type { Task, TaskSummaryStats } from '~/types/api'

const props = defineProps<{
  tasks: Task[]
  summary: TaskSummaryStats
}>()

const nearest = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const until = new Date(now)
  until.setDate(until.getDate() + 7)
  return props.tasks.filter((task) => {
    if (!task.due_date || task.status === 'completed') return false
    const due = new Date(`${task.due_date}T00:00:00`)
    return due >= now && due <= until
  }).length
})
const progress = computed(() => props.summary.total ? Math.round((props.summary.completed / props.summary.total) * 100) : 0)

function taskNoun(count: number): string {
  const mod100 = count % 100
  const mod10 = count % 10

  if (mod100 >= 11 && mod100 <= 14) return 'задач'
  if (mod10 === 1) return 'задача'
  if (mod10 >= 2 && mod10 <= 4) return 'задачи'
  return 'задач'
}
</script>

<template>
  <aside class="task-summary" aria-labelledby="summary-heading">
    <h2 id="summary-heading">Сводка</h2>
    <p class="task-summary__hint">по всем доступным задачам</p>

    <dl class="summary-list">
      <div>
        <dt>Всего задач</dt>
        <dd>{{ summary.total }}</dd>
      </div>
      <div>
        <dt>Ожидает</dt>
        <dd>{{ summary.pending }}</dd>
      </div>
      <div>
        <dt>В работе</dt>
        <dd>{{ summary.in_progress }}</dd>
      </div>
      <div>
        <dt>Готово</dt>
        <dd>{{ summary.completed }}</dd>
      </div>
    </dl>

    <div class="summary-progress">
      <div class="summary-progress__label">
        <span>Прогресс</span>
        <strong>{{ progress }}%</strong>
      </div>
      <div class="summary-progress__track" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
        <span :style="{ width: `${progress}%` }" />
      </div>
    </div>

    <dl class="summary-list summary-list--secondary">
      <div>
        <dt>Ближайшие на странице</dt>
        <dd>{{ nearest }} {{ taskNoun(nearest) }}</dd>
      </div>
      <div>
        <dt>Просрочено</dt>
        <dd :class="{ 'summary-list__danger': summary.overdue > 0 }">{{ summary.overdue }}</dd>
      </div>
    </dl>
  </aside>
</template>
