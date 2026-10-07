<template>
  <Teleport to="body">
    <div
      class="quizably-toast-stack"
      aria-live="polite"
      aria-atomic="false"
    >
      <TransitionGroup name="quizably-toast">
        <Toast
          v-for="t in toasts"
          :id="t.id"
          :key="t.id"
          :variant="t.variant"
          :title="t.title"
          :message="t.message"
          :duration="t.duration"
          @dismiss="dismiss"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import Toast from './Toast.vue';
import { useToast } from './useToast.js';

const { toasts, dismiss } = useToast();
</script>

<style scoped>
.quizably-toast-stack {
  position: fixed;
  bottom: 20px;
  inset-inline-end: 20px;
  z-index: 1100;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.quizably-toast-stack > :deep(*) {
  pointer-events: auto;
}

.quizably-toast-enter-active,
.quizably-toast-leave-active {
  transition:
    opacity 200ms ease,
    transform 200ms ease;
}
.quizably-toast-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.quizably-toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
.quizably-toast-enter-from:dir(rtl),
.quizably-toast-leave-to:dir(rtl) {
  transform: translateX(-20px);
}

@media (prefers-reduced-motion: reduce) {
  .quizably-toast-enter-active,
  .quizably-toast-leave-active {
    transition-duration: 0ms;
  }
}
</style>
