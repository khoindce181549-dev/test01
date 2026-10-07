<template>
  <div class="quizably-chart">
    <header class="quizably-chart__head">
      <div>
        <h3 class="quizably-chart__title">{{ __('Submissions, last 30 days') }}</h3>
        <p class="quizably-chart__sub">{{ completedText }}</p>
      </div>
      <div class="quizably-chart__legend">
        <span class="quizably-chart__legend-dot" />
        <span>{{ __('Daily completes') }}</span>
      </div>
    </header>

    <div
      v-if="!series.length"
      class="quizably-chart__empty"
    >
      {{ __('No submissions yet — share a quiz link to get rolling.') }}
    </div>

    <svg
      v-else
      :viewBox="`0 0 ${VB_W} ${VB_H}`"
      class="quizably-chart__svg"
      dir="ltr"
      preserveAspectRatio="none"
      role="img"
      :aria-label="chartAriaLabel"
    >
      <!-- Y-axis grid -->
      <g class="quizably-chart__grid">
        <line
          v-for="g in gridLines"
          :key="g.y"
          x1="0"
          :x2="VB_W"
          :y1="g.y"
          :y2="g.y"
        />
      </g>

      <!-- Area under the line -->
      <path
        :d="areaPath"
        class="quizably-chart__area"
      />

      <!-- Line itself -->
      <path
        :d="linePath"
        class="quizably-chart__line"
      />

      <!-- Data point dots -->
      <g class="quizably-chart__dots">
        <circle
          v-for="(p, i) in points"
          :key="i"
          :cx="p.x"
          :cy="p.y"
          r="2.5"
        />
      </g>

      <!-- Hover hit areas — invisible vertical strips that show a label on hover -->
      <g class="quizably-chart__hits">
        <rect
          v-for="(p, i) in points"
          :key="`hit-${i}`"
          :x="p.x - hitWidth / 2"
          y="0"
          :width="hitWidth"
          :height="VB_H"
          class="quizably-chart__hit"
          @mouseenter="hovered = i"
          @mouseleave="hovered = null"
        />
      </g>

      <!-- Hover marker -->
      <g
        v-if="hoveredPoint"
        class="quizably-chart__marker"
      >
        <line
          :x1="hoveredPoint.x"
          :x2="hoveredPoint.x"
          y1="0"
          :y2="VB_H - 12"
          class="quizably-chart__marker-line"
        />
        <circle
          :cx="hoveredPoint.x"
          :cy="hoveredPoint.y"
          r="4"
          class="quizably-chart__marker-dot"
        />
      </g>
    </svg>

    <div
      v-if="hoveredPoint"
      class="quizably-chart__tooltip"
      :style="tooltipStyle"
    >
      <strong>{{ hoveredPoint.count }}</strong>
      <span>{{ hoveredPoint.dateLabel }}</span>
    </div>
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, ref } from 'vue';

const props = defineProps({
  series: { type: Array, default: () => [] }, // [{ date: 'YYYY-MM-DD', count }]
});

const VB_W = 600;
const VB_H = 160;
const PAD_X = 8;
const PAD_Y = 14;

const points = computed(() => {
  const series = props.series ?? [];
  if (!series.length) return [];
  const max = Math.max(1, ...series.map((d) => Number(d.count) || 0));
  const innerW = VB_W - PAD_X * 2;
  const innerH = VB_H - PAD_Y * 2;
  const stepX = series.length > 1 ? innerW / (series.length - 1) : 0;
  return series.map((d, i) => {
    const value = Number(d.count) || 0;
    const ratio = max === 0 ? 0 : value / max;
    return {
      x: PAD_X + i * stepX,
      y: PAD_Y + (1 - ratio) * innerH,
      count: value,
      date: d.date,
      dateLabel: formatDate(d.date),
    };
  });
});

const linePath = computed(() => {
  if (!points.value.length) return '';
  return points.value
    .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(2)} ${p.y.toFixed(2)}`)
    .join(' ');
});

const areaPath = computed(() => {
  if (!points.value.length) return '';
  const baseY = VB_H - PAD_Y;
  const first = points.value[0];
  const last = points.value[points.value.length - 1];
  const lineSegment = points.value
    .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(2)} ${p.y.toFixed(2)}`)
    .join(' ');
  return `${lineSegment} L ${last.x.toFixed(2)} ${baseY} L ${first.x.toFixed(2)} ${baseY} Z`;
});

const gridLines = computed(() => {
  const innerH = VB_H - PAD_Y * 2;
  return [0, 0.25, 0.5, 0.75, 1].map((r) => ({ y: PAD_Y + r * innerH }));
});

const hitWidth = computed(() => {
  if (points.value.length < 2) return 30;
  return (points.value[1].x - points.value[0].x);
});

const hovered = ref(null);

const hoveredPoint = computed(() => {
  if (hovered.value === null) return null;
  return points.value[hovered.value] ?? null;
});

const tooltipStyle = computed(() => {
  if (!hoveredPoint.value) return {};
  const ratio = hoveredPoint.value.x / VB_W;
  return {
    left: `${ratio * 100}%`,
    top: `${(hoveredPoint.value.y / VB_H) * 100}%`,
  };
});

const totalLabel = computed(() => {
  const total = props.series.reduce((acc, d) => acc + (Number(d.count) || 0), 0);
  return total.toLocaleString();
});

// translators: %s is a formatted number of completed submissions.
const completedText = computed(() => sprintf(__('%s completed'), totalLabel.value));

// translators: %s is a formatted number of submissions.
const chartAriaLabel = computed(() => sprintf(__('%s submissions over the last 30 days'), totalLabel.value));

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso + 'T00:00:00');
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
</script>

<style scoped>
.quizably-chart {
  position: relative;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quizably-chart__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.quizably-chart__title {
  margin: 0;
  font-family: var(--f-display, var(--f-sans));
  font-size: 16px;
  font-weight: 600;
  color: var(--ink-1);
}

.quizably-chart__sub {
  margin: 4px 0 0;
  font-size: 12.5px;
  color: var(--ink-3);
}

.quizably-chart__legend {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-3);
}

.quizably-chart__legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--brand);
}

.quizably-chart__svg {
  display: block;
  width: 100%;
  height: 160px;
  overflow: visible;
}

.quizably-chart__grid line {
  stroke: var(--border-1);
  stroke-width: 1;
  stroke-dasharray: 2 4;
}

.quizably-chart__area {
  fill: var(--brand);
  opacity: 0.12;
}

.quizably-chart__line {
  fill: none;
  stroke: var(--brand);
  stroke-width: 2;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.quizably-chart__dots circle {
  fill: var(--brand);
  opacity: 0.6;
}

.quizably-chart__hits .quizably-chart__hit {
  fill: transparent;
  cursor: crosshair;
}

.quizably-chart__marker-line {
  stroke: var(--ink-3);
  stroke-width: 1;
  stroke-dasharray: 3 3;
  opacity: 0.6;
}

.quizably-chart__marker-dot {
  fill: var(--brand);
  stroke: var(--bg-surface);
  stroke-width: 2;
}

.quizably-chart__empty {
  padding: 32px 12px;
  text-align: center;
  color: var(--ink-3);
  font-size: 13px;
  border: 1px dashed var(--border-2);
  border-radius: var(--r-sm);
}

.quizably-chart__tooltip {
  position: absolute;
  transform: translate(-50%, -125%);
  background: var(--ink-1);
  color: #fff;
  padding: 6px 10px;
  border-radius: var(--r-sm);
  font-size: 12px;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
  white-space: nowrap;
  box-shadow: var(--shadow-md);
}

.quizably-chart__tooltip strong {
  font-family: var(--f-mono);
  font-size: 13px;
  font-weight: 700;
}

.quizably-chart__tooltip span {
  font-size: 10.5px;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 2px;
}
</style>
