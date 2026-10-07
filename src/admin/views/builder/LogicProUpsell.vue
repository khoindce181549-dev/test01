<template>
  <div
    v-if="visible"
    class="logic-upsell"
  >
    <div class="logic-upsell__card">
      <div class="logic-upsell__content">
        <div class="logic-upsell__eyebrow">
          <Badge
            variant="pro"
            size="sm"
          >
            Pro
          </Badge>
          <span>{{ __('Logic & branching') }}</span>
        </div>

        <h2 class="logic-upsell__title">
          {{ __('Send people down different paths') }}
        </h2>

        <p class="logic-upsell__lede">
          {{ __('Branching logic lets one quiz behave like many — different questions, skip ahead, or jump straight to a result based on answers.') }}
        </p>

        <ul class="logic-upsell__list">
          <li>
            <span
              class="logic-upsell__check"
              aria-hidden="true"
            />
            <!-- Static, developer-controlled markup inside one translatable string. -->
            <span v-html="perAnswerRule" />
          </li>
          <li>
            <span
              class="logic-upsell__check"
              aria-hidden="true"
            />
            {{ __('Skip questions, end early, or route to a specific result') }}
          </li>
          <li>
            <span
              class="logic-upsell__check"
              aria-hidden="true"
            />
            {{ __('Lead-qualifier flows with the Branching quiz type') }}
          </li>
        </ul>

        <div class="logic-upsell__cta">
          <a
            href="https://wordpress.org/plugins/quizably/"
            class="logic-upsell__btn"
            target="_blank"
            rel="noopener"
          >
            {{ __('Learn about Pro') }}
            <svg
              viewBox="0 0 16 16"
              width="12"
              height="12"
              aria-hidden="true"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M3 8h10M9 4l4 4-4 4" />
            </svg>
          </a>
          <span class="logic-upsell__sub">{{ __('Activate Pro to enable this tab.') }}</span>
        </div>
      </div>

      <div class="logic-upsell__visual">
        <svg
          viewBox="0 0 280 180"
          aria-hidden="true"
        >
          <defs>
            <marker
              id="quizably-logic-arrow"
              viewBox="0 0 10 10"
              refX="9"
              refY="5"
              markerWidth="6"
              markerHeight="6"
              orient="auto"
            >
              <path
                d="M0 0 L10 5 L0 10 z"
                fill="currentColor"
              />
            </marker>
          </defs>

          <rect
            x="10"
            y="72"
            width="86"
            height="36"
            rx="9"
            class="logic-upsell__node logic-upsell__node--start"
          />
          <text
            x="53"
            y="95"
            text-anchor="middle"
            class="logic-upsell__nodelabel"
          >{{ __('Question 1') }}</text>

          <rect
            x="120"
            y="14"
            width="86"
            height="36"
            rx="9"
            class="logic-upsell__node"
          />
          <text
            x="163"
            y="37"
            text-anchor="middle"
            class="logic-upsell__nodelabel"
          >{{ __('Question 2') }}</text>

          <rect
            x="120"
            y="130"
            width="86"
            height="36"
            rx="9"
            class="logic-upsell__node"
          />
          <text
            x="163"
            y="153"
            text-anchor="middle"
            class="logic-upsell__nodelabel"
          >{{ __('Question 5') }}</text>

          <rect
            x="226"
            y="72"
            width="48"
            height="36"
            rx="9"
            class="logic-upsell__node logic-upsell__node--end"
          />
          <text
            x="250"
            y="95"
            text-anchor="middle"
            class="logic-upsell__nodelabel logic-upsell__nodelabel--end"
          >{{ __('Result') }}</text>

          <path
            d="M96 84 C 110 50, 110 40, 120 32"
            class="logic-upsell__edge"
            marker-end="url(#quizably-logic-arrow)"
          />
          <text
            x="103"
            y="62"
            class="logic-upsell__edgelabel"
          >{{ __('if A') }}</text>

          <path
            d="M96 96 C 110 130, 110 140, 120 148"
            class="logic-upsell__edge"
            marker-end="url(#quizably-logic-arrow)"
          />
          <text
            x="103"
            y="124"
            class="logic-upsell__edgelabel"
          >{{ __('if B') }}</text>

          <path
            d="M206 32 C 220 56, 222 78, 226 88"
            class="logic-upsell__edge"
            marker-end="url(#quizably-logic-arrow)"
          />
          <path
            d="M206 148 C 220 124, 222 102, 226 92"
            class="logic-upsell__edge"
            marker-end="url(#quizably-logic-arrow)"
          />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Badge } from '@admin/ui';
import { proFeaturesVisible } from '@admin/api/pro.js';
import { __ } from '@shared/i18n';

// Pure teaser: renders nothing while Pro isn't active AND promotion is off
// (the free plugin doesn't mention Pro until a Pro product exists). It uses
// proFeaturesVisible() rather than showProPromo() so that an active-Pro
// install whose Logic editor hasn't registered yet still gets this card as
// the LogicTab fallback instead of a blank pane, exactly as before.
const visible = computed(() => proFeaturesVisible());

// Contains static <em> markup (rendered with v-html); the whole sentence is one
// translatable string so translators can reorder it.
const perAnswerRule = __('Per-answer rules — <em>if "Beginner" → jump to Q5</em>');
</script>

<style scoped>
.logic-upsell {
  display: flex;
  justify-content: center;
  padding: 24px 24px 32px;
}

.logic-upsell__card {
  width: 100%;
  max-width: 880px;
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  align-items: center;
  gap: 24px;
  padding: 22px 26px;
  background:
    linear-gradient(135deg, var(--accent-tint) 0%, var(--bg-surface) 65%);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
}

.logic-upsell__content {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.logic-upsell__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
  margin-bottom: 2px;
}

.logic-upsell__title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 22px;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.logic-upsell__lede {
  font-family: var(--f-sans);
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--ink-2);
  margin: 0;
  max-width: 52ch;
}

.logic-upsell__list {
  list-style: none;
  margin: 6px 0 4px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.logic-upsell__list li {
  display: flex;
  align-items: center;
  gap: 9px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.5;
  color: var(--ink-2);
}

.logic-upsell__list em {
  font-style: italic;
  color: var(--brand-hover);
}

.logic-upsell__check {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--success-bg) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' stroke='%23059669' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round' fill='none'/%3E%3C/svg%3E")
    center / 10px no-repeat;
}

.logic-upsell__cta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.logic-upsell__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-sm);
  font-family: var(--f-sans);
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: var(--shadow-sm);
  transition: background 150ms, transform 150ms, box-shadow 150ms;
}

.logic-upsell__btn:hover {
  background: var(--brand-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.logic-upsell__sub {
  font-family: var(--f-sans);
  font-size: 11.5px;
  color: var(--ink-3);
}

.logic-upsell__visual {
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 10px;
  min-width: 0;
}

.logic-upsell__visual svg {
  width: 100%;
  max-width: 280px;
  height: auto;
  color: var(--brand);
}

.logic-upsell__node {
  fill: var(--bg-surface);
  stroke: var(--border-2);
  stroke-width: 1.4;
}

.logic-upsell__node--start {
  fill: var(--brand-tint);
  stroke: var(--brand);
}

.logic-upsell__node--end {
  fill: var(--accent-bg);
  stroke: var(--accent);
}

.logic-upsell__nodelabel {
  font-family: var(--f-sans);
  font-size: 9.5px;
  font-weight: 600;
  fill: var(--ink-1);
}

.logic-upsell__nodelabel--end {
  fill: var(--accent);
}

.logic-upsell__edge {
  fill: none;
  stroke: currentColor;
  stroke-width: 1.4;
  stroke-dasharray: 4 3;
}

.logic-upsell__edgelabel {
  font-family: var(--f-mono);
  font-size: 8.5px;
  fill: var(--ink-3);
  letter-spacing: 0.04em;
}

@media (max-width: 880px) {
  .logic-upsell__card {
    grid-template-columns: 1fr;
  }
  .logic-upsell__visual {
    order: -1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .logic-upsell__btn {
    transition: none;
  }
}
</style>
