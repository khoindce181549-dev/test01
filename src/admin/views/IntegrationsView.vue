<template>
  <div class="integrations-view">
    <header class="integrations-view__header">
      <div class="integrations-view__heading">
        <h1 class="integrations-view__title">
          {{ __('Integrations') }}
        </h1>
        <p class="integrations-view__sub">
          {{ subtitle }}
        </p>
      </div>
    </header>

    <div
      v-if="loading"
      class="integrations-view__loading"
      aria-live="polite"
    >
      <span
        class="integrations-view__spinner"
        aria-hidden="true"
      />
      <span>{{ __('Loading integrations…') }}</span>
    </div>

    <div
      v-else
      class="integrations-view__grid"
    >
      <article
        v-for="integration in integrations"
        :key="integration.slug || integration.id"
        :class="[
          'integration-card',
          {
            'is-pro': integration.pro,
            'is-connected': isConnected(integration),
          },
        ]"
      >
        <div
          class="integration-card__logo"
          :style="logoStyle(integration)"
          aria-hidden="true"
        >
          {{ initials(integration) }}
        </div>
        <div class="integration-card__meta">
          <div class="integration-card__name">
            {{ integration.name || integration.slug }}
          </div>
          <div class="integration-card__desc">
            {{ integration.description || defaultDescription(integration) }}
          </div>
          <div class="integration-card__badges">
            <Badge
              v-if="integration.pro"
              variant="pro"
              size="sm"
            >
              {{ __('Pro') }}
            </Badge>
            <Badge
              v-else-if="tiersVisible"
              variant="neutral"
              size="sm"
            >
              {{ __('Free') }}
            </Badge>
            <Badge
              v-if="isConnected(integration)"
              variant="success"
              size="sm"
            >
              {{ __('Connected') }}
            </Badge>
            <Badge
              v-else
              variant="default"
              size="sm"
            >
              {{ __('Not connected') }}
            </Badge>
          </div>
        </div>
        <div class="integration-card__actions">
          <!-- Pro + configured (Pro plugin active): show Configure button -->
          <Button
            v-if="integration.pro && integration.configured"
            variant="primary"
            size="sm"
            @click="openConfigModal(integration)"
          >
            {{ __('Configure') }}
          </Button>
          <!-- Pro + not configured (free user): show Upgrade -->
          <Button
            v-else-if="integration.pro && !integration.configured"
            variant="primary"
            size="sm"
            @click="onUpgrade(integration)"
          >
            {{ __('Upgrade to connect') }}
          </Button>
          <Button
            v-else-if="integration.slug === 'webhook'"
            variant="outline"
            size="sm"
            @click="openWebhookModal"
          >
            {{ isConnected(integration) ? __('Edit webhook') : __('Configure') }}
          </Button>
          <Button
            v-else
            variant="outline"
            size="sm"
            disabled
          >
            {{ __('Configure') }}
          </Button>
        </div>
      </article>
    </div>

    <!-- Webhook modal -->
    <Modal
      :model-value="webhookModalOpen"
      :title="__('Configure webhook')"
      size="md"
      @update:model-value="onWebhookModalToggle"
    >
      <div class="integrations-webhook">
        <p class="integrations-webhook__desc">
          {{ __('We POST JSON of every submission to your endpoint. Use this as a default for new quizzes — individual quizzes can still override this URL in their Integrations tab.') }}
        </p>
        <Input
          v-model="webhookDraft"
          ltr
          :label="__('Default webhook URL')"
          placeholder="https://hooks.example.com/quizably"
          :helper-text="__('Leave blank to disable the default webhook.')"
        />
      </div>
      <template #footer>
        <Button
          variant="outline"
          :disabled="savingWebhook"
          @click="webhookModalOpen = false"
        >
          {{ __('Cancel') }}
        </Button>
        <Button
          variant="primary"
          :loading="savingWebhook"
          @click="onWebhookSave"
        >
          {{ __('Save webhook') }}
        </Button>
      </template>
    </Modal>

    <!-- Integration config modal (Pro) -->
    <Modal
      :model-value="configModalOpen"
      :title="configModalTitle"
      size="md"
      @update:model-value="onConfigModalToggle"
    >
      <div class="integrations-config">
        <p class="integrations-config__desc">
          {{ __('Enter your API credentials below. They are stored securely in your WordPress options table.') }}
        </p>

        <!-- Mailchimp -->
        <template v-if="configActiveKey === 'mailchimp'">
          <Input
            :model-value="configDraft.api_key"
            :label="__('API Key')"
            placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us1"
            :helper-text="__('Find this under Account → Extras → API Keys in Mailchimp.')"
            @update:model-value="configDraft.api_key = $event"
          />
          <Input
            :model-value="configDraft.audience_id"
            :label="__('Audience ID')"
            placeholder="a1b2c3d4e5"
            :helper-text="__('Found in Audience → Settings → Audience name and defaults.')"
            @update:model-value="configDraft.audience_id = $event"
          />
        </template>

        <!-- ConvertKit -->
        <template v-else-if="configActiveKey === 'convertkit'">
          <Input
            :model-value="configDraft.api_key"
            :label="__('API Key')"
            :placeholder="__('Your ConvertKit API key')"
            :helper-text="__('Found in Account Settings → Advanced.')"
            @update:model-value="configDraft.api_key = $event"
          />
          <Input
            :model-value="configDraft.form_id"
            :label="__('Form ID')"
            placeholder="1234567"
            :helper-text="__('The numeric ID of the form subscribers will be added to.')"
            @update:model-value="configDraft.form_id = $event"
          />
        </template>

        <!-- ActiveCampaign -->
        <template v-else-if="configActiveKey === 'activecampaign'">
          <Input
            :model-value="configDraft.api_url"
            :label="__('API URL')"
            placeholder="https://youraccountname.api-us1.com"
            :helper-text="__('Your ActiveCampaign account URL (no trailing slash).')"
            @update:model-value="configDraft.api_url = $event"
          />
          <Input
            :model-value="configDraft.api_key"
            :label="__('API Key')"
            :placeholder="__('Your ActiveCampaign API key')"
            :helper-text="__('Found under Settings → Developer.')"
            @update:model-value="configDraft.api_key = $event"
          />
          <Input
            :model-value="configDraft.list_id"
            :label="__('List ID (optional)')"
            placeholder="1"
            :helper-text="__('Leave blank to add contacts without a list.')"
            @update:model-value="configDraft.list_id = $event"
          />
        </template>

        <!-- MailerLite -->
        <template v-else-if="configActiveKey === 'mailerlite'">
          <Input
            :model-value="configDraft.api_key"
            :label="__('API Key')"
            :placeholder="__('Your MailerLite API key')"
            :helper-text="__('Found under Integrations → API in MailerLite.')"
            @update:model-value="configDraft.api_key = $event"
          />
          <Input
            :model-value="configDraft.group_id"
            :label="__('Group ID (optional)')"
            placeholder="12345"
            :helper-text="__('Subscribers will be added to this group if set.')"
            @update:model-value="configDraft.group_id = $event"
          />
        </template>

        <!-- Brevo (Sendinblue) -->
        <template v-else-if="configActiveKey === 'brevo'">
          <Input
            :model-value="configDraft.api_key"
            :label="__('API Key')"
            :placeholder="__('Your Brevo API key')"
            :helper-text="__('Found under SMTP & API in your Brevo account.')"
            @update:model-value="configDraft.api_key = $event"
          />
          <Input
            :model-value="configDraft.list_id"
            :label="__('List ID (optional)')"
            placeholder="3"
            :helper-text="__('Contacts will be added to this list if set.')"
            @update:model-value="configDraft.list_id = $event"
          />
        </template>
      </div>
      <template #footer>
        <Button
          variant="outline"
          :disabled="savingConfig"
          @click="configModalOpen = false"
        >
          {{ __('Cancel') }}
        </Button>
        <Button
          variant="primary"
          :loading="savingConfig"
          @click="onConfigSave"
        >
          {{ __('Save credentials') }}
        </Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Badge, Button, Input, Modal, useToast } from '@admin/ui';
import { useIntegrationsStore } from '@admin/stores/integrations';
import { useSettingsStore } from '@admin/stores/settings';
import { api } from '@admin/api/client';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';

const integrationsStore = useIntegrationsStore();
const settingsStore = useSettingsStore();
const toast = useToast();

const loading = ref(false);
const webhookModalOpen = ref(false);
const webhookDraft = ref('');
const savingWebhook = ref(false);

// ---- Integration config modal state ----
const configModalOpen = ref(false);
const configActiveKey = ref('');
const configActiveIntegration = ref(null);
const savingConfig = ref(false);

/**
 * Reactive draft object for the currently-open integration config modal.
 * Fields are populated when the modal opens (see openConfigModal).
 */
const configDraft = reactive({
  // Mailchimp
  api_key: '',
  audience_id: '',
  // ConvertKit
  form_id: '',
  // ActiveCampaign
  api_url: '',
  list_id: '',
  // (api_key is shared across all providers)
  // MailerLite / Brevo also use api_key + group_id / list_id (already above)
  group_id: '',
});

const configModalTitle = computed(() => {
  const integration = configActiveIntegration.value;
  if (!integration) return __('Configure integration');
  // translators: %s is the name of an integration, e.g. Mailchimp.
  return sprintf(__('Configure %s'), integration.name || integration.slug);
});

// Saved configs keyed by integration slug — loaded lazily when modal opens.
const savedConfigs = ref({});

// Marketing / CRM connectors ship with the Pro add-on. Without it (and with
// Pro promotion off) they aren't listed at all, leaving the free webhook.
// `tiersVisible` gates the "Free" tier chip, which is meaningless with no
// paid tier to contrast against.
const tiersVisible = computed(() => proFeaturesVisible());

const integrations = computed(() =>
  integrationsStore.items.filter((i) => !i.pro || tiersVisible.value)
);

const subtitle = computed(() =>
  tiersVisible.value
    ? __('Connect to your favorite email services.')
    : __('Send every quiz submission to your own endpoint with a webhook.')
);

const defaultWebhookUrl = computed(() => settingsStore.map.default_webhook_url || '');

function isConnected(integration) {
  if (integration.slug === 'webhook' && defaultWebhookUrl.value) return true;
  // Pro integration: connected if we have a saved config with at least one non-empty value.
  if (integration.pro && integration.configured) {
    const saved = savedConfigs.value[integration.slug];
    if (saved && typeof saved === 'object') {
      return Object.values(saved).some((v) => v && String(v).trim() !== '');
    }
  }
  return false;
}

function initials(integration) {
  const src = integration.name || integration.slug || '';
  const parts = src.trim().split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]).join('').toUpperCase() || '?';
}

function logoStyle(integration) {
  const palettes = [
    ['#6366F1', '#EC4899'],
    ['#F59E0B', '#F97316'],
    ['#10B981', '#059669'],
    ['#0EA5E9', '#2563EB'],
    ['#EF4444', '#F472B6'],
    ['#7C3AED', '#4F46E5'],
  ];
  const key = String(integration.slug || integration.id || '').length;
  const pair = palettes[key % palettes.length];
  return { background: `linear-gradient(135deg, ${pair[0]}, ${pair[1]})` };
}

function defaultDescription(integration) {
  switch (integration.slug) {
    case 'webhook':
      return __('POST JSON of each submission to your endpoint.');
    case 'mailchimp':
      return __('Add leads to a Mailchimp audience with tags.');
    case 'convertkit':
      return __('Subscribe leads to a ConvertKit form.');
    case 'activecampaign':
      return __('Create ActiveCampaign contacts with form data.');
    case 'mailerlite':
      return __('Add subscribers to a MailerLite group.');
    case 'brevo':
      return __('Add contacts to a Brevo list.');
    case 'zapier':
      return __('Trigger Zaps from submissions.');
    case 'hubspot':
      return __('Create HubSpot contacts with form data.');
    case 'google_sheets':
      return __('Append each submission as a new row.');
    default:
      return '';
  }
}

/** Reset all draft fields to empty strings. */
function resetDraft() {
  configDraft.api_key = '';
  configDraft.audience_id = '';
  configDraft.form_id = '';
  configDraft.api_url = '';
  configDraft.list_id = '';
  configDraft.group_id = '';
}

/** Populate draft from a saved config object. */
function fillDraftFrom(saved) {
  if (!saved || typeof saved !== 'object') return;
  if (saved.api_key !== undefined) configDraft.api_key = saved.api_key;
  if (saved.audience_id !== undefined) configDraft.audience_id = saved.audience_id;
  if (saved.form_id !== undefined) configDraft.form_id = saved.form_id;
  if (saved.api_url !== undefined) configDraft.api_url = saved.api_url;
  if (saved.list_id !== undefined) configDraft.list_id = saved.list_id;
  if (saved.group_id !== undefined) configDraft.group_id = saved.group_id;
}

async function openConfigModal(integration) {
  const slug = integration.slug || integration.id;
  configActiveKey.value = slug;
  configActiveIntegration.value = integration;
  resetDraft();

  // Load saved credentials if not already cached.
  if (!savedConfigs.value[slug]) {
    try {
      const saved = await api.get(`settings/integrations/${slug}`);
      savedConfigs.value[slug] = saved || {};
    } catch {
      savedConfigs.value[slug] = {};
    }
  }
  fillDraftFrom(savedConfigs.value[slug]);
  configModalOpen.value = true;
}

function onConfigModalToggle(open) {
  configModalOpen.value = open;
}

async function onConfigSave() {
  const key = configActiveKey.value;
  if (!key) return;

  // Build config object with only the fields relevant to this integration.
  let config = {};
  switch (key) {
    case 'mailchimp':
      config = { api_key: configDraft.api_key, audience_id: configDraft.audience_id };
      break;
    case 'convertkit':
      config = { api_key: configDraft.api_key, form_id: configDraft.form_id };
      break;
    case 'activecampaign':
      config = { api_url: configDraft.api_url, api_key: configDraft.api_key, list_id: configDraft.list_id };
      break;
    case 'mailerlite':
      config = { api_key: configDraft.api_key, group_id: configDraft.group_id };
      break;
    case 'brevo':
      config = { api_key: configDraft.api_key, list_id: configDraft.list_id };
      break;
    default:
      config = { api_key: configDraft.api_key };
  }

  savingConfig.value = true;
  try {
    await api.put('settings/integrations', { key, config });
    // Update cached saved config so isConnected() updates reactively.
    savedConfigs.value[key] = { ...config };
    toast.push({
      variant: 'success',
      title: __('Credentials saved'),
      // translators: %s is the name of an integration, e.g. Mailchimp.
      message: sprintf(__('%s is now configured.'), configActiveIntegration.value?.name || key),
    });
    configModalOpen.value = false;
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save credentials'),
      message: e.message,
    });
  } finally {
    savingConfig.value = false;
  }
}

function openWebhookModal() {
  webhookDraft.value = defaultWebhookUrl.value;
  webhookModalOpen.value = true;
}

function onWebhookModalToggle(open) {
  webhookModalOpen.value = open;
}

async function onWebhookSave() {
  savingWebhook.value = true;
  try {
    await settingsStore.update('default_webhook_url', webhookDraft.value || '');
    toast.push({
      variant: 'success',
      title: webhookDraft.value ? __('Webhook saved') : __('Webhook cleared'),
      message: webhookDraft.value
        ? __('This URL is now the default for new quizzes.')
        : __('The default webhook was removed.'),
    });
    webhookModalOpen.value = false;
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save webhook'),
      message: e.message,
    });
  } finally {
    savingWebhook.value = false;
  }
}

const isProUser = computed(() => isPro());

function onUpgrade(integration) {
  if (isProUser.value) {
    toast.push({
      variant: 'success',
      // translators: %s is the name of an integration, e.g. Mailchimp.
      title: sprintf(__('%s is included with Pro'), integration.name || integration.slug),
      message: __('Open a quiz → Integrations tab to wire up an API key for this provider.'),
    });
    return;
  }
  toast.push({
    variant: 'info',
    title: __('Pro integration'),
    // translators: %s is the name of an integration, e.g. Mailchimp.
    message: sprintf(__('%s requires Quizably Pro. Upgrade coming soon.'), integration.name || integration.slug),
  });
}

// Keep the webhook draft in sync if settings load while the modal is open.
watch(defaultWebhookUrl, (next) => {
  if (!webhookModalOpen.value) webhookDraft.value = next;
});

// Pre-load saved configs for all configured Pro integrations in the background.
watch(
  integrations,
  async (list) => {
    const proConfigured = list.filter((i) => i.pro && i.configured);
    await Promise.allSettled(
      proConfigured
        .filter((i) => !savedConfigs.value[i.slug])
        .map(async (i) => {
          try {
            const saved = await api.get(`settings/integrations/${i.slug}`);
            savedConfigs.value[i.slug] = saved || {};
          } catch {
            savedConfigs.value[i.slug] = {};
          }
        })
    );
  },
  { immediate: true }
);

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([integrationsStore.fetch(), settingsStore.fetch()]);
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not load integrations'),
      message: e.message,
    });
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.integrations-view {
  max-width: 1200px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.integrations-view__header {
  padding: 14px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.integrations-view__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  line-height: 1.25;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  margin: 0 0 2px;
}

.integrations-view__sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--ink-3);
  margin: 0;
  max-width: 64ch;
}

.integrations-view__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 48px;
  color: var(--ink-3);
  font-size: 14px;
  justify-content: center;
}

.integrations-view__spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-integrations-spin 700ms linear infinite;
}

@keyframes quizably-integrations-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .integrations-view__spinner {
    animation-duration: 0ms;
  }
}

.integrations-view__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

@media (max-width: 1040px) {
  .integrations-view__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .integrations-view__grid {
    grid-template-columns: 1fr;
  }
}

.integration-card {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
  transition: box-shadow 150ms ease, border-color 150ms ease;
}

.integration-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--border-2);
}

.integration-card.is-connected {
  border-color: var(--border-2);
}

.integration-card__logo {
  width: 44px;
  height: 44px;
  border-radius: var(--r-md);
  display: grid;
  place-items: center;
  color: #fff;
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 15px;
  letter-spacing: 0.02em;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

.integration-card__meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 0;
}

.integration-card__name {
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 500;
  color: var(--ink-1);
  letter-spacing: -0.005em;
}

.integration-card__desc {
  font-size: 12.5px;
  color: var(--ink-3);
  line-height: 1.5;
}

.integration-card__badges {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 4px;
}

.integration-card__actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.integrations-webhook {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.integrations-webhook__desc {
  margin: 0;
  font-size: 13px;
  color: var(--ink-3);
  line-height: 1.55;
}

.integrations-config {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.integrations-config__desc {
  margin: 0;
  font-size: 13px;
  color: var(--ink-3);
  line-height: 1.55;
}
</style>
