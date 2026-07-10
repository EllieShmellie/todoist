<template>
  <div class="tasks-skeleton" aria-label="Загрузка задач" aria-busy="true">
    <span class="sr-only">Задачи загружаются</span>
    <div v-for="index in 5" :key="index" class="tasks-skeleton__row">
      <span class="skeleton skeleton--check" />
      <span class="tasks-skeleton__text">
        <i class="skeleton skeleton--title" />
        <i class="skeleton skeleton--line" />
      </span>
      <span class="skeleton skeleton--date" />
      <span class="skeleton skeleton--status" />
      <span class="skeleton skeleton--action" />
    </div>
  </div>
</template>
