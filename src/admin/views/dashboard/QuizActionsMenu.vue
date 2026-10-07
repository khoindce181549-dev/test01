<template>
  <div
    class="quizably-actions-menu"
    @click.stop
  >
    <button
      ref="toggleRef"
      type="button"
      class="quizably-actions-menu__toggle"
      :aria-expanded="open"
      aria-haspopup="menu"
      :aria-label="actionsLabel"
      @click.stop="onToggleClick"
    >
      <svg
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <circle
          cx="12"
          cy="6"
          r="1.5"
          fill="currentColor"
        />
        <circle
          cx="12"
          cy="12"
          r="1.5"
          fill="currentColor"
        />
        <circle
          cx="12"
          cy="18"
          r="1.5"
          fill="currentColor"
        />
      </svg>
    </button>

    <!-- Teleported to <body> and positioned with `fixed`: the list table sits
         in an `overflow-x: auto` wrapper (and cards in `overflow: hidden`),
         either of which would clip or scroll an in-place dropdown — most
         visibly on the last rows. -->
    <Teleport to="body">
      <div
        v-if="open"
        ref="menuRef"
        class="quizably-actions-menu__dropdown"
        role="menu"
        :style="menuStyle"
        @click.stop
        @keydown="onMenuKeydown"
      >
        <button
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitAndClose('edit')"
        >
          {{ __('Edit') }}
        </button>
        <button
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitAndClose('duplicate')"
        >
          {{ __('Duplicate') }}
        </button>
        <button
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="onExport"
        >
          {{ __('Export') }}
        </button>
        <button
          v-if="quiz.status === 'published'"
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitPublishAndClose(false)"
        >
          {{ __('Set to draft') }}
        </button>
        <button
          v-else-if="quiz.status === 'draft'"
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitPublishAndClose(true)"
        >
          {{ __('Publish') }}
        </button>
        <button
          v-if="quiz.status === 'archived'"
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitAndClose('restore')"
        >
          {{ __('Restore') }}
        </button>
        <button
          v-else
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item"
          @click="emitAndClose('archive')"
        >
          {{ __('Archive') }}
        </button>
        <div
          class="quizably-actions-menu__sep"
          role="separator"
        />
        <button
          type="button"
          role="menuitem"
          class="quizably-actions-menu__item quizably-actions-menu__item--danger"
          @click="emitAndClose('remove')"
        >
          {{ __('Delete') }}
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
/**
 * QuizActionsMenu — the 3-dot menu for one quiz, shared by the grid card and
 * the list table so both offer the same actions with the same behaviour.
 *
 * It only *announces* what the user picked (`edit`, `duplicate`, `archive`,
 * `restore`, `remove`, `publish`); the listing page decides what needs a
 * confirmation and performs the action. Export is self-contained (a JSON
 * download), so it is handled here.
 */
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useToast } from '@admin/ui';
import { api } from '@admin/api/client';

const props = defineProps({
  quiz: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['edit', 'duplicate', 'archive', 'restore', 'remove', 'publish']);

const toast = useToast();

// translators: %s is the quiz title, or "untitled quiz" when it has none.
const actionsLabel = computed(() =>
  sprintf(__('Actions for %s'), props.quiz.title || __('untitled quiz'))
);
const toggleRef = ref(null);
const menuRef = ref(null);
const open = ref(false);
const pos = ref({ top: 0, left: 0 });

const MENU_GAP = 6; // px between the toggle and the dropdown
const VIEWPORT_EDGE = 8; // px kept clear of the viewport edge

const menuStyle = computed(() => ({
  top: `${pos.value.top}px`,
  left: `${pos.value.left}px`,
}));

/** Align to the toggle's inline-end edge under it; flip above it when there's no room below. */
function place() {
  const toggle = toggleRef.value;
  const menu = menuRef.value;
  if (!toggle || !menu) return;

  const t = toggle.getBoundingClientRect();
  const m = menu.getBoundingClientRect();
  const maxLeft = window.innerWidth - m.width - VIEWPORT_EDGE;
  // Align the menu's inline-end edge with the toggle's: its right edge in LTR,
  // its left edge in RTL.
  const rtl = getComputedStyle(toggle).direction === 'rtl';
  const anchored = rtl ? t.left : t.right - m.width;
  const left = Math.max(VIEWPORT_EDGE, Math.min(anchored, maxLeft));

  const below = t.bottom + MENU_GAP;
  const above = t.top - MENU_GAP - m.height;
  const fitsBelow = below + m.height + VIEWPORT_EDGE <= window.innerHeight;
  const top = fitsBelow || above < VIEWPORT_EDGE ? below : above;

  pos.value = { top, left };
}

async function openMenu({ focusFirst = false } = {}) {
  open.value = true;
  await nextTick();
  place();
  if (focusFirst) menuItems()[0]?.focus();
}

function closeMenu({ restoreFocus = false } = {}) {
  if (!open.value) return;
  open.value = false;
  if (restoreFocus) toggleRef.value?.focus();
}

function onToggleClick(event) {
  if (open.value) {
    closeMenu();
    return;
  }
  // A click with no pointer (detail === 0) came from the keyboard — move
  // focus into the menu so it can be driven with the arrow keys.
  openMenu({ focusFirst: event.detail === 0 });
}

function menuItems() {
  return Array.from(menuRef.value?.querySelectorAll('[role="menuitem"]') ?? []);
}

function onMenuKeydown(event) {
  const items = menuItems();
  const index = items.indexOf(document.activeElement);
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      items[(index + 1) % items.length]?.focus();
      break;
    case 'ArrowUp':
      event.preventDefault();
      items[(index - 1 + items.length) % items.length]?.focus();
      break;
    case 'Home':
      event.preventDefault();
      items[0]?.focus();
      break;
    case 'End':
      event.preventDefault();
      items[items.length - 1]?.focus();
      break;
    case 'Tab':
      closeMenu();
      break;
    default:
  }
}

function emitAndClose(event) {
  closeMenu({ restoreFocus: true });
  emit(event, props.quiz.id);
}

function emitPublishAndClose(next) {
  closeMenu({ restoreFocus: true });
  emit('publish', props.quiz.id, next);
}

async function onExport() {
  closeMenu({ restoreFocus: true });
  try {
    const data = await api.get(`quizzes/${props.quiz.id}/export`);
    const json = JSON.stringify(data, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `quiz-${props.quiz.slug || props.quiz.id}-export.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Export failed'), message: e?.message ?? __('Could not export quiz.') });
  }
}

// ── Dismissal ────────────────────────────────────────────────────────────────
// Listeners exist only while the menu is open, to keep the footprint small
// across dozens of rows.

/** A click outside just closes the menu — it must not also open the row/card
 *  underneath (capture phase, so it is swallowed before it reaches them). The
 *  one exception is another row's toggle: let that through so the user can
 *  hop between menus in a single click. */
function onOutsideClick(event) {
  const target = event.target;
  if (menuRef.value?.contains(target) || toggleRef.value?.contains(target)) return;
  closeMenu();
  if (target instanceof Element && target.closest('.quizably-actions-menu__toggle')) return;
  event.stopPropagation();
  event.preventDefault();
}

function onDocumentKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeMenu({ restoreFocus: true });
  }
}

/** The menu is `fixed`, so it would drift away from its row on scroll. */
function onViewportChange(event) {
  if (menuRef.value?.contains(event.target)) return; // scrolling inside the menu
  closeMenu();
}

watch(open, (isOpen) => {
  if (isOpen) {
    document.addEventListener('click', onOutsideClick, true);
    document.addEventListener('keydown', onDocumentKeydown);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
  } else {
    removeListeners();
  }
});

function removeListeners() {
  document.removeEventListener('click', onOutsideClick, true);
  document.removeEventListener('keydown', onDocumentKeydown);
  window.removeEventListener('scroll', onViewportChange, true);
  window.removeEventListener('resize', onViewportChange);
}

onBeforeUnmount(removeListeners);
</script>

<style scoped>
.quizably-actions-menu {
  position: relative;
  display: inline-flex;
}

.quizably-actions-menu__toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  background: transparent;
  border: 0;
  border-radius: var(--r-md);
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}
.quizably-actions-menu__toggle:hover,
.quizably-actions-menu__toggle[aria-expanded='true'] {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.quizably-actions-menu__toggle:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.quizably-actions-menu__toggle svg {
  width: 16px;
  height: 16px;
}

/* z-index: above page content and sticky bars, below the WP admin bar. */
.quizably-actions-menu__dropdown {
  position: fixed;
  z-index: 99990;
  min-width: 168px;
  max-height: calc(100vh - 16px);
  overflow-y: auto;
  padding: 4px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.quizably-actions-menu__item {
  text-align: start;
  padding: 8px 10px;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
  background: transparent;
  border: 0;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}
.quizably-actions-menu__item:hover,
.quizably-actions-menu__item:focus-visible {
  outline: none;
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-actions-menu__item--danger {
  color: var(--danger);
}
.quizably-actions-menu__item--danger:hover,
.quizably-actions-menu__item--danger:focus-visible {
  background: var(--danger-bg);
  color: var(--danger);
}

.quizably-actions-menu__sep {
  height: 1px;
  background: var(--border-1);
  margin: 4px 6px;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-actions-menu__toggle,
  .quizably-actions-menu__item {
    transition: none;
  }
}
</style>
