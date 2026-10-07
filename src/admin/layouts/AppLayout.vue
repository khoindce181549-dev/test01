<template>
  <div class="quizably-admin">
    <header class="quizably-admin__topbar">
      <a
        href="#/dashboard"
        class="quizably-admin__brand"
        :aria-label="__('Quizably — go to dashboard')"
      >
        <img
          :src="logoUrl"
          alt="Quizably"
          class="quizably-admin__brand-logo"
        >
      </a>
      <span class="quizably-admin__version">v{{ version }}</span>
    </header>
    <div class="quizably-admin__body">
      <aside
        class="quizably-admin__sidebar"
        :class="{ 'is-collapsed': !ui.sidebarOpen }"
        :aria-label="ui.sidebarOpen ? __('Primary navigation') : __('Primary navigation (collapsed)')"
      >
        <nav class="quizably-admin__nav">
          <div
            v-for="section in nav"
            :key="section.id"
            :class="['quizably-admin__navsection', `quizably-admin__navsection--${section.id}`]"
          >
            <span
              v-if="ui.sidebarOpen"
              class="quizably-admin__navsection-label"
            >
              {{ section.label }}
            </span>
            <RouterLink
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              class="quizably-admin__navitem"
              :title="ui.sidebarOpen ? null : item.label"
            >
              <span class="quizably-admin__navicon">
                <component :is="item.icon" />
              </span>
              <span class="quizably-admin__navlabel">{{ item.label }}</span>
              <Badge
                v-if="item.badge && ui.sidebarOpen"
                variant="pro"
                size="sm"
              >
                {{ item.badge }}
              </Badge>
            </RouterLink>
          </div>
        </nav>
        <div class="quizably-admin__sidebar-footer">
          <a
            v-if="ui.sidebarOpen"
            href="https://wordpress.org/plugins/quizably/"
            class="quizably-admin__footer-link"
            target="_blank"
            rel="noopener"
          >
            <svg
              viewBox="0 0 24 24"
              width="14"
              height="14"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle
                cx="12"
                cy="12"
                r="10"
              />
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
              <line
                x1="12"
                y1="17"
                x2="12.01"
                y2="17"
              />
            </svg>
            <span>{{ __('Help & docs') }}</span>
          </a>
          <button
            type="button"
            class="quizably-admin__collapse-btn quizably-admin__sidebar-toggle"
            :aria-label="ui.sidebarOpen ? __('Collapse sidebar') : __('Expand sidebar')"
            :aria-expanded="ui.sidebarOpen"
            :title="ui.sidebarOpen ? null : __('Expand sidebar')"
            @click="ui.toggleSidebar()"
          >
            <svg
              class="q-flip-rtl"
              viewBox="0 0 24 24"
              width="14"
              height="14"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline
                v-if="ui.sidebarOpen"
                points="15 18 9 12 15 6"
              />
              <polyline
                v-else
                points="9 18 15 12 9 6"
              />
            </svg>
            <span v-if="ui.sidebarOpen">{{ __('Collapse') }}</span>
          </button>
        </div>
      </aside>
      <main class="quizably-admin__main">
        <RouterView />
      </main>
    </div>
    <ToastStack />
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { h } from 'vue';
import { RouterLink, RouterView } from 'vue-router';
import { Badge, ToastStack } from '@admin/ui';
import { useUiStore } from '@admin/stores/ui';

const ui = useUiStore();
// QUIZABLY_ADMIN is populated server-side by Admin\Assets::enqueue().
// Fallback to 'dev' when the Vite dev server renders without PHP.
const version = window.QUIZABLY_ADMIN?.version ?? 'dev';
// Logo lives at assets/icons/quizably-logo.svg inside the plugin (a real
// Quizably wordmark, not the old plugin's branding baked into a raster PNG -
// vector also means it stays crisp at any sidebar/retina size). Resolve via
// QUIZABLY_ADMIN.pluginUrl (set in Admin/Assets.php) so the URL works under
// any wp-content path or multisite layout.
const pluginUrl = (window.QUIZABLY_ADMIN?.pluginUrl ?? '/wp-content/plugins/quizably/').replace(/\/?$/, '/');
const logoUrl = `${pluginUrl}assets/icons/quizably-logo.svg`;

/**
 * Icons are inline render functions rather than v-html strings so we
 * avoid triggering the vue/no-v-html lint rule and we keep Vue in
 * charge of the DOM. Swap for lucide-vue-next in Phase 4 if we settle
 * on a single icon set across the app.
 */
const iconProps = {
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': 2,
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
};

const DashboardIcon = () =>
  h('svg', iconProps, [
    h('rect', { x: 3, y: 3, width: 7, height: 9, rx: 1.5 }),
    h('rect', { x: 14, y: 3, width: 7, height: 5, rx: 1.5 }),
    h('rect', { x: 14, y: 12, width: 7, height: 9, rx: 1.5 }),
    h('rect', { x: 3, y: 16, width: 7, height: 5, rx: 1.5 }),
  ]);

const QuizzesIcon = () =>
  h('svg', iconProps, [
    h('rect', { x: 3, y: 4, width: 18, height: 16, rx: 2 }),
    h('path', { d: 'M7 9h10' }),
    h('path', { d: 'M7 13h7' }),
    h('path', { d: 'M7 17h4' }),
  ]);

const QuestionBankIcon = () =>
  h('svg', iconProps, [
    h('path', { d: 'M4 6a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z' }),
    h('path', { d: 'M9 12c0-1.1.9-2 2-2s2 .9 2 2-2 1.5-2 3' }),
    h('line', { x1: 11, y1: 18, x2: 11.01, y2: 18 }),
  ]);

const LeadsIcon = () =>
  h('svg', iconProps, [
    h('path', { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }),
    h('circle', { cx: 9, cy: 7, r: 4 }),
    h('path', { d: 'M23 21v-2a4 4 0 0 0-3-3.87' }),
    h('path', { d: 'M16 3.13a4 4 0 0 1 0 7.75' }),
  ]);

const IntegrationsIcon = () =>
  h('svg', iconProps, [
    h('polyline', { points: '16 18 22 12 16 6' }),
    h('polyline', { points: '8 6 2 12 8 18' }),
  ]);

const SettingsIcon = () =>
  h('svg', iconProps, [
    h('circle', { cx: 12, cy: 12, r: 3 }),
    h('path', {
      d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z',
    }),
  ]);

// Sidebar is grouped into four sections — each gets its own accent hue
// (see `.quizably-admin__navsection--{id}` rules) so the active item reads as
// "you are in this area" rather than as one undifferentiated list. The
// Templates entry is intentionally omitted: that picker lives in New Quiz
// modal step 2, the Quiz Builder Design tab, and Settings → Defaults.
const nav = [
  {
    id: 'overview',
    label: __('Overview'),
    items: [
      { to: '/dashboard', label: __('Dashboard'), icon: DashboardIcon },
    ],
  },
  {
    id: 'build',
    label: __('Build'),
    items: [
      { to: '/quizzes', label: __('Quizzes'), icon: QuizzesIcon },
      { to: '/question-bank', label: __('Question Bank'), icon: QuestionBankIcon },
    ],
  },
  {
    id: 'engage',
    label: __('Engage'),
    items: [
      { to: '/leads', label: __('Leads'), icon: LeadsIcon },
      { to: '/integrations', label: __('Integrations'), icon: IntegrationsIcon },
    ],
  },
  {
    id: 'system',
    label: __('System'),
    items: [
      { to: '/settings', label: __('Settings'), icon: SettingsIcon },
    ],
  },
];
</script>

<style scoped>
.quizably-admin {
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 32px);
}

.quizably-admin__topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 32px;
  border-bottom: 1px solid var(--border-1);
  background: var(--bg-surface);
  position: sticky;
  top: 32px;
  z-index: 10;
}

.quizably-admin__brand {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  border-radius: var(--r-sm);
  padding: 4px 6px;
  margin: -4px -6px;
  transition: background 120ms;
}

.quizably-admin__brand:hover {
  background: var(--bg-canvas);
}

.quizably-admin__brand:focus-visible {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
}

/* Image-rule note (CLAUDE.md): always object-fit: contain on plugin
   shipped images so the artwork is never cropped. The logo is a wide
   horizontal lockup, so we cap the height and let the width auto. */
.quizably-admin__brand-logo {
  display: block;
  height: 26px;
  width: auto;
  max-width: 220px;
  object-fit: contain;
  background: transparent;
}

.quizably-admin__version {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-4);
}

.quizably-admin__body {
  display: flex;
  flex: 1;
  min-height: 0;
}

.quizably-admin__sidebar {
  position: relative;
  width: 240px;
  padding: 20px 14px 16px;
  border-inline-end: 1px solid var(--border-1);
  background:
    linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-canvas) 280px);
  transition: width 200ms ease, padding 200ms ease;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.quizably-admin__sidebar.is-collapsed {
  width: 72px;
  padding-inline-start: 10px;
  padding-inline-end: 10px;
}

.quizably-admin__sidebar.is-collapsed .quizably-admin__navlabel,
.quizably-admin__sidebar.is-collapsed .quizably-admin__navsection-label {
  display: none;
}

.quizably-admin__collapse-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 12px;
  border: 0;
  border-radius: var(--r-sm);
  background: transparent;
  color: var(--ink-3);
  cursor: pointer;
  font-family: var(--f-sans);
  font-size: 12.5px;
  font-weight: 500;
  text-align: start;
  transition: background 150ms, color 150ms;
}

.quizably-admin__collapse-btn:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-admin__collapse-btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  color: var(--brand);
}

.quizably-admin__sidebar.is-collapsed .quizably-admin__collapse-btn {
  justify-content: center;
  padding-inline-start: 0;
  padding-inline-end: 0;
}

.quizably-admin__nav {
  display: flex;
  flex-direction: column;
  gap: 18px;
  flex: 1;
}

.quizably-admin__navsection {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.quizably-admin__navsection-label {
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--ink-4);
  padding: 0 12px 6px;
}

.quizably-admin__navitem {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding-block: 9px 9px;
  padding-inline: 14px 12px;
  border-radius: var(--r-md);
  font-family: var(--f-sans);
  font-size: 13.5px;
  font-weight: 500;
  color: var(--ink-2);
  transition:
    background 150ms ease,
    color 150ms ease,
    box-shadow 150ms ease;
  text-decoration: none;
}

/* Soft 3px accent rail anchored to the left edge — fades in on
   hover/active, color set per section below. */
.quizably-admin__navitem::before {
  content: '';
  position: absolute;
  inset-inline-start: 4px;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 0;
  border-radius: var(--r-pill);
  background: var(--ink-4);
  opacity: 0;
  transition: height 150ms ease, opacity 150ms ease, background 150ms ease;
}

.quizably-admin__navitem:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-admin__navitem:hover::before {
  height: 14px;
  opacity: 0.7;
}

.quizably-admin__navitem:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.quizably-admin__navitem.router-link-active {
  background: var(--brand-bg);
  color: var(--brand-hover);
  font-weight: 600;
}

.quizably-admin__navitem.router-link-active::before {
  height: 22px;
  opacity: 1;
  background: var(--brand);
}

.quizably-admin__navitem.router-link-active .quizably-admin__navicon {
  color: var(--brand);
}

/* Per-section accent hues — Overview = brand indigo (default),
   Build = teal, Engage = coral, System = neutral. Each tints both
   the active rail and the icon when the user lands in that area, so
   the sidebar reads at a glance without overpowering the layout. */
.quizably-admin__navsection--overview .quizably-admin__navitem.router-link-active {
  background: var(--brand-bg);
  color: var(--brand-hover);
}
.quizably-admin__navsection--overview .quizably-admin__navitem.router-link-active::before { background: var(--brand); }
.quizably-admin__navsection--overview .quizably-admin__navitem.router-link-active .quizably-admin__navicon { color: var(--brand); }
.quizably-admin__navsection--overview .quizably-admin__navitem:hover::before { background: var(--brand); }

.quizably-admin__navsection--build .quizably-admin__navitem.router-link-active {
  background: #ECFDF5;
  color: #047857;
}
.quizably-admin__navsection--build .quizably-admin__navitem.router-link-active::before { background: #10B981; }
.quizably-admin__navsection--build .quizably-admin__navitem.router-link-active .quizably-admin__navicon { color: #10B981; }
.quizably-admin__navsection--build .quizably-admin__navitem:hover::before { background: #10B981; }

.quizably-admin__navsection--engage .quizably-admin__navitem.router-link-active {
  background: #FEF3F2;
  color: #B23B2C;
}
.quizably-admin__navsection--engage .quizably-admin__navitem.router-link-active::before { background: #F97066; }
.quizably-admin__navsection--engage .quizably-admin__navitem.router-link-active .quizably-admin__navicon { color: #F97066; }
.quizably-admin__navsection--engage .quizably-admin__navitem:hover::before { background: #F97066; }

.quizably-admin__navsection--system .quizably-admin__navitem.router-link-active {
  background: var(--bg-muted);
  color: var(--ink-1);
}
.quizably-admin__navsection--system .quizably-admin__navitem.router-link-active::before { background: var(--ink-2); }
.quizably-admin__navsection--system .quizably-admin__navitem.router-link-active .quizably-admin__navicon { color: var(--ink-1); }
.quizably-admin__navsection--system .quizably-admin__navitem:hover::before { background: var(--ink-3); }

.quizably-admin__navicon {
  display: inline-flex;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  color: var(--ink-3);
  transition: color 150ms ease;
}

.quizably-admin__navitem:hover .quizably-admin__navicon {
  color: var(--ink-1);
}

.quizably-admin__navicon :deep(svg) {
  width: 18px;
  height: 18px;
}

.quizably-admin__sidebar.is-collapsed .quizably-admin__navitem {
  justify-content: center;
  padding-inline-start: 12px;
  padding-inline-end: 12px;
}

.quizably-admin__sidebar.is-collapsed .quizably-admin__navitem::before {
  inset-inline-start: 0;
}

.quizably-admin__sidebar-footer {
  padding-top: 12px;
  border-top: 1px solid var(--border-1);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.quizably-admin__footer-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: var(--r-sm);
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-3);
  text-decoration: none;
  transition: background 150ms, color 150ms;
}

.quizably-admin__footer-link:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-admin__main {
  flex: 1;
  padding: 32px;
  overflow-y: auto;
  min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-admin__sidebar,
  .quizably-admin__collapse-btn,
  .quizably-admin__navitem,
  .quizably-admin__navitem::before,
  .quizably-admin__navicon,
  .quizably-admin__footer-link {
    transition: none;
  }
}

/* Below tablet width the full 240px sidebar leaves too little room for
   content — force the same icon-only rail the manual collapse toggle
   already produces, regardless of the user's stored sidebarOpen state. */
@media (max-width: 900px) {
  .quizably-admin__topbar {
    padding: 12px 16px;
  }

  .quizably-admin__main {
    padding: 16px;
  }

  .quizably-admin__sidebar {
    width: 72px;
    padding-inline-start: 10px;
    padding-inline-end: 10px;
  }

  .quizably-admin__sidebar .quizably-admin__navlabel,
  .quizably-admin__sidebar .quizably-admin__navsection-label,
  .quizably-admin__sidebar .quizably-badge,
  .quizably-admin__sidebar .quizably-admin__footer-link span,
  .quizably-admin__sidebar .quizably-admin__collapse-btn span {
    display: none;
  }

  .quizably-admin__sidebar .quizably-admin__navitem {
    justify-content: center;
    padding-inline-start: 12px;
    padding-inline-end: 12px;
  }

  .quizably-admin__sidebar .quizably-admin__navitem::before {
    inset-inline-start: 0;
  }

  .quizably-admin__sidebar .quizably-admin__collapse-btn {
    justify-content: center;
    padding-inline-start: 0;
    padding-inline-end: 0;
  }
}

@media (max-width: 600px) {
  .quizably-admin__version {
    display: none;
  }
}
</style>
