<template>
  <figure class="quizably-rchart" :aria-label="chartAriaLabel">
    <!-- Pie / Donut -->
    <div
      v-if="type === 'pie' || type === 'donut'"
      class="quizably-rchart__ring-wrap"
    >
      <svg
        viewBox="0 0 200 200"
        class="quizably-rchart__svg"
        role="img"
        aria-hidden="true"
      >
        <defs>
          <filter id="quizably-chart-shadow" x="-10%" y="-10%" width="120%" height="120%">
            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(0,0,0,0.12)" />
          </filter>
        </defs>
        <g :filter="segments.length > 1 ? 'url(#quizably-chart-shadow)' : undefined">
          <!-- Single-segment: full circle -->
          <g v-if="slices.length === 1">
            <circle cx="100" cy="100" :r="outerR" :fill="slices[0].color" />
          </g>
          <!-- Multi-segment: slices -->
          <g v-else>
            <path
              v-for="(sl, i) in slices"
              :key="i"
              :d="sl.path"
              :fill="sl.color"
            />
          </g>
        </g>
        <!-- Donut hole -->
        <circle
          v-if="type === 'donut'"
          cx="100"
          cy="100"
          :r="holeR"
          class="quizably-rchart__hole"
        />
      </svg>

      <div
        v-if="type === 'donut' && centerText"
        class="quizably-rchart__center"
        aria-hidden="true"
      >
        <span class="quizably-rchart__center-val">{{ centerText }}</span>
        <span v-if="centerSub" class="quizably-rchart__center-sub">{{ centerSub }}</span>
      </div>
    </div>

    <!-- Bar chart — stacked label/pct header above full-width track -->
    <div
      v-else-if="type === 'bar'"
      class="quizably-rchart__bars"
      role="list"
    >
      <div
        v-for="seg in segments"
        :key="seg.label"
        class="quizably-rchart__bar-row"
        role="listitem"
      >
        <div class="quizably-rchart__bar-meta">
          <span class="quizably-rchart__bar-label">{{ seg.label }}</span>
          <span class="quizably-rchart__bar-pct">{{ seg.pct }}%</span>
        </div>
        <div class="quizably-rchart__bar-track">
          <div
            class="quizably-rchart__bar-fill"
            :style="{ width: seg.pct + '%', '--seg-color': seg.color }"
          />
        </div>
      </div>
    </div>

    <!-- Legend (pie / donut, multiple segments) -->
    <div
      v-if="type !== 'bar' && segments.length > 1"
      class="quizably-rchart__legend"
      aria-hidden="true"
    >
      <div
        v-for="seg in segments"
        :key="seg.label"
        class="quizably-rchart__legend-item"
      >
        <span class="quizably-rchart__legend-swatch" :style="{ background: seg.color }" />
        <span class="quizably-rchart__legend-label">{{ seg.label }}</span>
        <span class="quizably-rchart__legend-pct">{{ seg.pct }}%</span>
      </div>
    </div>
  </figure>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';

const props = defineProps({
  type: { type: String, default: 'donut' }, // 'pie' | 'donut' | 'bar'
  segments: { type: Array, default: () => [] }, // [{ label, value, pct, color }]
  centerText: { type: String, default: '' },
  centerSub: { type: String, default: '' },
  title: { type: String, default: () => __('Result chart') },
});

const outerR = 88;
const holeR = 56;

const chartAriaLabel = computed(() => {
  const parts = (props.segments ?? []).map((s) => `${s.label}: ${s.pct}%`).join(', ');
  return `${props.title}. ${parts}`;
});

function deg2rad(deg) {
  return ((deg - 90) * Math.PI) / 180;
}

function point(angleDeg, r) {
  return [
    (100 + r * Math.cos(deg2rad(angleDeg))).toFixed(3),
    (100 + r * Math.sin(deg2rad(angleDeg))).toFixed(3),
  ];
}

function slicePath(startDeg, endDeg, donut) {
  const G = props.segments.length > 1 ? 2 : 0;
  const s = startDeg + G;
  const e = endDeg - G;
  const large = e - s > 180 ? 1 : 0;
  const [ox1, oy1] = point(s, outerR);
  const [ox2, oy2] = point(e, outerR);
  if (donut) {
    const ir = holeR + 2;
    const [ix1, iy1] = point(s, ir);
    const [ix2, iy2] = point(e, ir);
    return `M ${ox1} ${oy1} A ${outerR} ${outerR} 0 ${large} 1 ${ox2} ${oy2} L ${ix2} ${iy2} A ${ir} ${ir} 0 ${large} 0 ${ix1} ${iy1} Z`;
  }
  return `M 100 100 L ${ox1} ${oy1} A ${outerR} ${outerR} 0 ${large} 1 ${ox2} ${oy2} Z`;
}

const slices = computed(() => {
  if (!props.segments.length) return [];
  const isDonut = props.type === 'donut';
  const total = props.segments.reduce((s, seg) => s + seg.value, 0);
  if (total <= 0) return [];
  let cursor = 0;
  return props.segments.map((seg) => {
    const deg = (seg.value / total) * 360;
    const path = slicePath(cursor, cursor + deg, isDonut);
    cursor += deg;
    return { path, color: seg.color };
  });
});
</script>

<style scoped>
.quizably-rchart {
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  width: 100%;
}

/* ---- Ring (pie / donut) ---- */
.quizably-rchart__ring-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: min(200px, 100%);
}

.quizably-rchart__svg {
  width: 100%;
  height: auto;
  display: block;
  overflow: visible;
}

.quizably-rchart__hole {
  fill: var(--quizably-result-bg, var(--quizably-quiz-bg, var(--bg-surface, #ffffff)));
}

.quizably-rchart__center {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  pointer-events: none;
}

.quizably-rchart__center-val {
  font-family: var(--f-display, serif);
  font-size: clamp(24px, 4vw, 34px);
  font-weight: 500;
  line-height: 1;
  letter-spacing: -0.03em;
  color: var(--ink-1, #0a0a0b);
}

.quizably-rchart__center-sub {
  font-size: 11px;
  color: var(--ink-3, #6b7280);
  font-family: var(--f-mono, monospace);
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

/* ---- Bar chart ---- */
.quizably-rchart__bars {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.quizably-rchart__bar-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.quizably-rchart__bar-meta {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.quizably-rchart__bar-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-2, #374151);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  min-width: 0;
}

.quizably-rchart__bar-pct {
  font-family: var(--f-mono, monospace);
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-1, #0a0a0b);
  flex-shrink: 0;
}

.quizably-rchart__bar-track {
  height: 10px;
  background: color-mix(in srgb, currentColor 8%, transparent);
  border-radius: 999px;
  overflow: hidden;
}

.quizably-rchart__bar-fill {
  height: 100%;
  border-radius: 999px;
  background: var(--seg-color, var(--quizably-quiz-brand, #6d28d9));
  transition: width 700ms cubic-bezier(0.34, 1.1, 0.64, 1);
}

/* ---- Legend ---- */
.quizably-rchart__legend {
  display: flex;
  flex-direction: column;
  gap: 7px;
  width: 100%;
}

.quizably-rchart__legend-item {
  display: grid;
  grid-template-columns: 12px 1fr auto;
  align-items: center;
  gap: 9px;
}

.quizably-rchart__legend-swatch {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  flex-shrink: 0;
}

.quizably-rchart__legend-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-2, #374151);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-rchart__legend-pct {
  font-family: var(--f-mono, monospace);
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-1, #0a0a0b);
}
</style>
