<template>
  <div class="integrations-scroll">
    <div class="integrations-tab">
    <header class="integrations-tab__head">
      <div>
        <h2 class="integrations-tab__title">
          {{ __('Integrations') }}
        </h2>
        <p class="integrations-tab__desc">
          {{ introText }}
        </p>
      </div>
      <span class="integrations-tab__count">
        {{ connectedLabel }}
      </span>
    </header>

    <div
      v-if="loading"
      class="integrations-tab__loading"
      aria-live="polite"
    >
      {{ __('Loading integrations…') }}
    </div>

    <template v-else>
      <!-- FREE — webhook always visible, configured inline -->
      <section class="integrations-section">
        <header class="integrations-section__head">
          <span class="integrations-section__title">{{ freeSectionTitle }}</span>
          <span class="integrations-section__sub">{{ __('Available on every quiz') }}</span>
        </header>

        <article
          v-if="webhookIntegration"
          :class="['int-card', { 'is-connected': isWebhookEnabled }]"
        >
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('webhook')"
            >
              <component :is="iconFor('webhook')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                {{ webhookIntegration.name || __('Webhook') }}
                <Badge
                  v-if="isWebhookEnabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('POST JSON of each submission to your endpoint. Plays nicely with Zapier, Make, n8n, or any HTTPS service.') }}
              </div>
            </div>
            <Toggle
              :model-value="isWebhookEnabled"
              size="sm"
              :aria-label="isWebhookEnabled ? __('Disable webhook') : __('Enable webhook')"
              @update:model-value="onWebhookEnabled"
            />
          </div>

          <!-- Non-Pro: single webhook body (unchanged) -->
          <div
            v-if="!isProUser"
            class="int-card__body"
          >
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="webhookInputId"
              >{{ __('Webhook URL') }}</label>
              <Input
                :id="webhookInputId"
                :model-value="webhookUrl"
                placeholder="https://hooks.example.com/quizably"
                :helper-text="__('Sent when the lead form is submitted (event: lead_captured) and when the quiz is completed (event: submission_completed, which includes the lead). A failed delivery is retried up to 3 times, after about 1, 2 and 4 minutes.')"
                @update:model-value="scheduleWebhookSave"
                @blur="flushWebhookSave"
              />
            </div>
            <div class="int-card__actions">
              <Button
                variant="outline"
                size="sm"
                :disabled="!webhookUrl || testingWebhook"
                @click="onTestWebhook"
              >
                <template #icon-left>
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <polyline points="13 17 18 12 13 7" />
                    <polyline points="6 17 11 12 6 7" />
                  </svg>
                </template>
                {{ testingWebhook ? __('Sending…') : __('Send test') }}
              </Button>
            </div>
          </div>

          <!-- Pro: multiple webhooks panel -->
          <div
            v-else
            class="int-card__body wh-multi"
          >
            <div
              v-for="(wh, idx) in webhooksArray"
              :key="wh.id"
              class="wh-row"
            >
              <div class="wh-row__fields">
                <div class="int-card__field wh-row__label-field">
                  <label
                    class="int-card__label"
                    :for="`${uid}-wh-${wh.id}-label`"
                  >{{ __('Label') }}</label>
                  <Input
                    :id="`${uid}-wh-${wh.id}-label`"
                    :model-value="wh.label"
                    :placeholder="__('e.g. Zapier')"
                    @update:model-value="(v) => updateWebhookRow(idx, 'label', v)"
                    @blur="flushWebhooksArraySave"
                  />
                </div>
                <div class="int-card__field wh-row__url-field">
                  <label
                    class="int-card__label"
                    :for="`${uid}-wh-${wh.id}-url`"
                  >{{ __('URL') }}</label>
                  <Input
                    :id="`${uid}-wh-${wh.id}-url`"
                    :model-value="wh.url"
                    placeholder="https://hooks.example.com/quizably"
                    @update:model-value="(v) => updateWebhookRow(idx, 'url', v)"
                    @blur="flushWebhooksArraySave"
                  />
                </div>
                <div class="int-card__field wh-row__secret-field">
                  <label
                    class="int-card__label"
                    :for="`${uid}-wh-${wh.id}-secret`"
                  >{{ __('Secret') }} <span class="int-card__optional">{{ __('(optional)') }}</span></label>
                  <Input
                    :id="`${uid}-wh-${wh.id}-secret`"
                    :model-value="wh.secret"
                    :placeholder="__('HMAC signing secret')"
                    type="password"
                    @update:model-value="(v) => updateWebhookRow(idx, 'secret', v)"
                    @blur="flushWebhooksArraySave"
                  />
                </div>
              </div>
              <div class="wh-row__controls">
                <Toggle
                  :model-value="Boolean(wh.enabled)"
                  size="sm"
                  :aria-label="webhookToggleLabel(wh, idx)"
                  @update:model-value="(v) => updateWebhookRowImmediate(idx, 'enabled', v)"
                />
                <button
                  type="button"
                  class="wh-row__test"
                  :disabled="!wh.url || testingWebhookRowId === wh.id"
                  :aria-label="sprintf(__('Test webhook %s'), wh.label || idx + 1)"
                  :title="wh.url ? __('Send test') : __('Enter a URL first')"
                  @click="onTestWebhookRow(wh)"
                >
                  <svg
                    v-if="testingWebhookRowId !== wh.id"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    width="16"
                    height="16"
                  >
                    <polyline points="13 17 18 12 13 7" />
                    <polyline points="6 17 11 12 6 7" />
                  </svg>
                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    width="16"
                    height="16"
                    class="wh-row__spin"
                  >
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                  </svg>
                </button>
                <button
                  type="button"
                  class="wh-row__delete"
                  :aria-label="sprintf(__('Delete webhook %s'), wh.label || idx + 1)"
                  @click="deleteWebhookRow(idx)"
                >
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    width="16"
                    height="16"
                  >
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    <path d="M10 11v6M14 11v6" />
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="wh-multi__footer">
              <Button
                variant="outline"
                size="sm"
                @click="addWebhookRow"
              >
                <template #icon-left>
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <line
                      x1="12"
                      y1="5"
                      x2="12"
                      y2="19"
                    />
                    <line
                      x1="5"
                      y1="12"
                      x2="19"
                      y2="12"
                    />
                  </svg>
                </template>
                {{ __('Add webhook') }}
              </Button>
              <span
                v-if="webhooksArray.length === 0"
                class="wh-multi__empty"
              >{{ __('No webhooks configured yet.') }}</span>
            </div>
          </div>
        </article>
      </section>

      <!-- PRO — per-quiz config cards for AC / ML / Brevo -->
      <section
        v-if="isProUser"
        class="integrations-section"
      >
        <header class="integrations-section__head">
          <span class="integrations-section__title">{{ __('Premium connectors') }}</span>
          <span class="integrations-section__sub">{{ __('Available with Pro') }}</span>
        </header>

        <!-- ActiveCampaign -->
        <article :class="['int-card', { 'is-connected': acConfig.enabled }]">
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('activecampaign')"
            >
              <component :is="iconFor('activecampaign')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                ActiveCampaign
                <Badge
                  v-if="acConfig.enabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('Sync leads to ActiveCampaign as contacts.') }}
              </div>
            </div>
            <Toggle
              :model-value="Boolean(acConfig.enabled)"
              size="sm"
              :aria-label="sprintf(__('Enable %s'), 'ActiveCampaign')"
              @update:model-value="(v) => scheduleIntegrationSave('activecampaign', { ...acConfig, enabled: v })"
            />
          </div>

          <div class="int-card__body">
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ac-url`"
              >{{ __('API URL') }}</label>
              <Input
                :id="`${uid}-ac-url`"
                :model-value="acConfig.api_url || ''"
                :placeholder="globalConfigs.activecampaign?.api_url || 'https://your-account.api-us1.com'"
                @update:model-value="(v) => scheduleIntegrationSave('activecampaign', { ...acConfig, api_url: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ac-key`"
              >{{ __('API Key') }}</label>
              <Input
                :id="`${uid}-ac-key`"
                :model-value="acConfig.api_key || ''"
                :placeholder="globalConfigs.activecampaign?.api_key ? '••••••••' : __('Paste your API key')"
                type="password"
                @update:model-value="(v) => scheduleIntegrationSave('activecampaign', { ...acConfig, api_key: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ac-list`"
              >{{ __('List ID') }} <span class="int-card__optional">{{ __('(optional)') }}</span></label>
              <Input
                :id="`${uid}-ac-list`"
                :model-value="acConfig.list_id || ''"
                :placeholder="globalConfigs.activecampaign?.list_id || ''"
                :helper-text="__('Leave blank to use the account default.')"
                @update:model-value="(v) => scheduleIntegrationSave('activecampaign', { ...acConfig, list_id: v })"
              />
            </div>
          </div>
        </article>

        <!-- MailerLite -->
        <article :class="['int-card', { 'is-connected': mlConfig.enabled }]">
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('mailerlite')"
            >
              <component :is="iconFor('mailerlite')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                MailerLite
                <Badge
                  v-if="mlConfig.enabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('Add subscribers to a MailerLite group.') }}
              </div>
            </div>
            <Toggle
              :model-value="Boolean(mlConfig.enabled)"
              size="sm"
              :aria-label="sprintf(__('Enable %s'), 'MailerLite')"
              @update:model-value="(v) => scheduleIntegrationSave('mailerlite', { ...mlConfig, enabled: v })"
            />
          </div>

          <div class="int-card__body">
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ml-key`"
              >{{ __('API Key') }}</label>
              <Input
                :id="`${uid}-ml-key`"
                :model-value="mlConfig.api_key || ''"
                :placeholder="globalConfigs.mailerlite?.api_key ? '••••••••' : __('Paste your API key')"
                type="password"
                @update:model-value="(v) => scheduleIntegrationSave('mailerlite', { ...mlConfig, api_key: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ml-group`"
              >{{ __('Group ID') }} <span class="int-card__optional">{{ __('(optional)') }}</span></label>
              <Input
                :id="`${uid}-ml-group`"
                :model-value="mlConfig.group_id || ''"
                :placeholder="globalConfigs.mailerlite?.group_id || ''"
                @update:model-value="(v) => scheduleIntegrationSave('mailerlite', { ...mlConfig, group_id: v })"
              />
            </div>
          </div>
        </article>

        <!-- Brevo -->
        <article :class="['int-card', { 'is-connected': brevoConfig.enabled }]">
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('brevo')"
            >
              <component :is="iconFor('brevo')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                Brevo
                <Badge
                  v-if="brevoConfig.enabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('Create contacts in Brevo (Sendinblue).') }}
              </div>
            </div>
            <Toggle
              :model-value="Boolean(brevoConfig.enabled)"
              size="sm"
              :aria-label="sprintf(__('Enable %s'), 'Brevo')"
              @update:model-value="(v) => scheduleIntegrationSave('brevo', { ...brevoConfig, enabled: v })"
            />
          </div>

          <div class="int-card__body">
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-brevo-key`"
              >{{ __('API Key') }}</label>
              <Input
                :id="`${uid}-brevo-key`"
                :model-value="brevoConfig.api_key || ''"
                :placeholder="globalConfigs.brevo?.api_key ? '••••••••' : __('Paste your API key')"
                type="password"
                @update:model-value="(v) => scheduleIntegrationSave('brevo', { ...brevoConfig, api_key: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-brevo-list`"
              >{{ __('List ID') }} <span class="int-card__optional">{{ __('(optional)') }}</span></label>
              <Input
                :id="`${uid}-brevo-list`"
                :model-value="brevoConfig.list_id || ''"
                :placeholder="globalConfigs.brevo?.list_id || ''"
                @update:model-value="(v) => scheduleIntegrationSave('brevo', { ...brevoConfig, list_id: v })"
              />
            </div>
          </div>
        </article>

        <!-- Mailchimp -->
        <article :class="['int-card', { 'is-connected': mcConfig.enabled }]">
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('mailchimp')"
            >
              <component :is="iconFor('mailchimp')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                Mailchimp
                <Badge
                  v-if="mcConfig.enabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('Add leads to a Mailchimp audience.') }}
              </div>
            </div>
            <Toggle
              :model-value="Boolean(mcConfig.enabled)"
              size="sm"
              :aria-label="sprintf(__('Enable %s'), 'Mailchimp')"
              @update:model-value="(v) => scheduleIntegrationSave('mailchimp', { ...mcConfig, enabled: v })"
            />
          </div>

          <div class="int-card__body">
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-mc-key`"
              >{{ __('API Key') }}</label>
              <Input
                :id="`${uid}-mc-key`"
                :model-value="mcConfig.api_key || ''"
                :placeholder="globalConfigs.mailchimp?.api_key ? '••••••••' : __('Paste your API key')"
                type="password"
                @update:model-value="(v) => scheduleIntegrationSave('mailchimp', { ...mcConfig, api_key: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-mc-list`"
              >{{ __('Audience ID') }}</label>
              <Input
                :id="`${uid}-mc-list`"
                :model-value="mcConfig.list_id || ''"
                :placeholder="globalConfigs.mailchimp?.list_id || ''"
                @update:model-value="(v) => scheduleIntegrationSave('mailchimp', { ...mcConfig, list_id: v })"
              />
            </div>
          </div>
        </article>

        <!-- ConvertKit -->
        <article :class="['int-card', { 'is-connected': ckConfig.enabled }]">
          <div class="int-card__head">
            <div
              class="int-card__icon"
              :style="iconStyle('convertkit')"
            >
              <component :is="iconFor('convertkit')" />
            </div>
            <div class="int-card__meta">
              <div class="int-card__name">
                ConvertKit
                <Badge
                  v-if="ckConfig.enabled"
                  variant="success"
                  size="sm"
                >
                  {{ __('Connected') }}
                </Badge>
              </div>
              <div class="int-card__desc">
                {{ __('Subscribe leads to a ConvertKit form.') }}
              </div>
            </div>
            <Toggle
              :model-value="Boolean(ckConfig.enabled)"
              size="sm"
              :aria-label="sprintf(__('Enable %s'), 'ConvertKit')"
              @update:model-value="(v) => scheduleIntegrationSave('convertkit', { ...ckConfig, enabled: v })"
            />
          </div>

          <div class="int-card__body">
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ck-secret`"
              >{{ __('API Secret') }}</label>
              <Input
                :id="`${uid}-ck-secret`"
                :model-value="ckConfig.api_secret || ''"
                :placeholder="globalConfigs.convertkit?.api_secret ? '••••••••' : __('Paste your API secret')"
                type="password"
                @update:model-value="(v) => scheduleIntegrationSave('convertkit', { ...ckConfig, api_secret: v })"
              />
            </div>
            <div class="int-card__field">
              <label
                class="int-card__label"
                :for="`${uid}-ck-form`"
              >{{ __('Form ID') }}</label>
              <Input
                :id="`${uid}-ck-form`"
                :model-value="ckConfig.form_id || ''"
                :placeholder="globalConfigs.convertkit?.form_id || ''"
                @update:model-value="(v) => scheduleIntegrationSave('convertkit', { ...ckConfig, form_id: v })"
              />
            </div>
          </div>
        </article>

        <!-- Remaining PRO tiles (Zapier, HubSpot, Google Sheets, etc.) -->
        <div
          v-if="remainingProIntegrations.length"
          class="integrations-grid"
        >
          <button
            v-for="integration in remainingProIntegrations"
            :key="integration.slug || integration.id"
            type="button"
            class="int-tile"
            @click="onUpgrade(integration)"
          >
            <div
              class="int-tile__icon"
              :style="iconStyle(integration.slug)"
            >
              <component :is="iconFor(integration.slug)" />
            </div>
            <div class="int-tile__meta">
              <div class="int-tile__name">
                {{ integration.name || integration.slug }}
              </div>
              <div class="int-tile__desc">
                {{ integration.description || integrationDefaultDesc(integration) }}
              </div>
            </div>
            <Badge
              variant="pro"
              size="sm"
              class="int-tile__pill"
            >
              {{ __('Pro') }}
            </Badge>
          </button>
        </div>
      </section>

      <!-- PRO upsell grid for non-Pro users — a pure teaser, so it exists
           only while Pro promotion is on -->
      <section
        v-else-if="promoVisible && proIntegrations.length"
        class="integrations-section"
      >
        <header class="integrations-section__head">
          <span class="integrations-section__title">{{ __('Premium connectors') }}</span>
          <span class="integrations-section__sub">{{ __('Available with Pro') }}</span>
        </header>

        <div class="integrations-grid">
          <button
            v-for="integration in proIntegrations"
            :key="integration.slug || integration.id"
            type="button"
            class="int-tile"
            @click="onUpgrade(integration)"
          >
            <div
              class="int-tile__icon"
              :style="iconStyle(integration.slug)"
            >
              <component :is="iconFor(integration.slug)" />
            </div>
            <div class="int-tile__meta">
              <div class="int-tile__name">
                {{ integration.name || integration.slug }}
              </div>
              <div class="int-tile__desc">
                {{ integration.description || integrationDefaultDesc(integration) }}
              </div>
            </div>
            <Badge
              variant="pro"
              size="sm"
              class="int-tile__pill"
            >
              {{ __('Pro') }}
            </Badge>
          </button>
        </div>
      </section>
    </template>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue';
import { Badge, Button, Input, Toggle, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { useIntegrationsStore } from '@admin/stores/integrations';
import { isPro, proFeaturesVisible, showProPromo } from '@admin/api/pro.js';
import { api } from '@admin/api/client';
import { __, sprintf } from '@shared/i18n';

const store = useQuizBuilderStore();
const integrationsStore = useIntegrationsStore();
const toast = useToast();

const loading = ref(false);
const testingWebhook = ref(false);
const testingWebhookRowId = ref(null);

const uid = Math.random().toString(36).slice(2, 9);
const webhookInputId = `int-webhook-url-${uid}`;

// ---- Global credentials fetched on mount for placeholder hints ----
const globalConfigs = ref({
  activecampaign: {},
  mailerlite: {},
  brevo: {},
  mailchimp: {},
  convertkit: {},
});

// ---- Quiz-level integrations ----
const quizIntegrations = computed(() => store.quiz?.settings?.integrations ?? {});
const webhookConfig = computed(() => quizIntegrations.value.webhook ?? {});
const webhookUrl = computed(() => webhookConfig.value.url || '');
const isWebhookEnabled = computed(() => Boolean(webhookConfig.value.enabled));

// Pro: multiple webhooks array
const webhooksArray = computed(() => quizIntegrations.value.webhooks ?? []);

const acConfig = computed(() => quizIntegrations.value.activecampaign ?? {});
const mlConfig = computed(() => quizIntegrations.value.mailerlite ?? {});
const brevoConfig = computed(() => quizIntegrations.value.brevo ?? {});
const mcConfig = computed(() => quizIntegrations.value.mailchimp ?? {});
const ckConfig = computed(() => quizIntegrations.value.convertkit ?? {});

const webhookIntegration = computed(() =>
  integrationsStore.items.find((i) => (i.slug || i.id) === 'webhook')
);

// Slugs that get full per-quiz cards in the Pro section
const INLINE_PRO_SLUGS = new Set(['activecampaign', 'mailerlite', 'brevo', 'mailchimp', 'convertkit']);

const proIntegrations = computed(() =>
  integrationsStore.items.filter((i) => i.pro)
);

// Pro tiles shown below the three inline cards (Mailchimp, ConvertKit, etc.)
const remainingProIntegrations = computed(() =>
  proIntegrations.value.filter((i) => !INLINE_PRO_SLUGS.has(i.slug))
);

const isProUser = computed(() => isPro());

// Pro marketing (upsell tiles) vs. tier wording. With no Pro add-on and
// promotion off, the tab is just the free webhook and copy that contrasts
// "free" with "Pro" is dropped.
const promoVisible = computed(() => showProPromo());
const tiersVisible = computed(() => proFeaturesVisible());

const introText = computed(() =>
  tiersVisible.value
    ? __('Send leads and submissions where they need to go. Webhook is free; marketing and CRM integrations unlock with Pro.')
    : __('Send leads and submissions where they need to go with a webhook.')
);

const freeSectionTitle = computed(() => (tiersVisible.value ? __('Free') : __('Webhook')));

const connectedCount = computed(() => {
  let n = 0;
  if (isProUser.value) {
    // Count each enabled webhook in the array.
    n += webhooksArray.value.filter((wh) => wh.enabled).length;
    if (acConfig.value.enabled) n++;
    if (mlConfig.value.enabled) n++;
    if (brevoConfig.value.enabled) n++;
    if (mcConfig.value.enabled) n++;
    if (ckConfig.value.enabled) n++;
  } else {
    if (isWebhookEnabled.value) n++;
  }
  return n;
});

// translators: %d is the number of connected integrations.
const connectedLabel = computed(() => sprintf(__('%d connected'), connectedCount.value));

// translators: %s is the webhook's label, or its position in the list when it has no label.
function webhookToggleLabel(wh, idx) {
  return wh.enabled
    ? sprintf(__('Disable webhook %s'), wh.label || idx + 1)
    : sprintf(__('Enable webhook %s'), wh.label || idx + 1);
}

// ---- Inline SVG icons keyed by integration slug ----
const iconAttrs = {
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': 1.75,
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
  width: 18,
  height: 18,
};

const WebhookIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81a3 3 0 1 0-3-3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9a3 3 0 0 0 0 6c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65a2.92 2.92 0 1 0 2.92-2.92z' }),
  ]);

const MailchimpIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M4 4h16v16H4z' }),
    h('path', { d: 'm4 6 8 7 8-7' }),
  ]);

const ZapierIcon = () =>
  h('svg', iconAttrs, [
    h('polygon', { points: '13 2 3 14 12 14 11 22 21 10 12 10 13 2' }),
  ]);

const HubspotIcon = () =>
  h('svg', iconAttrs, [
    h('circle', { cx: 9, cy: 7, r: 4 }),
    h('path', { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }),
    h('circle', { cx: 17, cy: 4, r: 2 }),
  ]);

const ConvertkitIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M3 8a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z' }),
    h('path', { d: 'm6 9 6 5 6-5' }),
  ]);

const SheetsIcon = () =>
  h('svg', iconAttrs, [
    h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }),
    h('line', { x1: 3, y1: 9, x2: 21, y2: 9 }),
    h('line', { x1: 3, y1: 15, x2: 21, y2: 15 }),
    h('line', { x1: 9, y1: 3, x2: 9, y2: 21 }),
    h('line', { x1: 15, y1: 3, x2: 15, y2: 21 }),
  ]);

const PlugIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M9 7V3M15 7V3M6 11h12v3a6 6 0 0 1-12 0v-3z' }),
    h('line', { x1: 12, y1: 20, x2: 12, y2: 23 }),
  ]);

// ActiveCampaign — lightning bolt (sends/automation signal)
const ActiveCampaignIcon = () =>
  h('svg', iconAttrs, [
    h('path', {
      d: 'M13 2 4.5 13.5H11L10 22l8.5-11.5H13L13 2z',
      'stroke-linejoin': 'round',
    }),
  ]);

// MailerLite — envelope with upward arrow, suggesting delivery
const MailerLiteIcon = () =>
  h('svg', iconAttrs, [
    h('rect', { x: 2, y: 6, width: 20, height: 13, rx: 2 }),
    h('path', { d: 'm2 7 10 8 10-8' }),
    h('path', { d: 'M12 2v5' }),
    h('path', { d: 'M9.5 4.5 12 2l2.5 2.5' }),
  ]);

// Brevo — signal waves / broadcast (communication brand hint)
const BrevoIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M5.6 18.4A9 9 0 0 1 12 3a9 9 0 0 1 6.4 15.4' }),
    h('path', { d: 'M8.4 15.6A5 5 0 0 1 12 7a5 5 0 0 1 3.6 8.6' }),
    h('circle', { cx: 12, cy: 12, r: 1.5, fill: 'currentColor', stroke: 'none' }),
  ]);

const ICON_MAP = {
  webhook: WebhookIcon,
  mailchimp: MailchimpIcon,
  zapier: ZapierIcon,
  hubspot: HubspotIcon,
  convertkit: ConvertkitIcon,
  google_sheets: SheetsIcon,
  activecampaign: ActiveCampaignIcon,
  mailerlite: MailerLiteIcon,
  brevo: BrevoIcon,
};

function iconFor(slug) {
  return ICON_MAP[slug] || PlugIcon;
}

const ICON_PALETTES = {
  webhook: ['#4F46E5', '#818CF8'],
  mailchimp: ['#FFC72C', '#F97316'],
  zapier: ['#FF4A00', '#F97316'],
  hubspot: ['#FF7A59', '#F97316'],
  convertkit: ['#FB6970', '#F472B6'],
  google_sheets: ['#0F9D58', '#34A853'],
  activecampaign: ['#356AE6', '#1B4CB0'],
  mailerlite: ['#09C269', '#07974F'],
  brevo: ['#044BD9', '#022F8A'],
};

function iconStyle(slug) {
  const palette = ICON_PALETTES[slug] || ['#6366F1', '#A78BFA'];
  return {
    background: `linear-gradient(135deg, ${palette[0]}, ${palette[1]})`,
  };
}

function integrationDefaultDesc(integration) {
  switch (integration.slug) {
    case 'mailchimp':
      return __('Add leads to a Mailchimp audience with tags.');
    case 'zapier':
      return __('Trigger Zaps from each submission.');
    case 'hubspot':
      return __('Create HubSpot contacts with form data.');
    case 'convertkit':
      return __('Subscribe leads to a ConvertKit form.');
    case 'google_sheets':
      return __('Append each submission as a new row.');
    case 'activecampaign':
      return __('Sync leads to ActiveCampaign as contacts.');
    case 'mailerlite':
      return __('Add subscribers to a MailerLite group.');
    case 'brevo':
      return __('Create contacts in Brevo (Sendinblue).');
    default:
      return integration.description || '';
  }
}

// ---- Webhook ----
function scheduleWebhookSave(value) {
  store.stageSettings({
    integrations: { ...quizIntegrations.value, webhook: { ...webhookConfig.value, url: value } },
  });
}

function onWebhookEnabled(next) {
  store.stageSettings({
    integrations: { ...quizIntegrations.value, webhook: { ...webhookConfig.value, enabled: next } },
  });
}

async function onTestWebhook() {
  if (testingWebhook.value) return;
  testingWebhook.value = true;
  try {
    const res = await api.post(`quizzes/${store.quiz.id}/test-webhook`, {
      webhook_url: webhookUrl.value,
      webhook_secret: webhookConfig.value.secret || '',
    });
    if (res.ok) {
      toast.push({
        variant: 'success',
        title: __('Test delivered'),
        // translators: %s is the HTTP status code returned by the webhook endpoint.
        message: sprintf(__('HTTP %s'), res.http_status),
      });
    } else {
      toast.push({
        variant: 'danger',
        title: __('Test failed'),
        message: res.message || sprintf(__('HTTP %s'), res.http_status),
      });
    }
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Test failed'),
      message: e.message || __('Unknown error'),
    });
  } finally {
    testingWebhook.value = false;
  }
}

async function onTestWebhookRow(wh) {
  if (testingWebhookRowId.value === wh.id || !wh.url) return;
  testingWebhookRowId.value = wh.id;
  try {
    const res = await api.post(`quizzes/${store.quiz.id}/test-webhook`, {
      webhook_url: wh.url,
      webhook_secret: wh.secret || '',
    });
    if (res.ok) {
      toast.push({ variant: 'success', title: __('Test delivered'), message: sprintf(__('HTTP %s'), res.http_status) });
    } else {
      toast.push({ variant: 'danger', title: __('Test failed'), message: res.message || sprintf(__('HTTP %s'), res.http_status) });
    }
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Test failed'), message: e.message || __('Unknown error') });
  } finally {
    testingWebhookRowId.value = null;
  }
}

// ---- Pro: multiple webhooks ----
function stageWebhooksArray(arr) {
  store.stageSettings({ integrations: { ...quizIntegrations.value, webhooks: arr } });
}

function scheduleWebhooksArraySave(updatedArray) {
  stageWebhooksArray(updatedArray);
}

function updateWebhookRow(idx, field, value) {
  stageWebhooksArray(webhooksArray.value.map((wh, i) => i === idx ? { ...wh, [field]: value } : wh));
}

function updateWebhookRowImmediate(idx, field, value) {
  stageWebhooksArray(webhooksArray.value.map((wh, i) => i === idx ? { ...wh, [field]: value } : wh));
}

function addWebhookRow() {
  stageWebhooksArray([...webhooksArray.value, { id: 'wh_' + Date.now(), label: '', url: '', enabled: true, secret: '' }]);
}

function deleteWebhookRow(idx) {
  stageWebhooksArray(webhooksArray.value.filter((_, i) => i !== idx));
}

// ---- Per-integration save ----
function scheduleIntegrationSave(key, updatedConfig) {
  store.stageSettings({ integrations: { ...quizIntegrations.value, [key]: updatedConfig } });
}

function labelFor(key) {
  const labels = {
    activecampaign: 'ActiveCampaign',
    mailerlite: 'MailerLite',
    brevo: 'Brevo',
    mailchimp: 'Mailchimp',
    convertkit: 'ConvertKit',
  };
  return labels[key] || key;
}

function onUpgrade(integration) {
  if (isProUser.value) {
    toast.push({
      variant: 'success',
      // translators: %s is the integration name, e.g. Mailchimp.
      title: sprintf(__('%s is included with Pro'), integration.name || integration.slug),
      message: __('Configure your API key in the integration settings to start delivering leads.'),
    });
    return;
  }
  toast.push({
    variant: 'info',
    title: __('Pro integration'),
    // translators: %s is the integration name, e.g. Mailchimp.
    message: sprintf(__('%s requires Quizably Pro. Upgrade coming soon.'), integration.name),
  });
}

// ---- Fetch global credentials for placeholder hints ----
async function fetchGlobalConfigs() {
  const keys = ['activecampaign', 'mailerlite', 'brevo', 'mailchimp', 'convertkit'];
  const results = await Promise.allSettled(
    keys.map((k) => api.get(`settings/integrations/${k}`))
  );
  results.forEach((r, i) => {
    if (r.status === 'fulfilled' && r.value && typeof r.value === 'object') {
      globalConfigs.value[keys[i]] = r.value;
    }
  });
}

onMounted(async () => {
  const tasks = [];

  if (!integrationsStore.loaded) {
    loading.value = true;
    tasks.push(
      integrationsStore.fetch().catch((e) => {
        toast.push({
          variant: 'danger',
          title: __('Could not load integrations'),
          message: e.message,
        });
      })
    );
  }

  // Fetch global credentials in parallel; failures are silent (placeholders just stay empty).
  // They only feed placeholders on the Pro connector cards, which render for
  // Pro users alone — everyone else has no use for the lookup.
  if (isProUser.value) {
    tasks.push(fetchGlobalConfigs().catch(() => {}));
  }

  await Promise.all(tasks);
  loading.value = false;

  // Pro migration: if the webhooks array is empty but the legacy single
  // webhook has a URL, seed the array from it (one-time, frontend only).
  if (isProUser.value) {
    const existing = quizIntegrations.value.webhooks ?? [];
    const legacy = quizIntegrations.value.webhook ?? {};
    if (existing.length === 0 && legacy.url) {
      const migrated = [
        {
          id: 'wh_' + Date.now(),
          label: 'Webhook',
          url: legacy.url,
          enabled: Boolean(legacy.enabled),
          secret: legacy.secret ?? '',
        },
      ];
      stageWebhooksArray(migrated);
    }
  }
});
</script>

<style scoped>
/* Scroll container fills the builder body so the page can scroll
   internally. Without this, content overflowing the fullscreen
   builder gets clipped by the outer `.builder { overflow: hidden }`. */
.integrations-scroll {
  height: 100%;
  overflow-y: auto;
  min-height: 0;
}

.integrations-tab {
  max-width: 920px;
  margin: 0 auto;
  padding: 28px 32px 40px;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.integrations-tab__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.integrations-tab__title {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.2;
}

.integrations-tab__desc {
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--ink-3);
  margin: 4px 0 0;
  max-width: 60ch;
}

.integrations-tab__count {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  padding: 4px 10px;
  background: var(--bg-subtle);
  border-radius: var(--r-pill);
}

.integrations-tab__loading {
  padding: 48px;
  text-align: center;
  color: var(--ink-3);
  font-size: 13px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
}

.integrations-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.integrations-section__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.integrations-section__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.integrations-section__sub {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.02em;
}

/* ---- Connected (free + pro inline) card ---- */
.int-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  overflow: hidden;
  transition: border-color 150ms, box-shadow 150ms;
  box-shadow: var(--shadow-xs);
}

.int-card.is-connected {
  border-color: color-mix(in srgb, var(--success) 30%, var(--border-2));
  box-shadow: var(--shadow-sm);
}

.int-card__head {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--border-1);
}

.int-card__icon {
  width: 44px;
  height: 44px;
  border-radius: var(--r-md);
  display: grid;
  place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.int-card__icon :deep(svg) {
  width: 20px;
  height: 20px;
}

.int-card__meta {
  flex: 1;
  min-width: 0;
}

.int-card__name {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-sans);
  font-size: 15px;
  font-weight: 600;
  color: var(--ink-1);
}

.int-card__desc {
  margin-top: 2px;
  font-size: 12.5px;
  color: var(--ink-3);
  line-height: 1.5;
}

.int-card__body {
  padding: 16px 20px 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.int-card__field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.int-card__label {
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.int-card__optional {
  font-weight: 400;
  color: var(--ink-3);
}

.int-card__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

/* ---- Pro grid (remaining tiles below inline cards) ---- */
.integrations-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.int-tile {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  cursor: pointer;
  text-align: start;
  font-family: inherit;
  transition: border-color 150ms, box-shadow 150ms, transform 150ms;
}

.int-tile:hover {
  border-color: var(--border-3);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.int-tile:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.int-tile__icon {
  width: 36px;
  height: 36px;
  border-radius: var(--r-sm);
  display: grid;
  place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.int-tile__icon :deep(svg) {
  width: 18px;
  height: 18px;
}

.int-tile__meta {
  flex: 1;
  min-width: 0;
  padding-inline-end: 36px;
}

.int-tile__name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ink-1);
  margin-bottom: 2px;
}

.int-tile__desc {
  font-size: 12px;
  color: var(--ink-3);
  line-height: 1.4;
}

.int-tile__pill {
  position: absolute;
  top: 14px;
  inset-inline-end: 14px;
}

@media (max-width: 880px) {
  .integrations-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .integrations-grid {
    grid-template-columns: 1fr;
  }
  .int-card__head {
    flex-wrap: wrap;
  }
  .integrations-tab__head {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* ---- Pro: multi-webhook panel ---- */
.wh-multi {
  gap: 0;
  padding: 0;
}

.wh-row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border-1);
}

.wh-row:last-of-type {
  border-bottom: none;
}

.wh-row__fields {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  gap: 10px;
  min-width: 0;
}

.wh-row__controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  padding-bottom: 2px; /* align with input baseline */
}

.wh-row__test,
.wh-row__delete {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  padding: 0;
  background: transparent;
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  color: var(--ink-3);
  cursor: pointer;
  transition: color 150ms, border-color 150ms, background 150ms;
}

.wh-row__test:not(:disabled):hover {
  color: var(--brand);
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 8%, transparent);
}

.wh-row__test:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.wh-row__test:focus-visible,
.wh-row__delete:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.wh-row__delete:hover {
  color: var(--danger, #ef4444);
  border-color: var(--danger, #ef4444);
  background: color-mix(in srgb, var(--danger, #ef4444) 8%, transparent);
}

@keyframes quizably-wh-spin {
  to { transform: rotate(360deg); }
}
.wh-row__spin {
  animation: quizably-wh-spin 0.8s linear infinite;
}

.wh-multi__footer {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
}

.wh-multi__empty {
  font-size: 12.5px;
  color: var(--ink-3);
}

@media (max-width: 720px) {
  .wh-row {
    flex-direction: column;
    align-items: stretch;
  }

  .wh-row__fields {
    grid-template-columns: 1fr;
  }

  .wh-row__controls {
    justify-content: flex-end;
  }
}
</style>
