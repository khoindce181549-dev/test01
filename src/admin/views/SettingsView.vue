<template>
  <div class="settings-view">
    <!-- Page top: title + autosave pill on the right. -->
    <header class="settings-view__head">
      <div class="settings-view__lead">
        <span
          v-if="activeSection.icon"
          class="settings-view__icon"
          aria-hidden="true"
        >
          <Icon
            :name="activeSection.icon"
            :size="20"
          />
        </span>
        <div class="settings-view__heading">
          <h1 class="settings-view__title">{{ activeSection.label }}</h1>
          <p
            v-if="activeSection.description"
            class="settings-view__sub"
          >{{ activeSection.description }}</p>
        </div>
      </div>
      <div
        class="settings-view__status"
        aria-live="polite"
      >
        <template v-if="isSaving">
          <span
            class="settings-view__dot settings-view__dot--saving"
            aria-hidden="true"
          />
          <span>{{ __('Saving…') }}</span>
        </template>
        <template v-else-if="recentlySaved">
          <span
            class="settings-view__dot"
            aria-hidden="true"
          />
          <span>{{ savedLabel }}</span>
        </template>
        <template v-else-if="loading">
          <span
            class="settings-view__spinner"
            aria-hidden="true"
          />
          <span>{{ __('Loading…') }}</span>
        </template>
      </div>
    </header>

    <!-- Top horizontal tab strip — flat tabs with an underline on active. -->
    <nav
      class="settings-tabs"
      :aria-label="__('Settings sections')"
    >
      <ul class="settings-tabs__list">
        <li
          v-for="s in flatSections"
          :key="s.id"
        >
          <a
            :href="`#${s.id}`"
            :class="['settings-tabs__item', 'settings-nav__item', { 'is-active': activeId === s.id, 'is-danger': s.id === 'danger' }]"
            @click.prevent="onNavClick(s.id)"
          ><span class="settings-nav__label">{{ s.label }}</span></a>
        </li>
      </ul>
    </nav>

    <div class="settings-view__content">
      <div
        v-if="loading"
        class="settings-view__loading"
      >
        <span
          class="settings-view__spinner"
          aria-hidden="true"
        />
        <span>{{ __('Loading settings…') }}</span>
      </div>

      <template v-else>
          <!-- Branding -->
          <section
            v-if="activeId === 'branding'"
            id="branding"
            class="settings-section"
            data-section-id="branding"
          >
            <SettingsCard
              icon="droplet"
              :title="__('Colors')"
              :description="__('The palette new quizzes start with. Each quiz can still change it in its Design tab.')"
            >
              <SettingsRow
                :title="__('Brand color')"
                :description="__('Used for buttons, links, and progress indicators in new quizzes.')"
                inline
              >
                <div class="settings-color">
                  <input
                    id="quizably-brand-color"
                    type="color"
                    class="settings-color__picker"
                    :value="draft.branding_primary_color || '#4F46E5'"
                    @input="(e) => scheduleSave('branding_primary_color', e.target.value)"
                  >
                  <code>{{ draft.branding_primary_color || '#4F46E5' }}</code>
                </div>
              </SettingsRow>

              <SettingsRow
                :title="__('Accent color')"
                :description="__('Used for highlights and secondary callouts in new quizzes.')"
                inline
              >
                <div class="settings-color">
                  <input
                    id="quizably-accent-color"
                    type="color"
                    class="settings-color__picker"
                    :value="draft.branding_accent_color || '#F59E0B'"
                    @input="(e) => scheduleSave('branding_accent_color', e.target.value)"
                  >
                  <code>{{ draft.branding_accent_color || '#F59E0B' }}</code>
                </div>
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- Defaults -->
          <section
            v-else-if="activeId === 'defaults'"
            id="defaults"
            class="settings-section"
            data-section-id="defaults"
          >
            <SettingsCard
              icon="layout"
              :title="__('Look & feel')"
              :description="__('What a newly created quiz starts with. Each quiz can change these afterwards.')"
            >
              <SettingsRow
                :title="__('Default template')"
                :description="__('Pre-selected on the New Quiz modal.')"
              >
                <Select
                  :model-value="draft.default_template"
                  :options="templateOptions"
                  :placeholder="templateOptions.length ? __('Select a template') : __('Loading…')"
                  label=""
                  @update:model-value="(v) => saveImmediate('default_template', v)"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Default font')"
                :description="__('Typeface new quizzes start with — change it per quiz in the Design tab.')"
              >
                <Select
                  :model-value="draft.defaults_font_family || ''"
                  :options="defaultFontOptions"
                  label=""
                  @update:model-value="(v) => saveImmediate('defaults_font_family', v)"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Default button shape')"
                :description="__('Applied to new quizzes — change it per quiz in the Design tab.')"
              >
                <Select
                  :model-value="buttonStyleFor(draft.defaults_button_radius)"
                  :options="buttonStyleOptions"
                  label=""
                  @update:model-value="(v) => saveImmediate('defaults_button_radius', v)"
                />
              </SettingsRow>
            </SettingsCard>

            <SettingsCard
              icon="mail"
              :title="__('Lead form')"
              :description="__('Where the lead-capture form appears in new quizzes.')"
            >
              <SettingsRow
                :title="__('Default form position')"
                :description="__('Where the lead-capture form appears in newly created quizzes.')"
              >
                <div class="settings-placement">
                  <Radio
                    v-for="p in placementOptions"
                    :key="p.value"
                    :model-value="canonicalPlacement(draft.default_optin_placement || 'none')"
                    :value="p.value"
                    name="quizably-default-placement"
                    @update:model-value="(v) => saveImmediate('default_optin_placement', v)"
                  >
                    {{ p.label }}
                  </Radio>
                </div>
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- Notifications -->
          <section
            v-else-if="activeId === 'notifications'"
            id="notifications"
            class="settings-section"
            data-section-id="notifications"
          >
            <SettingsCard
              icon="bell"
              :title="__('Email alerts')"
              :description="__('Choose what you are emailed about, and where it goes.')"
            >
              <SettingsRow
                :title="__('Email me on new submissions')"
                :description="__('One message per quiz completion, sent to the recipient below.')"
                inline
              >
                <Toggle
                  :model-value="boolFor('notifications_email_on_submission')"
                  @update:model-value="(v) => saveImmediate('notifications_email_on_submission', v)"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Email me on new leads')"
                :description="__('Triggers when a quiz captures a name or email via a form.')"
                inline
              >
                <Toggle
                  :model-value="boolFor('notifications_email_on_lead')"
                  @update:model-value="(v) => saveImmediate('notifications_email_on_lead', v)"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Notifications recipient')"
                :description="__('Defaults to the WP admin email if left empty.')"
              >
                <Input
                  :model-value="draft.notifications_recipient"
                  type="email"
                  label=""
                  :placeholder="adminEmail"
                  @update:model-value="(v) => scheduleSave('notifications_recipient', v)"
                  @blur="flushSave"
                />
              </SettingsRow>

            </SettingsCard>
          </section>

          <!-- Email -->
          <section
            v-else-if="activeId === 'email'"
            id="email"
            class="settings-section"
            data-section-id="email"
          >
            <SettingsCard
              icon="send"
              :title="__('Sender')"
              :description="__('Who outbound quiz email appears to come from.')"
            >
              <SettingsRow
                :title="__('From name')"
                :description="__('Display name shown on outbound email.')"
              >
                <Input
                  :model-value="draft.email_from_name"
                  label=""
                  :placeholder="siteTitle"
                  @update:model-value="(v) => scheduleSave('email_from_name', v)"
                  @blur="flushSave"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('From address')"
                :description="__('The address recipients see in their inbox.')"
              >
                <Input
                  :model-value="draft.email_from_address"
                  type="email"
                  label=""
                  :placeholder="adminEmail"
                  @update:model-value="(v) => scheduleSave('email_from_address', v)"
                  @blur="flushSave"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Reply-To matches lead email')"
                :description="__('Lets the admin reply directly to the user who submitted the quiz.')"
                inline
              >
                <Toggle
                  :model-value="boolFor('email_reply_to_lead')"
                  @update:model-value="(v) => saveImmediate('email_reply_to_lead', v)"
                />
              </SettingsRow>
            </SettingsCard>

            <SettingsCard
              icon="file-text"
              :title="__('Alert message')"
              :description="__('The wording of the new-submission email sent to you.')"
            >
              <SettingsRow
                :title="__('Submission email subject')"
                :description="__('Subject line for new-submission alerts. Tokens: {quiz_title}, {site_title}.')"
              >
                <Input
                  :model-value="draft.email_template_subject"
                  label=""
                  :placeholder="__('New submission: {quiz_title}')"
                  @update:model-value="(v) => scheduleSave('email_template_subject', v)"
                  @blur="flushSave"
                />
              </SettingsRow>

              <SettingsRow
                :title="__('Submission email body')"
                :description="__('Write the email the way you want it to look. Click a detail below to drop it in at the cursor; it is filled in with the real value when the email is sent.')"
              >
                <RichTextEditor
                  ref="emailBodyEditor"
                  :model-value="emailBodyHtml"
                  :placeholder="__('A new submission landed for {quiz_title}.')"
                  :aria-label="__('Submission email body')"
                  min-height="220px"
                  @update:model-value="onEmailBodyChange"
                  @blur="flushSave"
                />
                <div
                  class="settings-tokens"
                  role="group"
                  :aria-label="__('Insert a detail into the email')"
                >
                  <button
                    v-for="t in EMAIL_TOKENS"
                    :key="t.token"
                    type="button"
                    class="settings-token"
                    :title="t.hint"
                    @mousedown.prevent
                    @click="insertEmailToken(t.token)"
                  >
                    {{ t.token }}
                  </button>
                </div>
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- Privacy -->
          <section
            v-else-if="activeId === 'privacy'"
            id="privacy"
            class="settings-section"
            data-section-id="privacy"
          >
            <SettingsCard
              icon="shield"
              :title="__('Data handling')"
              :description="__('How visitor data is stored.')"
            >
              <SettingsRow
                :title="__('Data baseline')"
                :description="__('All IPs are SHA-256 hashed before storage. The plain-text IP is never written to disk.')"
                inline
              >
                <span class="settings-readonly-pill">{{ __('Always on') }}</span>
              </SettingsRow>
            </SettingsCard>

            <SettingsCard
              icon="check-square"
              :title="__('Consent')"
              :description="__('The checkbox wording new quiz forms start with.')"
            >
              <SettingsRow
                :title="__('Default consent text')"
                :description="__('Copied into new quizzes that have a form (see Defaults > Default form position); each quiz can still change it.')"
              >
                <Textarea
                  :model-value="draft.gdpr_default_consent_text"
                  label=""
                  :placeholder="__('I agree to receive follow-up emails about my results.')"
                  :rows="3"
                  @update:model-value="(v) => scheduleSave('gdpr_default_consent_text', v)"
                  @blur="flushSave"
                />
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- Roles & access -->
          <section
            v-else-if="activeId === 'roles'"
            id="roles"
            class="settings-section"
            data-section-id="roles"
          >
            <SettingsCard
              icon="users"
              :title="__('Built-in roles')"
              :description="__('What each WordPress role can already do with quizzes.')"
            >
              <div class="roles-grid">
                <article
                  v-for="role in rolePermissions"
                  :key="role.role"
                  :class="['role-card', `role-card--${role.role}`, { 'is-elevated': role.canManage }]"
                >
                  <div class="role-card__top">
                    <span
                      class="role-card__avatar"
                      aria-hidden="true"
                    >
                      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21a8 8 0 0 1 16 0" />
                      </svg>
                    </span>
                    <div class="role-card__heading">
                      <h3 class="role-card__name">{{ role.label }}</h3>
                      <span class="role-card__role">{{ role.role }}</span>
                    </div>
                    <Badge
                      :variant="role.canManage ? 'success' : 'neutral'"
                      size="sm"
                      class="role-card__badge"
                    >
                      {{ role.canManage ? __('Can manage') : __('Read-only') }}
                    </Badge>
                  </div>
                  <p class="role-card__caps">{{ role.caps }}</p>
                </article>
              </div>
            </SettingsCard>
          </section>

          <!-- Integrations -->
          <section
            v-else-if="activeId === 'integrations'"
            id="integrations"
            class="settings-section"
            data-section-id="integrations"
          >
            <SettingsCard
              icon="link"
              :title="__('Connections')"
              :description="__('Send quiz results to the other tools you use.')"
            >
              <SettingsRow
                :title="__('Manage integrations')"
                :description="integrationsDescription"
                inline
              >
                <span class="settings-readonly-pill">{{ __('Use Integrations tab') }}</span>
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- License (Pro only) -->
          <section
            v-else-if="activeId === 'license' && isProUser"
            id="license"
            class="settings-section"
            data-section-id="license"
          >
            <SettingsCard
              icon="key"
              :title="__('License key')"
              :description="__('Your Pro license status and key.')"
            >
              <SettingsRow
                :title="__('License status')"
                :description="__('Your Quizably Pro license key status.')"
                inline
              >
                <template v-if="licenseLoading">
                  <span class="settings-view__spinner" aria-hidden="true" />
                </template>
                <template v-else-if="licenseStatus">
                  <Badge
                    :variant="licenseStatus.has_key && licenseStatus.valid ? 'success' : 'neutral'"
                    size="sm"
                  >
                    {{ licenseStatus.has_key ? (licenseStatus.valid ? __('Active') : __('Invalid')) : __('Not entered') }}
                  </Badge>
                </template>
                <template v-else>
                  <Badge variant="neutral" size="sm">{{ __('Not entered') }}</Badge>
                </template>
              </SettingsRow>

              <SettingsRow
                v-if="licenseStatus && licenseStatus.has_key"
                :title="__('Current key')"
                :description="__('The stored key is masked for security.')"
                inline
              >
                <code class="settings-license__masked">{{ licenseStatus.key }}</code>
              </SettingsRow>

              <SettingsRow
                :title="__('Enter or update license key')"
                :description="__('Paste your license key from your Quizably Pro purchase receipt. Keys must be at least 20 characters.')"
              >
                <div class="settings-license__row">
                  <Input
                    v-model="licenseKey"
                    ltr
                    label=""
                    :placeholder="__('Paste your license key here…')"
                    type="text"
                    autocomplete="off"
                  />
                  <Button
                    :loading="licenseSaving"
                    :disabled="!licenseKey.trim()"
                    @click="saveLicense"
                  >
                    {{ __('Save key') }}
                  </Button>
                </div>
              </SettingsRow>
            </SettingsCard>
          </section>

          <!-- Danger zone -->
          <section
            v-else-if="activeId === 'danger'"
            id="danger"
            class="settings-section settings-section--danger"
            data-section-id="danger"
          >
            <SettingsCard
              icon="alert-triangle"
              :title="__('Reset')"
              :description="__('Irreversible. Your quizzes, questions and leads are never touched.')"
              tone="danger"
            >
              <SettingsRow
                :title="__('Reset all settings')"
                :description="resetDescription"
                inline
              >
                <Button
                  variant="danger"
                  :loading="resetting"
                  @click="onReset"
                >
                  {{ __('Reset all') }}
                </Button>
              </SettingsRow>
            </SettingsCard>
          </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue';
import {
  Badge,
  Button,
  Icon,
  Input,
  Radio,
  RichTextEditor,
  Select,
  Textarea,
  Toggle,
  useToast,
} from '@admin/ui';
import { useSettingsStore } from '@admin/stores/settings';
import { useTemplatesStore } from '@admin/stores/templates';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';
import { api } from '@admin/api/client.js';
import { canonicalPlacement } from '@shared/formPlacement.js';
import SettingsCard from './settings/SettingsCard.vue';
import SettingsRow from './settings/SettingsRow.vue';
import { EMAIL_TOKENS, bodyToEditorHtml } from '@admin/utils/emailBody.js';

const AUTOSAVE_DEBOUNCE = 600;
const SAVED_PILL_DURATION = 5000;

const settingsStore = useSettingsStore();
const templatesStore = useTemplatesStore();
const toast = useToast();

const loading = ref(false);
const resetting = ref(false);
const isSaving = ref(false);

// --- License (Pro only) --------------------------------------------------
const licenseKey = ref('');
const licenseStatus = ref(null); // null = not fetched, { has_key, valid, key }
const licenseLoading = ref(false);
const licenseSaving = ref(false);
const lastSavedAt = ref(0);
const now = ref(Date.now());

// Tick once a second so "Saved 3s ago" stays current without re-rendering
// the whole tree. Cheap because the only readers are two computed strings.
let tickTimer = null;
function startTick() {
  if (tickTimer) return;
  tickTimer = setInterval(() => {
    now.value = Date.now();
  }, 1000);
}
function stopTick() {
  if (tickTimer) {
    clearInterval(tickTimer);
    tickTimer = null;
  }
}

const recentlySaved = computed(() => {
  if (!lastSavedAt.value) return false;
  return now.value - lastSavedAt.value < SAVED_PILL_DURATION;
});

const savedLabel = computed(() => {
  if (!lastSavedAt.value) return '';
  const diff = Math.max(0, Math.round((now.value - lastSavedAt.value) / 1000));
  // translators: %d is the number of seconds since the last autosave.
  return diff < 2 ? __('Saved') : sprintf(__('Saved %ds ago'), diff);
});

/**
 * Local draft mirrors the store — lets us drive debounced autosave per
 * key without re-rendering the whole form on every keystroke.
 */
const DEFAULT_DRAFT = {
  // Existing scalar settings.
  // Colours, typeface and button shape are empty until chosen: empty means "the
  // builder's own default", and Reset clears them back to that.
  default_template: '',
  default_optin_placement: 'none',
  branding_primary_color: '',
  branding_accent_color: '',
  defaults_font_family: '',
  defaults_button_radius: '',
  gdpr_default_consent_text: '',
  // Notifications.
  notifications_email_on_submission: '0',
  notifications_email_on_lead: '0',
  notifications_recipient: '',
  // Email.
  email_from_name: '',
  email_from_address: '',
  email_reply_to_lead: '0',
  email_template_subject: '',
  email_template_body: '',
};

const draft = reactive({ ...DEFAULT_DRAFT });

// The email body is edited as HTML. A template saved as plain text (before the
// editor existed) is shown as paragraphs; it is only rewritten as HTML if edited.
const emailBodyEditor = ref(null);
const emailBodyHtml = computed(() => bodyToEditorHtml(draft.email_template_body));

function hydrate() {
  const map = settingsStore.map || {};
  for (const key of Object.keys(DEFAULT_DRAFT)) {
    draft[key] = map[key] ?? DEFAULT_DRAFT[key];
  }
}

function boolFor(key, fallback = false) {
  const value = draft[key];
  if (value === undefined || value === null || value === '') {
    return fallback;
  }
  if (typeof value === 'boolean') return value;
  const v = String(value).toLowerCase();
  return v === '1' || v === 'true' || v === 'yes' || v === 'on';
}

const adminEmail = computed(() => window.QUIZABLY_ADMIN?.adminEmail || 'admin@example.com');
const siteTitle = computed(() => window.QUIZABLY_ADMIN?.siteTitle || __('My Site'));

const resetDescription = __("Clears every global default back to the plugin's out-of-the-box values. Your quizzes, questions, leads, and submissions stay intact.");

const placementOptions = [
  { value: 'start', label: __('Before the quiz') },
  { value: 'end', label: __('End of the quiz') },
  { value: 'none', label: __('Off — never collect') },
];

// Only the typefaces the quiz builder's Typography control offers, so a default
// chosen here is always one the builder can show and edit.
const defaultFontOptions = [
  { value: '', label: __('Default — no override') },
  { value: 'geist', label: 'Geist' },
  { value: 'inter', label: 'Inter' },
  { value: 'system', label: __('System Sans-Serif') },
  { value: 'georgia', label: 'Georgia' },
  { value: 'courier', label: 'Courier' },
];

// The builder's button shapes (Design tab). An earlier version of this screen
// saved a pixel radius; buttonStyleFor() reads those as the nearest shape.
const buttonStyleOptions = [
  { value: 'rounded', label: __('Rounded') },
  { value: 'pill', label: __('Pill') },
  { value: 'sharp', label: __('Sharp') },
];

function buttonStyleFor(raw) {
  const v = String(raw ?? '').trim().toLowerCase();
  if (['rounded', 'pill', 'sharp'].includes(v)) return v;
  if (/^\d+$/.test(v)) return Number(v) === 0 ? 'sharp' : 'rounded';
  return 'rounded';
}

const templateOptions = computed(() => [
  { value: '', label: __('No default — prompt on each new quiz') },
  ...templatesStore.items
    .filter((t) => !t.pro)
    .map((t) => ({
      value: String(t.id || t.slug),
      label: t.name || String(t.id || t.slug),
    })),
]);

const rolePermissions = [
  {
    role: 'administrator',
    label: __('Administrator'),
    canManage: true,
    caps: __('Full access — including settings, integrations, and dangerous actions.'),
  },
  {
    role: 'editor',
    label: __('Editor'),
    canManage: true,
    caps: __('Can create and edit quizzes; cannot edit settings or delete quizzes.'),
  },
  {
    role: 'author',
    label: __('Author'),
    canManage: false,
    caps: __('Read-only — no access to quizzes, leads or settings.'),
  },
];

/**
 * Sidebar nav is grouped by category. The flat list is computed for the
 * mobile tab strip and to validate hash deep-links.
 */
const sectionGroups = computed(() => {
  const groups = [
    {
      label: __('General'),
      items: [
        { id: 'branding', icon: 'droplet', label: __('Branding'), description: __('The colours new quizzes start with. Each quiz can still change them.') },
        { id: 'defaults', icon: 'sliders', label: __('Defaults'), description: __('Starting values for new quizzes — editable per quiz afterward.') },
      ],
    },
    {
      label: __('Communications'),
      items: [
        { id: 'notifications', icon: 'bell', label: __('Notifications'), description: __('Email alerts when quizzes capture submissions or new leads.') },
        { id: 'email', icon: 'mail', label: __('Email'), description: __('Sender identity and message templates for transactional quiz emails.') },
      ],
    },
    {
      label: __('Privacy & access'),
      items: [
        { id: 'privacy', icon: 'shield', label: __('Privacy'), description: __('Baseline data handling and the default consent text shown to your visitors.') },
        { id: 'roles', icon: 'users', label: __('Roles & access'), description: __('Capability mapping for the built-in WordPress roles.') },
      ],
    },
    {
      label: __('Operations'),
      items: [
        { id: 'integrations', icon: 'link', label: __('Integrations'), description: __('Global settings for third-party service connections.') },
      ],
    },
    {
      label: __('Danger zone'),
      items: [
        { id: 'danger', icon: 'alert-triangle', label: __('Danger zone'), description: __('Irreversible actions that affect every saved global setting.') },
      ],
    },
  ];

  if (isPro()) {
    groups.push({
      label: __('Pro'),
      items: [
        { id: 'license', icon: 'key', label: __('License'), description: __('Manage your Quizably Pro license key.') },
      ],
    });
  }

  return groups;
});

const flatSections = computed(() =>
  sectionGroups.value.flatMap((g) => g.items)
);

const knownIds = computed(() => new Set(flatSections.value.map((s) => s.id)));
const activeId = ref(flatSections.value[0].id);

// Used by the right pane's title block — derived from the sidebar nav
// so each section has one canonical label/description.
const activeSection = computed(
  () => flatSections.value.find((s) => s.id === activeId.value) ?? flatSections.value[0]
);

function readHashId() {
  if (typeof window === 'undefined') return '';
  const raw = String(window.location.hash || '').replace(/^#\/?/, '');
  // Vue router uses hashes like "#/settings"; ignore those by requiring
  // a plain section id with no slashes.
  if (!raw || raw.includes('/')) return '';
  return raw;
}

function onNavClick(id) {
  if (!knownIds.value.has(id)) return;
  activeId.value = id;
  // Scroll the admin shell back to the top so the new section opens at
  // its own header, not mid-scroll from the previous one.
  const root = document.querySelector('.quizably-admin__main');
  if (root) {
    root.scrollTo({ top: 0, behavior: 'smooth' });
  } else if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// Sync the URL hash when the active section changes so deep links work
// (e.g. #branding loads the Branding section directly).
watch(activeId, (id) => {
  if (typeof window === 'undefined') return;
  // The plugin admin uses vue-router in hash mode (#/settings); we
  // append our section id with a comma-style separator so the router
  // path stays intact while the section is still recoverable.
  // Implementation: store the section id in a sub-fragment after the
  // router path — `#/settings#branding`. Browsers accept multiple `#`
  // characters in the URL bar.
  try {
    const current = window.location.hash || '';
    const [routerPart] = current.split('#').filter(Boolean);
    const next = routerPart ? `#${routerPart}#${id}` : `#${id}`;
    if (window.location.hash !== next) {
      // Use history.replaceState so we don't pollute the back-stack on
      // every sidebar click.
      window.history.replaceState(null, '', next);
    }
  } catch (_e) {
    // No-op: browsers without history API just lose the deep link.
  }
});

// --- Debounced autosave ---------------------------------------------------
const pending = {};
let saveTimer = null;
let inflight = 0;

function markSaving() {
  inflight += 1;
  isSaving.value = true;
}
function markSaved() {
  inflight = Math.max(0, inflight - 1);
  if (inflight === 0) {
    isSaving.value = false;
    lastSavedAt.value = Date.now();
    now.value = Date.now();
  }
}

function onEmailBodyChange(html) {
  scheduleSave('email_template_body', html);
}

/** Drop a {token} into the email body at the cursor. */
function insertEmailToken(token) {
  emailBodyEditor.value?.insertText(token);
}

function scheduleSave(key, value) {
  draft[key] = value;
  pending[key] = value;
  if (saveTimer) clearTimeout(saveTimer);
  saveTimer = setTimeout(flushSave, AUTOSAVE_DEBOUNCE);
}

async function flushSave() {
  if (saveTimer) {
    clearTimeout(saveTimer);
    saveTimer = null;
  }
  const keys = Object.keys(pending);
  if (!keys.length) return;
  const batch = { ...pending };
  for (const k of keys) delete pending[k];
  for (const [key, value] of Object.entries(batch)) {
    markSaving();
    try {
      await settingsStore.update(key, value);
    } catch (e) {
      toast.push({
        variant: 'danger',
        title: __('Could not save setting'),
        message: e.message,
      });
    } finally {
      markSaved();
    }
  }
}

async function saveImmediate(key, value) {
  draft[key] = value;
  markSaving();
  try {
    await settingsStore.update(key, value);
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save setting'),
      message: e.message,
    });
  } finally {
    markSaved();
  }
}

const isProUser = computed(() => isPro());

// Whether this screen may mention Pro at all (it only does in the Integrations
// blurb now). The License section is separate: it only exists when the Pro
// add-on itself is active — see sectionGroups.
const proControlsVisible = computed(() => proFeaturesVisible());

const integrationsDescription = computed(() =>
  proControlsVisible.value
    ? __('Connect email marketing tools, CRMs, and webhooks for your quizzes.')
    : __('Send quiz submissions to your own endpoint with a webhook.')
);

async function fetchLicense() {
  if (!isPro()) return;
  licenseLoading.value = true;
  try {
    licenseStatus.value = await api.get('pro/license');
  } catch (e) {
    // Non-fatal: Pro REST route may not exist on older Pro versions.
    licenseStatus.value = null;
  } finally {
    licenseLoading.value = false;
  }
}

async function saveLicense() {
  if (licenseSaving.value) return;
  licenseSaving.value = true;
  try {
    const result = await api.put('pro/license', { license_key: licenseKey.value.trim() });
    licenseStatus.value = {
      has_key: licenseKey.value.trim().length > 0,
      valid: result.valid,
      key: licenseKey.value.trim().length > 0
        ? '*'.repeat(Math.max(0, licenseKey.value.trim().length - 4)) + licenseKey.value.trim().slice(-4)
        : null,
    };
    licenseKey.value = '';
    toast.push({ variant: 'success', title: __('License key saved') });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save license key'),
      message: e.message,
    });
  } finally {
    licenseSaving.value = false;
  }
}

async function onReset() {
  const confirmed = window.confirm(
    __('Reset every global setting back to defaults? Quizzes, questions, and leads are untouched.')
  );
  if (!confirmed) return;
  resetting.value = true;
  try {
    for (const [key, value] of Object.entries(DEFAULT_DRAFT)) {
      await settingsStore.update(key, value);
    }
    hydrate();
    lastSavedAt.value = Date.now();
    toast.push({ variant: 'info', title: __('Settings reset') });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Reset failed'),
      message: e.message,
    });
  } finally {
    resetting.value = false;
  }
}

// Keep local draft in sync whenever the store's authoritative map changes
// (e.g. after the initial fetch or an update echo).
watch(
  () => settingsStore.map,
  () => hydrate(),
  { deep: true }
);

onMounted(async () => {
  loading.value = true;
  startTick();

  // Pick up a deep-linked section before fetching, so the right region
  // mounts as soon as the loading spinner clears.
  const hashed = readHashId();
  if (hashed && knownIds.value.has(hashed)) {
    activeId.value = hashed;
  }

  try {
    await Promise.all([settingsStore.fetch(), templatesStore.fetch()]);
    hydrate();
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not load settings'),
      message: e.message,
    });
  } finally {
    loading.value = false;
  }

  // Fetch license status independently — non-blocking so a missing Pro
  // plugin doesn't stall the rest of the settings page.
  fetchLicense();
});

onBeforeUnmount(() => {
  flushSave();
  stopTick();
});

</script>

<style scoped>
.settings-view {
  position: relative;
  padding: 0;
  max-width: 1180px;
  margin-inline: auto;
}

/* ------------------------------------------------------------------ */
/* Page top: section header + autosave status pill                    */
/* ------------------------------------------------------------------ */

.settings-view {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.settings-view__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 0 4px;
}

.settings-view__lead {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  min-width: 0;
}

.settings-view__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  border-radius: var(--r-lg);
  background: var(--brand-tint);
  color: var(--brand);
}

.settings-view__heading {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.settings-view__title {
  font-family: var(--f-display, var(--f-sans));
  font-size: 20px;
  font-weight: 600;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.settings-view__sub {
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.5;
  color: var(--ink-3);
  margin: 0;
  max-width: 72ch;
}

/* ------------------------------------------------------------------ */
/* Top tab strip — flat tabs with an underline rule on the active     */
/* item, matches the Smart Community / Stripe / GitHub pattern.        */
/* ------------------------------------------------------------------ */

.settings-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-1);
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.settings-tabs::-webkit-scrollbar { display: none; }

.settings-tabs__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: inline-flex;
  gap: 4px;
  white-space: nowrap;
}

.settings-tabs__list li { margin: 0; }

.settings-tabs__item {
  position: relative;
  display: inline-flex;
  align-items: center;
  padding: 10px 14px;
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-3);
  text-decoration: none;
  border-radius: var(--r-sm) var(--r-sm) 0 0;
  transition: color 120ms, background 120ms;
  line-height: 1;
}

.settings-tabs__item:hover {
  color: var(--ink-1);
  background: var(--bg-surface);
}

.settings-tabs__item:focus-visible {
  outline: none;
  color: var(--ink-1);
  box-shadow: 0 0 0 2px var(--brand);
}

.settings-tabs__item.is-active {
  color: var(--brand-hover);
  font-weight: 600;
}

.settings-tabs__item.is-active::after {
  content: '';
  position: absolute;
  inset-inline-start: 8px;
  inset-inline-end: 8px;
  bottom: -1px;
  height: 2px;
  background: var(--brand);
  border-radius: var(--r-pill) var(--r-pill) 0 0;
}

.settings-tabs__item.is-active.is-danger {
  color: var(--danger);
}

.settings-tabs__item.is-active.is-danger::after {
  background: var(--danger);
}

.settings-view__status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  font-family: var(--f-mono);
  font-size: 12px;
  letter-spacing: 0.02em;
  color: var(--ink-2);
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  border-radius: var(--r-pill);
  min-height: 28px;
  font-variant-numeric: tabular-nums;
}

.settings-view__status:empty {
  display: none;
}

.settings-view__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 0 3px var(--success-bg);
}

.settings-view__dot--saving {
  background: var(--brand);
  box-shadow: 0 0 0 3px var(--brand-bg);
}

.settings-view__spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-settings-spin 700ms linear infinite;
}

@keyframes quizably-settings-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .settings-view__spinner {
    animation-duration: 0ms;
  }
}

/* ------------------------------------------------------------------ */
/* Content + sections                                                 */
/* ------------------------------------------------------------------ */

.settings-view__content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.settings-view__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 48px;
  color: var(--ink-3);
  font-size: 14px;
  justify-content: center;
}

/* One section = a few cards; 20px between them. */
.settings-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}


/* ------------------------------------------------------------------ */
/* Inline control helpers                                             */
/* ------------------------------------------------------------------ */

.settings-color {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding-block: 4px 4px;
  padding-inline: 4px 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-pill);
  transition: border-color 120ms;
}

.settings-color:hover,
.settings-color:focus-within {
  border-color: var(--brand);
}

/* Round swatch built from the native <input type="color"> — the browser
   picker still opens on click, we just hide the native chrome. The two
   inset rings simulate a soft border + halo without an extra wrapper. */
.settings-color__picker {
  width: 28px;
  height: 28px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  box-shadow:
    inset 0 0 0 2px var(--bg-surface),
    0 0 0 1px var(--border-2);
  transition: box-shadow 120ms;
}

.settings-color__picker::-webkit-color-swatch-wrapper { padding: 0; }
.settings-color__picker::-webkit-color-swatch { border: 0; border-radius: 50%; }
.settings-color__picker::-moz-color-swatch { border: 0; border-radius: 50%; }

.settings-color__picker:hover,
.settings-color__picker:focus-visible {
  outline: none;
  box-shadow:
    inset 0 0 0 2px var(--bg-surface),
    0 0 0 1px var(--brand),
    0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent);
}

.settings-color code {
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-2);
  padding: 0;
  background: transparent;
  border-radius: 0;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.settings-placement {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* One-click "insert a detail" pills under the email editor. */
.settings-tokens {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.settings-token {
  display: inline-flex;
  align-items: center;
  padding: 3px 9px;
  font-family: var(--f-mono);
  font-size: 11.5px;
  line-height: 1.4;
  color: var(--ink-2);
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  border-radius: var(--r-pill);
  cursor: pointer;
  transition: background 120ms, border-color 120ms, color 120ms;
}

.settings-token:hover {
  background: var(--brand-tint);
  border-color: var(--brand);
  color: var(--brand);
}

.settings-token:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.settings-readonly-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--success);
  background: var(--success-bg);
  border-radius: var(--r-pill);
}

/* ------------------------------------------------------------------ */
/* Roles & access — modern card grid for the role overview.            */
/* ------------------------------------------------------------------ */

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 10px;
  padding: 18px 0;
}

.role-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
  transition: border-color 120ms, box-shadow 120ms;
}

.role-card:hover {
  border-color: var(--ink-3);
}

.role-card.is-elevated {
  /* The roles that already have manage capability get a subtle accent
     left-border so admins can scan them at a glance. */
  border-inline-start: 3px solid var(--brand);
}

.role-card__top {
  display: flex;
  align-items: center;
  gap: 10px;
}

.role-card__avatar {
  flex: 0 0 auto;
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  color: var(--ink-3);
}

.role-card.is-elevated .role-card__avatar {
  background: color-mix(in srgb, var(--brand) 12%, var(--bg-surface));
  border-color: color-mix(in srgb, var(--brand) 28%, var(--border-1));
  color: var(--brand);
}

.role-card__heading {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.role-card__name {
  margin: 0;
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-1);
  line-height: 1.2;
}

.role-card__role {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
}

.role-card__badge {
  flex: 0 0 auto;
}

.role-card__caps {
  margin: 0;
  font-size: 11.5px;
  line-height: 1.4;
  color: var(--ink-3);
}

/* ------------------------------------------------------------------ */
/* License                                                            */
/* ------------------------------------------------------------------ */

.settings-license__masked {
  font-family: var(--f-mono);
  font-size: 12px;
  letter-spacing: 0.06em;
  color: var(--ink-2);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 3px 8px;
}

.settings-license__row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.settings-license__row > :first-child {
  flex: 1;
  min-width: 0;
}

/* ------------------------------------------------------------------ */
/* Danger zone                                                        */
/* ------------------------------------------------------------------ */


/* ------------------------------------------------------------------ */
/* Responsive                                                         */
/* ------------------------------------------------------------------ */

@media (max-width: 720px) {
  .settings-view__head {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .settings-view__title {
    font-size: 17px;
  }

}
</style>
