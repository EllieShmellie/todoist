<script setup lang="ts">
import { AlertTriangle, LoaderCircle, X } from '@lucide/vue'

const props = withDefaults(defineProps<{
  open: boolean
  title: string
  message: string
  confirming?: boolean
}>(), {
  confirming: false,
})

const emit = defineEmits<{
  cancel: []
  confirm: []
}>()

const cancelRef = ref<HTMLButtonElement | null>(null)

watch(() => props.open, async (open) => {
  if (!open) return
  await nextTick()
  cancelRef.value?.focus()
})

function cancel(): void {
  if (!props.confirming) emit('cancel')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="open" class="modal-backdrop" @mousedown.self="cancel">
        <section class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" @keydown.esc.prevent="cancel">
          <button class="modal-close" type="button" :disabled="confirming" aria-label="Закрыть окно" @click="cancel">
            <X :size="22" aria-hidden="true" />
          </button>
          <span class="confirm-dialog__icon" aria-hidden="true"><AlertTriangle :size="25" /></span>
          <h2 id="confirm-title">{{ title }}</h2>
          <p>{{ message }}</p>
          <div class="confirm-dialog__actions">
            <button ref="cancelRef" class="button button--secondary" type="button" :disabled="confirming" @click="cancel">Отмена</button>
            <button class="button button--danger" type="button" :disabled="confirming" @click="emit('confirm')">
              <LoaderCircle v-if="confirming" class="spin" :size="18" aria-hidden="true" />
              {{ confirming ? 'Удаляем…' : 'Удалить' }}
            </button>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>
