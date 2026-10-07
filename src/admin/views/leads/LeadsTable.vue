<template>
  <Card padding="none">
    <table class="quizably-leadstable">
      <thead>
        <tr>
          <th scope="col">{{ __('Email') }}</th>
          <th v-if="showQuiz" scope="col">{{ __('Quiz') }}</th>
          <th scope="col">{{ __('Name') }}</th>
          <th scope="col">{{ __('Submitted at') }}</th>
          <th scope="col">{{ __('Sync status') }}</th>
          <th scope="col" class="quizably-leadstable__actions-col">
            <span class="quizably-leadstable__sr">{{ __('Actions') }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="lead in leads"
          :key="lead.id"
          class="quizably-leadstable__row"
        >
          <td class="quizably-leadstable__email">
            <span class="quizably-leadstable__email-address">{{ lead.email || '—' }}</span>
            <div
              v-if="utmSource(lead)"
              class="quizably-leadstable__utm"
              :title="utmTooltip(lead)"
            >
              <span class="quizably-leadstable__utm-badge">{{ __('UTM') }}</span>
              <span class="quizably-leadstable__utm-text">{{ utmSource(lead) }}</span>
              <span v-if="utmMedium(lead)" class="quizably-leadstable__utm-sep">/</span>
              <span v-if="utmMedium(lead)" class="quizably-leadstable__utm-text">{{ utmMedium(lead) }}</span>
            </div>
          </td>
          <td v-if="showQuiz" class="quizably-leadstable__quiz">
            {{ quizMap[String(lead.quiz_id)] || `#${lead.quiz_id}` }}
          </td>
          <td class="quizably-leadstable__name">
            {{ lead.name || '—' }}
          </td>
          <td class="quizably-leadstable__time">
            {{ formatTime(lead.created_at) }}
          </td>
          <td>
            <div class="quizably-leadstable__syncs">
              <template v-if="syncEntries(lead).length">
                <Badge
                  v-for="entry in syncEntries(lead)"
                  :key="entry.integration"
                  :variant="syncVariant(entry.status)"
                  size="sm"
                >
                  {{ entry.integration }}: {{ entry.status }}
                </Badge>
              </template>
              <Badge
                v-else
                variant="neutral"
                size="sm"
              >
                {{ __('Not synced') }}
              </Badge>
            </div>
          </td>
          <td class="quizably-leadstable__actions">
            <button
              class="quizably-leadstable__icon-btn"
              type="button"
              :title="__('View submission detail')"
              @click="emit('view', lead)"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
            <button
              class="quizably-leadstable__icon-btn quizably-leadstable__icon-btn--danger"
              type="button"
              :title="__('Delete lead')"
              @click="emit('remove', lead.id)"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
              </svg>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </Card>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { Badge, Card } from '@admin/ui';

defineProps({
  leads: { type: Array, required: true },
  showQuiz: { type: Boolean, default: false },
  quizMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['remove', 'view']);

/**
 * UTM helpers — utm_data may come as { utm_source, utm_medium, ... } on the
 * lead object when the submission had UTM params at quiz-start time.
 */
function utmSource(lead) {
  return lead.utm_data?.utm_source || null;
}

function utmMedium(lead) {
  return lead.utm_data?.utm_medium || null;
}

function utmTooltip(lead) {
  const utm = lead.utm_data;
  if (!utm) return '';
  const parts = [];
  // translators: %s is a UTM parameter value (campaign tracking) in a tooltip.
  if (utm.utm_source) parts.push(sprintf(__('source: %s'), utm.utm_source));
  // translators: %s is a UTM parameter value (campaign tracking) in a tooltip.
  if (utm.utm_medium) parts.push(sprintf(__('medium: %s'), utm.utm_medium));
  // translators: %s is a UTM parameter value (campaign tracking) in a tooltip.
  if (utm.utm_campaign) parts.push(sprintf(__('campaign: %s'), utm.utm_campaign));
  // translators: %s is a UTM parameter value (campaign tracking) in a tooltip.
  if (utm.utm_term) parts.push(sprintf(__('term: %s'), utm.utm_term));
  // translators: %s is a UTM parameter value (campaign tracking) in a tooltip.
  if (utm.utm_content) parts.push(sprintf(__('content: %s'), utm.utm_content));
  return parts.join(' · ');
}

function syncEntries(lead) {
  const raw = lead.integration_sync_status;
  if (!raw || typeof raw !== 'object') return [];
  return Object.entries(raw).map(([integration, status]) => ({
    integration,
    status: typeof status === 'string' ? status : String(status),
  }));
}

function syncVariant(status) {
  switch (status) {
    case 'synced':
    case 'ok':
    case 'success':
      return 'success';
    case 'failed':
    case 'error':
      return 'danger';
    case 'pending':
    case 'queued':
      return 'info';
    default:
      return 'neutral';
  }
}

function formatTime(isoish) {
  if (!isoish) return '—';
  const then = new Date(isoish).getTime();
  if (!Number.isFinite(then)) return '—';
  const d = new Date(isoish);
  return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
}
</script>

<style scoped>
.quizably-leadstable {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
}

.quizably-leadstable thead th {
  text-align: start;
  font-family: var(--f-mono);
  font-weight: 500;
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-1);
  background: var(--bg-canvas);
  white-space: nowrap;
}

.quizably-leadstable tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-1);
  vertical-align: middle;
}

.quizably-leadstable tbody tr:last-child td {
  border-bottom: 0;
}

.quizably-leadstable__row:hover {
  background: var(--bg-subtle);
}

.quizably-leadstable__email {
  font-weight: 500;
  color: var(--ink-1);
  max-width: 320px;
  overflow: hidden;
}

.quizably-leadstable__email-address {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-leadstable__utm {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 3px;
  flex-wrap: nowrap;
  overflow: hidden;
}

.quizably-leadstable__utm-badge {
  font-family: var(--f-mono);
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--brand);
  background: color-mix(in srgb, var(--brand) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--brand) 25%, transparent);
  border-radius: 3px;
  padding: 1px 4px;
  line-height: 1.4;
  flex-shrink: 0;
}

.quizably-leadstable__utm-text {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-leadstable__utm-sep {
  color: var(--ink-4);
  font-size: 11px;
  flex-shrink: 0;
}

.quizably-leadstable__quiz {
  font-size: 12.5px;
  color: var(--ink-3);
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-leadstable__name {
  color: var(--ink-2);
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-leadstable__time {
  font-family: var(--f-mono);
  font-size: 11.5px;
  letter-spacing: 0.03em;
  color: var(--ink-4);
  white-space: nowrap;
}

.quizably-leadstable__syncs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
}

.quizably-leadstable__actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.quizably-leadstable__icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  color: var(--ink-3);
  cursor: pointer;
  transition: background 100ms, border-color 100ms, color 100ms;
  padding: 0;
}

.quizably-leadstable__icon-btn:hover {
  background: var(--bg-subtle);
  border-color: var(--border-2);
  color: var(--ink-1);
}

.quizably-leadstable__icon-btn--danger:hover {
  background: color-mix(in srgb, #dc2626 8%, var(--bg-canvas));
  border-color: color-mix(in srgb, #dc2626 35%, transparent);
  color: #dc2626;
}

.quizably-leadstable__actions-col {
  width: 1%;
  text-align: end;
}

.quizably-leadstable__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
