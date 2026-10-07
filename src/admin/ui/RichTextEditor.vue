<template>
  <div
    :class="['quizably-rte', { 'is-focused': focused, 'is-disabled': disabled, 'is-toolbarless': toolbarless }]"
    :style="minHeight ? { '--quizably-rte-min': minHeight } : undefined"
  >
    <!-- Static toolbar — shown in normal (non-toolbarless) mode -->
    <div
      v-if="editor && !toolbarless"
      class="quizably-rte__toolbar"
      role="toolbar"
      :aria-label="__('Formatting')"
    >
      <button
        v-for="b in toolbar"
        :key="b.name"
        type="button"
        :class="['quizably-rte__btn', { 'is-active': b.isActive() }]"
        :title="b.title"
        :aria-label="b.title"
        :aria-pressed="b.isActive()"
        :disabled="disabled"
        @click.prevent="b.run"
      >
        <component :is="b.icon" />
      </button>
    </div>

    <EditorContent
      :editor="editor"
      class="quizably-rte__content"
    />
  </div>

  <!-- Floating bubble toolbar — rendered in <body> via Teleport so it sits above
       everything. Visible only in toolbarless mode when text is selected. -->
  <Teleport to="body">
    <div
      v-if="toolbarless && bubbleVisible"
      class="quizably-bubble"
      :style="bubbleStyle"
      role="toolbar"
      :aria-label="__('Text formatting')"
    >
      <!-- Font size presets -->
      <div class="quizably-bubble__group">
        <button
          v-for="sz in FONT_SIZES"
          :key="sz.label"
          type="button"
          :class="['quizably-bubble__btn', 'quizably-bubble__sz', {
            'is-active': sz.value
              ? editor?.isActive('fontSize', { size: sz.value })
              : !editor?.isActive('fontSize'),
          }]"
          :title="sz.title"
          @mousedown.prevent="applyFontSize(sz.value)"
        >{{ sz.label }}</button>
      </div>
      <div class="quizably-bubble__sep" />
      <!-- Alignment -->
      <div class="quizably-bubble__group">
        <button type="button" :class="['quizably-bubble__btn', { 'is-active': editor?.isActive({ textAlign: 'left' }) }]" :title="__('Align left')" @mousedown.prevent="editor?.chain().focus().setTextAlign('left').run()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
        </button>
        <button type="button" :class="['quizably-bubble__btn', { 'is-active': editor?.isActive({ textAlign: 'center' }) }]" :title="__('Align center')" @mousedown.prevent="editor?.chain().focus().setTextAlign('center').run()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <button type="button" :class="['quizably-bubble__btn', { 'is-active': editor?.isActive({ textAlign: 'right' }) }]" :title="__('Align right')" @mousedown.prevent="editor?.chain().focus().setTextAlign('right').run()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
        </button>
      </div>
      <div class="quizably-bubble__sep" />
      <!-- Bold / Italic / Underline -->
      <div class="quizably-bubble__group">
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('bold') }]"
          :title="__('Bold (Ctrl+B)')"
          @mousedown.prevent="editor?.chain().focus().toggleBold().run()"
        ><BoldIcon /></button>
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('italic') }]"
          :title="__('Italic (Ctrl+I)')"
          @mousedown.prevent="editor?.chain().focus().toggleItalic().run()"
        ><ItalicIcon /></button>
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('underline') }]"
          :title="__('Underline (Ctrl+U)')"
          @mousedown.prevent="editor?.chain().focus().toggleUnderline().run()"
        ><UnderlineIcon /></button>
      </div>
      <div class="quizably-bubble__sep" />
      <!-- Lists / Blockquote / Link -->
      <div class="quizably-bubble__group">
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('bulletList') }]"
          :title="__('Bullet list')"
          @mousedown.prevent="editor?.chain().focus().toggleBulletList().run()"
        ><BulletListIcon /></button>
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('orderedList') }]"
          :title="__('Numbered list')"
          @mousedown.prevent="editor?.chain().focus().toggleOrderedList().run()"
        ><OrderedListIcon /></button>
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('blockquote') }]"
          :title="__('Quote')"
          @mousedown.prevent="editor?.chain().focus().toggleBlockquote().run()"
        ><QuoteIcon /></button>
        <button
          type="button"
          :class="['quizably-bubble__btn', { 'is-active': editor?.isActive('link') }]"
          :title="__('Link')"
          @mousedown.prevent="promptLink()"
        ><LinkIcon /></button>
      </div>
      <div class="quizably-bubble__sep" />
      <!-- Text colour -->
      <div class="quizably-bubble__group">
        <label class="quizably-bubble__color-btn" :title="__('Text color')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M9 7l6 10H3z"/><line x1="20" y1="17" x2="20" y2="22"/><line x1="17.5" y1="19.5" x2="22.5" y2="19.5"/></svg>
          <span class="quizably-bubble__color-swatch" :style="{ background: bubbleColor }" />
          <input type="color" class="quizably-bubble__color-input" :value="bubbleColor" @input="applyColor($event.target.value)">
        </label>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { Teleport, computed, h, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { Mark } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TextAlign } from '@tiptap/extension-text-align';
import { TextStyle, Color } from '@tiptap/extension-text-style';

// ── Custom FontSize mark ──────────────────────────────────────────────────────
const FontSize = Mark.create({
  name: 'fontSize',
  addAttributes() {
    return {
      size: {
        default: null,
        parseHTML: el => el.getAttribute('data-fs') || null,
        renderHTML: ({ size }) =>
          size ? { 'data-fs': size, style: `font-size: ${size}` } : {},
      },
    };
  },
  parseHTML() {
    return [{ tag: 'span[data-fs]' }];
  },
  renderHTML({ HTMLAttributes }) {
    return ['span', HTMLAttributes, 0];
  },
  addCommands() {
    return {
      setFontSize: size => ({ commands }) =>
        size ? commands.setMark(this.name, { size }) : commands.unsetMark(this.name),
    };
  },
});

const FONT_SIZES = [
  { label: 'S',  value: '13px', title: __('Small text')  },
  { label: 'M',  value: null,   title: __('Normal text')  },
  { label: 'L',  value: '18px', title: __('Large text')   },
  { label: 'XL', value: '22px', title: __('Heading-size') },
];

// ── Props & emits ─────────────────────────────────────────────────────────────
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  // Hides the static toolbar and shows a floating bubble menu on text selection.
  toolbarless: { type: Boolean, default: false },
  id: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
  // Minimum height of the editing area (any CSS length), e.g. '220px' for a
  // long-form body. The whole area stays clickable, not just the first line.
  minHeight: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const focused = ref(false);
const editor = ref(null);
const bubbleColor = ref('#000000');

// ── Bubble menu state ─────────────────────────────────────────────────────────
const bubbleVisible = ref(false);
const bubbleX = ref(0);
const bubbleY = ref(0);

const bubbleStyle = computed(() => ({
  position: 'fixed',
  top: `${bubbleY.value}px`,
  left: `${bubbleX.value}px`,
  transform: 'translateX(-50%)',
  zIndex: '99999',
}));

function updateBubble(ed) {
  if (!props.toolbarless || !ed) { bubbleVisible.value = false; return; }
  const { state, view } = ed;
  if (state.selection.empty) { bubbleVisible.value = false; return; }
  const { from, to } = state.selection;
  const start = view.coordsAtPos(from);
  const end   = view.coordsAtPos(to);
  const midX  = (start.left + end.right) / 2;
  const topY  = Math.min(start.top, end.top);
  // Keep bubble within viewport; 48px gap above selection
  bubbleX.value = Math.max(120, Math.min(window.innerWidth - 120, midX));
  bubbleY.value = Math.max(8, topY - 48);
  bubbleVisible.value = true;
}

function applyFontSize(size) {
  editor.value?.chain().focus().setFontSize(size).run();
}

function applyColor(color) {
  bubbleColor.value = color;
  editor.value?.chain().focus().setColor(color).run();
}

// ── Icon factories ────────────────────────────────────────────────────────────
const iconAttrs = {
  viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
  'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
  width: 13, height: 13,
};

const BoldIcon        = () => h('svg', iconAttrs, [h('path', { d: 'M6 4h8a4 4 0 0 1 0 8H6z' }), h('path', { d: 'M6 12h9a4 4 0 0 1 0 8H6z' })]);
const ItalicIcon      = () => h('svg', iconAttrs, [h('line', { x1: 19, y1: 4, x2: 10, y2: 4 }), h('line', { x1: 14, y1: 20, x2: 5, y2: 20 }), h('line', { x1: 15, y1: 4, x2: 9, y2: 20 })]);
const UnderlineIcon   = () => h('svg', iconAttrs, [h('path', { d: 'M6 4v6a6 6 0 0 0 12 0V4' }), h('line', { x1: 4, y1: 20, x2: 20, y2: 20 })]);
const BulletListIcon  = () => h('svg', iconAttrs, [h('line', { x1: 9, y1: 6, x2: 20, y2: 6 }), h('line', { x1: 9, y1: 12, x2: 20, y2: 12 }), h('line', { x1: 9, y1: 18, x2: 20, y2: 18 }), h('circle', { cx: 4.5, cy: 6, r: 1.2 }), h('circle', { cx: 4.5, cy: 12, r: 1.2 }), h('circle', { cx: 4.5, cy: 18, r: 1.2 })]);
const OrderedListIcon = () => h('svg', iconAttrs, [h('line', { x1: 10, y1: 6, x2: 20, y2: 6 }), h('line', { x1: 10, y1: 12, x2: 20, y2: 12 }), h('line', { x1: 10, y1: 18, x2: 20, y2: 18 }), h('path', { d: 'M4 6h1v-2' }), h('path', { d: 'M4 13.5c0-.75 1.5-1 1.5-2C5.5 11 5 10.5 4.5 10.5S3.5 11 3.5 11.5' }), h('path', { d: 'M3.5 16.5c0-.5.5-1 1-1s1 .5 1 1-1 .8-1 1.5h1' })]);
const QuoteIcon       = () => h('svg', iconAttrs, [h('path', { d: 'M3 7h4v4H4v-2c0-1 1-2 2-2H3zm10 0h4v4h-3v-2c0-1 1-2 2-2h-3z' }), h('path', { d: 'M5 13v4M15 13v4' })]);
const LinkIcon        = () => h('svg', iconAttrs, [h('path', { d: 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71' }), h('path', { d: 'M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71' })]);

// ── Static toolbar (non-toolbarless mode) ─────────────────────────────────────
const toolbar = [
  { name: 'bold',        title: __('Bold (Ctrl+B)'),  icon: BoldIcon,        isActive: () => editor.value?.isActive('bold'),        run: () => editor.value?.chain().focus().toggleBold().run() },
  { name: 'italic',      title: __('Italic (Ctrl+I)'), icon: ItalicIcon,     isActive: () => editor.value?.isActive('italic'),      run: () => editor.value?.chain().focus().toggleItalic().run() },
  { name: 'underline',   title: __('Underline (Ctrl+U)'), icon: UnderlineIcon, isActive: () => editor.value?.isActive('underline'), run: () => editor.value?.chain().focus().toggleUnderline().run() },
  { name: 'bulletList',  title: __('Bullet list'),   icon: BulletListIcon,  isActive: () => editor.value?.isActive('bulletList'),  run: () => editor.value?.chain().focus().toggleBulletList().run() },
  { name: 'orderedList', title: __('Numbered list'), icon: OrderedListIcon, isActive: () => editor.value?.isActive('orderedList'), run: () => editor.value?.chain().focus().toggleOrderedList().run() },
  { name: 'blockquote',  title: __('Quote'),          icon: QuoteIcon,       isActive: () => editor.value?.isActive('blockquote'), run: () => editor.value?.chain().focus().toggleBlockquote().run() },
  { name: 'link',        title: __('Link'),           icon: LinkIcon,        isActive: () => editor.value?.isActive('link'),       run: () => promptLink() },
];

function promptLink() {
  if (!editor.value) return;
  const previous = editor.value.getAttributes('link').href || '';
  const url = window.prompt(__('Link URL'), previous);
  if (url === null) return;
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    return;
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

// ── Editor lifecycle ──────────────────────────────────────────────────────────
onMounted(() => {
  editor.value = new Editor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
      // StarterKit (v3) already bundles Link and Underline; both are added below
      // with this editor's own settings, so switch the bundled copies off or each
      // would be registered twice (duplicate autolink / input rules).
      StarterKit.configure({ heading: false, codeBlock: false, horizontalRule: false, link: false, underline: false }),
      FontSize,
      Underline,
      Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' } }),
      Placeholder.configure({ placeholder: props.placeholder || '', emptyEditorClass: 'is-editor-empty', emptyNodeClass: 'is-empty' }),
      TextAlign.configure({ types: ['paragraph', 'heading'] }),
      TextStyle,
      Color,
    ],
    editorProps: {
      attributes: {
        class: 'quizably-rte__doc',
        ...(props.id ? { id: props.id } : {}),
        ...(props.ariaLabel ? { 'aria-label': props.ariaLabel } : {}),
        'data-placeholder': props.placeholder,
      },
    },
    onFocus() { focused.value = true; emit('focus'); },
    onBlur()  { focused.value = false; emit('blur'); bubbleVisible.value = false; },
    onSelectionUpdate({ editor: ed }) { updateBubble(ed); },
    onUpdate({ editor: ed }) {
      const html = ed.getHTML();
      emit('update:modelValue', html === '<p></p>' ? '' : html);
      updateBubble(ed);
    },
  });
});

watch(() => props.modelValue, (next) => {
  if (!editor.value) return;
  const current = editor.value.getHTML();
  if (current === next) return;
  if (next === '' && current === '<p></p>') return;
  editor.value.commands.setContent(next || '', { emitUpdate: false });
});

watch(() => props.disabled, (next) => { editor.value?.setEditable(!next); });

/**
 * Insert text at the cursor (replacing any selection) and keep focus in the
 * editor. For callers that offer "insert a placeholder" buttons.
 */
function insertText(text) {
  if (!editor.value || props.disabled) return;
  editor.value.chain().focus().insertContent(String(text)).run();
}

defineExpose({ insertText });

onBeforeUnmount(() => {
  editor.value?.destroy();
  editor.value = null;
  bubbleVisible.value = false;
});
</script>

<style scoped>
.quizably-rte {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  background: var(--bg-surface);
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.quizably-rte.is-focused { border-color: var(--brand); box-shadow: var(--shadow-focus); }
.quizably-rte.is-disabled { opacity: 0.65; cursor: not-allowed; }

/* ── Toolbarless / WYSIWYG mode ── */
.quizably-rte.is-toolbarless {
  border: none;
  background: transparent;
  border-radius: 0;
  width: 100%;
}

.quizably-rte.is-toolbarless.is-focused { box-shadow: none; }

.quizably-rte.is-toolbarless .quizably-rte__content {
  padding: 0;
  min-height: 24px;
  color: inherit;
  font-size: inherit;
  font-family: inherit;
  line-height: inherit;
}

/* ── Static toolbar ── */
.quizably-rte__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  padding: 6px;
  border-bottom: 1px solid var(--border-1);
  background: var(--bg-canvas);
  border-radius: var(--r-md) var(--r-md) 0 0;
}

.quizably-rte__btn {
  display: inline-grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.quizably-rte__btn:hover:not(:disabled):not(.is-active) { background: var(--bg-subtle); color: var(--ink-1); }
.quizably-rte__btn.is-active { background: var(--brand-tint); color: var(--brand); }
.quizably-rte__btn:focus-visible { outline: none; box-shadow: var(--shadow-focus); }
.quizably-rte__btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Editor content ── */
.quizably-rte__content {
  padding: 12px 14px;
  min-height: var(--quizably-rte-min, 90px);
  font-family: var(--f-sans);
  font-size: 14px;
  line-height: 1.6;
  color: var(--ink-1);
}

/* Content area minus its 12px top/bottom padding, so the whole area is editable. */
.quizably-rte :deep(.quizably-rte__doc) { outline: none; min-height: calc(var(--quizably-rte-min, 84px) - 24px); }
.quizably-rte :deep(.quizably-rte__doc p) { margin: 0 0 6px; }
.quizably-rte :deep(.quizably-rte__doc p:last-child) { margin-bottom: 0; }
.quizably-rte :deep(.quizably-rte__doc strong) { font-weight: 600; }
.quizably-rte :deep(.quizably-rte__doc em) { font-style: italic; }
.quizably-rte :deep(.quizably-rte__doc ul),
.quizably-rte :deep(.quizably-rte__doc ol) { margin: 4px 0 8px; padding-inline-start: 22px; }
.quizably-rte :deep(.quizably-rte__doc li) { margin-bottom: 2px; }
.quizably-rte :deep(.quizably-rte__doc blockquote) {
  margin: 6px 0;
  padding: 6px 14px;
  border-inline-start: 3px solid var(--border-3);
  color: var(--ink-2);
  background: var(--bg-subtle);
  border-start-start-radius: 0;
  border-start-end-radius: var(--r-sm);
  border-end-end-radius: var(--r-sm);
  border-end-start-radius: 0;
  font-style: italic;
}
.quizably-rte :deep(.quizably-rte__doc a) { color: var(--brand); text-decoration: underline; }

/* Placeholder */
.quizably-rte :deep(.quizably-rte__doc.is-editor-empty:first-child::before),
.quizably-rte :deep(.quizably-rte__doc p.is-empty:first-child::before) {
  content: attr(data-placeholder);
  float: inline-start;
  height: 0;
  pointer-events: none;
  color: var(--ink-4);
}
</style>

<!-- Bubble menu is Teleported to <body> so scoped styles won't reach it.
     Non-scoped block keeps these styles intentionally global. -->
<style>
.quizably-bubble {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 5px 7px;
  background: #1c1c1e;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 1px 3px rgba(0,0,0,.2);
  pointer-events: auto;
  user-select: none;
}

.quizably-bubble__group {
  display: flex;
  align-items: center;
  gap: 1px;
}

.quizably-bubble__sep {
  width: 1px;
  height: 16px;
  background: rgba(255,255,255,.15);
  margin: 0 4px;
  flex-shrink: 0;
}

.quizably-bubble__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  min-width: 26px;
  padding: 0 5px;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: rgba(255,255,255,.65);
  cursor: pointer;
  transition: background 100ms ease, color 100ms ease;
  line-height: 1;
}

.quizably-bubble__btn:hover { background: rgba(255,255,255,.1); color: #fff; }
.quizably-bubble__btn.is-active { background: rgba(255,255,255,.18); color: #fff; }
.quizably-bubble__btn svg { display: block; width: 13px; height: 13px; }

/* Font size labels */
.quizably-bubble__sz {
  font-family: ui-monospace, 'Courier New', monospace;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.03em;
  min-width: 20px;
}

/* Color picker button */
.quizably-bubble__color-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 2px;
  height: 26px;
  width: 30px;
  padding: 0 3px;
  border-radius: 5px;
  cursor: pointer;
  color: rgba(255,255,255,.65);
  transition: background 100ms, color 100ms;
}

.quizably-bubble__color-btn:hover {
  background: rgba(255,255,255,.1);
  color: #fff;
}

.quizably-bubble__color-swatch {
  display: block;
  width: 16px;
  height: 3px;
  border-radius: 2px;
  margin-top: -1px;
}

.quizably-bubble__color-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
</style>
