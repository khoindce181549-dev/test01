import { reactive } from 'vue';

// Shared reactive state so all callers across the app reference the same stack.
// Single instance pattern: the state lives at module scope, not inside the
// composable function, so push() from one component shows up in ToastStack.
const state = reactive({
  toasts: [],
});

let idCounter = 0;

function nextId() {
  idCounter += 1;
  return `quizably-toast-${Date.now()}-${idCounter}`;
}

function push({ variant = 'info', title = '', message = '', duration = 4000 } = {}) {
  const id = nextId();
  const toast = { id, variant, title, message, duration };
  state.toasts.push(toast);

  if (duration > 0) {
    setTimeout(() => dismiss(id), duration);
  }

  return id;
}

function dismiss(id) {
  const idx = state.toasts.findIndex((t) => t.id === id);
  if (idx !== -1) {
    state.toasts.splice(idx, 1);
  }
}

function clear() {
  state.toasts.splice(0, state.toasts.length);
}

export function useToast() {
  return {
    toasts: state.toasts,
    push,
    dismiss,
    clear,
  };
}
