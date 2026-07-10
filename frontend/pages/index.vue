<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
await navigateTo('/tasks', { replace: true })
</script>

<template>
  <div />
</template>
