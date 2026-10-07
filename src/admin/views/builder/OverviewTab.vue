<template>
  <div class="overview-tab">
    <div class="overview-tab__inner">
      <header class="overview-tab__head">
        <span class="overview-tab__eyebrow">{{ blueprint.eyebrow }}</span>
        <h1 class="overview-tab__title">{{ blueprint.title }}</h1>
        <p class="overview-tab__lede">{{ blueprint.lede }}</p>
      </header>

      <section class="overview-tab__cards">
        <article
          v-for="(step, i) in blueprint.steps"
          :key="step.tab"
          class="overview-card"
          :class="{ 'is-done': stepIsDone(step) }"
          :data-testid="`overview-step-${step.tab}`"
        >
          <div class="overview-card__num">{{ i + 1 }}</div>
          <div class="overview-card__body">
            <h3 class="overview-card__title">{{ step.title }}</h3>
            <p class="overview-card__desc">{{ step.desc }}</p>
            <button
              type="button"
              class="overview-card__cta"
              @click="goTo(step.tab)"
            >
              {{ stepIsDone(step) ? __('Review') : __('Open') }}
              <span class="q-flip-rtl" aria-hidden="true">→</span>
            </button>
          </div>
        </article>
      </section>

      <section
        v-if="blueprint.tips.length"
        class="overview-tab__tips"
      >
        <h3 class="overview-tab__tips-title">{{ tipsTitle }}</h3>
        <ul>
          <li
            v-for="(tip, i) in blueprint.tips"
            :key="i"
          >
            {{ tip }}
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { proFeaturesVisible } from '@admin/api/pro.js';
import { __, sprintf } from '@shared/i18n';

const router = useRouter();
const route = useRoute();
const store = useQuizBuilderStore();

const quizType = computed(() => String(store.quiz?.type ?? 'personality').toLowerCase());

const BLUEPRINTS = {
  personality: {
    eyebrow: __('Personality quiz'),
    shortName: __('Personality'),
    title: __('Match every visitor to one of your results'),
    lede: __('Visitors answer questions, each answer points to a result, and the most-picked result wins. Build the results first so your answers have somewhere to map to.'),
    steps: [
      { tab: 'results', title: __('Define your results'), desc: __('Create the personalities or archetypes a visitor can land on (e.g. "Adventurer", "Analyst").'), requires: () => store.results.length >= 2 },
      { tab: 'questions', title: __('Add questions and map answers'), desc: __('Each answer points to one result. The result with the most points wins.'), requires: () => store.questions.length >= 1 },
      { tab: 'logic', pro: true, title: __('Add branching (optional)'), desc: __('Send people to specific questions or skip ahead based on what they pick.') },
      { tab: 'intro', title: __('Write the intro'), desc: __('A great hook gets people to start. Add a title, image, and quick description.') },
      { tab: 'form', title: __('Capture leads (optional)'), desc: __('Collect email before showing the result, or after — your call.') },
      { tab: 'design', title: __('Style it'), desc: __('Pick a template, brand color, fonts. Looks like your site, not a generic plugin.') },
      { tab: 'integrations', title: __('Send leads to your CRM'), desc: __('Webhook for free; Mailchimp / ConvertKit / etc. with Pro.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('Timer, randomize, GDPR consent — all the per-quiz behavior controls live here.') },
    ],
    tips: [
      __('Aim for 5–10 questions. Shorter completes better.'),
      __('Every answer must map to exactly one result — visitors who skip mappings end up on a random result.'),
      __('Results get tied. The first one wins by default; reorder them in the Results tab so the "preferred" tie-break is first.'),
    ],
    free: {
      desc: {
        integrations: __('Send each new lead to your CRM or automation tool with a webhook.'),
        settings: __('Auto-advance, answer review and other per-quiz behavior controls live here.'),
      },
    },
  },
  weighted: {
    eyebrow: __('Weighted assessment'),
    shortName: __('Weighted'),
    title: __('Score across multiple dimensions'),
    lede: __('Each answer adds points to one or more categories (think Big-Five, OCEAN, archetypes). The dominant category — or full score profile — drives the result.'),
    steps: [
      { tab: 'results', title: __('Define your categories'), desc: __('Create one result per dimension. The "weights" you assign on answers reference these.'), requires: () => store.results.length >= 2 },
      { tab: 'questions', title: __('Add questions with weighted answers'), desc: __('Each answer carries a weight per category. Set positive weights to add, negative to subtract.'), requires: () => store.questions.length >= 1 },
      { tab: 'logic', pro: true, title: __('Add branching (optional)'), desc: __('Skip irrelevant blocks based on early answers — e.g. ask "B2B vs B2C" then branch.') },
      { tab: 'intro', title: __('Write the intro'), desc: __('Set expectations: how long, what they get, why it matters.') },
      { tab: 'form', title: __('Capture leads'), desc: __('Common pattern: gate the result behind email so you can deliver it personalized.') },
      { tab: 'design', title: __('Style it'), desc: __('Brand color, fonts, layout — keep it on-brand.') },
      { tab: 'integrations', title: __('Send leads + scores'), desc: __('Webhook payload includes the full score breakdown so your CRM can segment.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('Timer, retake policy, anything quiz-wide.') },
    ],
    tips: [
      __('Weighted is overkill for "match me to one of three personas" — use Personality for that.'),
      __('Negative weights work — useful for "this answer rules out this category".'),
      __('Keep weights small (1–5). Big numbers swing the result on a single answer.'),
    ],
    free: {
      desc: {
        settings: __('Auto-advance, answer review, anything quiz-wide.'),
      },
    },
  },
  trivia: {
    eyebrow: __('Trivia / score'),
    shortName: __('Trivia'),
    title: __('Right or wrong, with a final score'),
    lede: __('Each answer is correct or incorrect. Right answers add points; the final score lands the visitor in a band ("Beginner", "Expert", etc.).'),
    steps: [
      { tab: 'questions', title: __('Add questions and mark the correct answer'), desc: __("For each question, tick which answer is right and how many points it's worth."), requires: () => store.questions.length >= 1 },
      { tab: 'results', title: __('Define score bands'), desc: __('Set the min/max score range for each band ("0–4 = Novice", "5–8 = Expert", etc.).'), requires: () => store.results.length >= 1 },
      { tab: 'logic', pro: true, title: __('Add branching (optional)'), desc: __('Useful for adaptive quizzes — wrong answer? Send them to a refresher question.') },
      { tab: 'intro', title: __('Write the intro'), desc: __('Set the hook — what topic, how many questions, time limit.') },
      { tab: 'form', title: __('Capture leads (optional)'), desc: __("Gate the result, or skip — many trivia quizzes don't need a lead form.") },
      { tab: 'design', title: __('Style it'), desc: __('Pick a template and colors that match the topic.') },
      { tab: 'integrations', title: __('Send results to your CRM'), desc: __('Score breakdown delivered per submission.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('Timer is especially useful here — adds urgency.') },
    ],
    tips: [
      __("Don't set point values higher than 1 unless some questions are weighted heavier."),
      __("Score bands shouldn't overlap — the first matching band wins."),
      __('Add a Per-question timer (Pro) for "rapid fire" feel.'),
    ],
    free: {
      desc: {
        settings: __('Answer review and other per-quiz behavior controls live here.'),
      },
      tips: [
        __("Don't set point values higher than 1 unless some questions are weighted heavier."),
        __("Score bands shouldn't overlap — the first matching band wins."),
      ],
    },
  },
  survey: {
    eyebrow: __('Survey'),
    shortName: __('Survey'),
    title: __('Collect responses, show one tailored result'),
    lede: __('No scoring — every visitor reaches the same result screen. Use this for feedback, NPS-lite, or a simple lead magnet.'),
    steps: [
      { tab: 'questions', title: __('Add your questions'), desc: __('Mix single-choice, multi-choice, and (with Pro) text/slider/rating.'), requires: () => store.questions.length >= 1 },
      { tab: 'results', title: __('Add the result screen'), desc: __('One screen with thank-you copy and any next-step CTA.'), requires: () => store.results.length >= 1 },
      { tab: 'logic', pro: true, title: __('Add branching (optional)'), desc: __('Skip irrelevant questions based on earlier answers.') },
      { tab: 'intro', title: __('Write the intro'), desc: __("Be upfront about what you'll do with the data.") },
      { tab: 'form', title: __('Capture leads (optional)'), desc: __('Email gate or post-quiz — your call.') },
      { tab: 'design', title: __('Style it'), desc: __('Match your brand.') },
      { tab: 'integrations', title: __('Send responses to your CRM'), desc: __('Each submission posted to webhook / Mailchimp / etc.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('GDPR consent text lives here.') },
    ],
    tips: [
      __('Surveys with more than 12 questions see big drop-off rates. Keep it tight.'),
      __("Use Required toggles sparingly — let visitors skip what doesn't apply to them."),
    ],
    free: {
      desc: {
        questions: __('Mix single-choice, multi-choice, and true/false questions.'),
        integrations: __('Each submission is posted to your webhook.'),
      },
    },
  },
  poll: {
    eyebrow: __('Poll'),
    shortName: __('Poll'),
    title: __('One question, live tally'),
    lede: __('A single multiple-choice question. After submission, every visitor sees the running result.'),
    steps: [
      { tab: 'questions', title: __('Write your one question'), desc: __('Add 2–6 answer options. Polls work best with short, punchy choices.'), requires: () => store.questions.length >= 1 },
      { tab: 'results', title: __('Customize the tally screen'), desc: __('Optional — defaults work fine.') },
      { tab: 'intro', title: __('Add a hook (optional)'), desc: __('Polls usually skip the intro and jump straight to the question.') },
      { tab: 'form', title: __('Capture leads (optional)'), desc: __("Most polls don't — but you can.") },
      { tab: 'design', title: __('Style it'), desc: __('Pick a compact template — polls embed well in sidebars.') },
      { tab: 'integrations', title: __('Send votes to your CRM'), desc: __('Each vote becomes a row.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('Review the quiz settings before you publish.') },
    ],
    tips: [
      __("Polls don't use Logic — there's only one question, nowhere to branch."),
      __('Embed in a blog post via the [quizably_quiz id="..."] shortcode.'),
    ],
    free: {
      tips: [
        __('Embed in a blog post via the [quizably_quiz id="..."] shortcode.'),
      ],
    },
  },
  branching: {
    eyebrow: __('Branching / lead qualifier'),
    shortName: __('Branching'),
    title: __('Different paths for different people'),
    lede: __('A decision tree. Build the question pool, then wire up the branches in the Logic tab — each answer can jump to a different question or end the quiz.'),
    steps: [
      { tab: 'questions', title: __('Add every possible question'), desc: __('Build the full pool first — branches reference these by id.'), requires: () => store.questions.length >= 2 },
      { tab: 'logic', pro: true, title: __('Wire up the branches'), desc: __('Drag connections in the Logic editor: each answer goes to a question or ends the quiz.') },
      { tab: 'results', title: __('Define endpoints'), desc: __('One result per "leaf" of the tree.') },
      { tab: 'intro', title: __('Write the intro'), desc: __("Branching quizzes are usually qualifiers — be specific about who they're for.") },
      { tab: 'form', title: __('Capture leads'), desc: __('Place the form at the start (gate) for high-intent qualifiers.') },
      { tab: 'design', title: __('Style it'), desc: __('Conversational template often pairs well with branching.') },
      { tab: 'integrations', title: __('Send leads with their path'), desc: __('Webhook payload includes the answer path so your CRM can route.') },
      { tab: 'settings', title: __('Final tweaks'), desc: __('Optional timer, randomize, etc.') },
    ],
    tips: [
      __('Always set a "default" branch on every question — otherwise visitors get stuck.'),
      __("Use the Logic tab's reachability warnings to find unreachable questions."),
      __('Keep the tree shallow — deep branches kill completion.'),
    ],
    free: {
      lede: __('Build the question pool, then define an endpoint for every outcome.'),
      desc: {
        questions: __('Build the full pool of questions your quiz will ask.'),
        design: __('Pick a template and colors that fit who the quiz is for.'),
        settings: __('Auto-advance, answer review, etc.'),
      },
      tips: [
        __('Keep it short — long quizzes lose people before the last question.'),
      ],
    },
  },
};

// Some copy points at Pro-tier features (the Logic tab, Pro question types,
// integrations, timers). It is shown as written while Pro is active or Pro
// promotion is on; otherwise each blueprint's `free` overrides apply and the
// Pro-only steps drop out, so the free builder never mentions or links to
// something it doesn't offer.
const blueprint = computed(() => {
  const base = BLUEPRINTS[quizType.value] ?? BLUEPRINTS.personality;
  if (proFeaturesVisible()) return base;
  const free = base.free ?? {};
  return {
    ...base,
    lede: free.lede ?? base.lede,
    steps: base.steps
      .filter((s) => !s.pro)
      .map((s) => (free.desc?.[s.tab] ? { ...s, desc: free.desc[s.tab] } : s)),
    tips: free.tips ?? base.tips,
  };
});

// translators: %s is the quiz type's short name, e.g. "Personality".
const tipsTitle = computed(() => sprintf(__('Tips for %s quizzes'), blueprint.value.shortName));

function stepIsDone(step) {
  return typeof step.requires === 'function' ? Boolean(step.requires()) : false;
}

function goTo(tabKey) {
  const quizId = Number(route.params?.id);
  if (Number.isFinite(quizId) && quizId > 0) {
    router.push(`/quiz/${quizId}/${tabKey}`);
  }
}
</script>

<style scoped>
.overview-tab {
  height: 100%;
  overflow-y: auto;
  background: var(--bg-canvas);
  padding: 32px 28px 64px;
}

.overview-tab__inner {
  max-width: 920px;
  margin: 0 auto;
}

.overview-tab__head {
  margin-bottom: 28px;
}

.overview-tab__eyebrow {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  color: var(--brand);
  text-transform: uppercase;
  font-weight: 600;
}

.overview-tab__title {
  margin: 4px 0 4px;
  font-family: var(--f-display, var(--f-sans));
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.overview-tab__lede {
  margin: 0;
  font-size: 13px;
  color: var(--ink-2);
  line-height: 1.5;
  max-width: 60ch;
}

.overview-tab__cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 14px;
  margin: 0 0 28px;
}

.overview-card {
  position: relative;
  display: flex;
  gap: 14px;
  padding: 16px 18px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
  transition: border-color 150ms, box-shadow 150ms;
}

.overview-card:hover {
  border-color: var(--brand);
  box-shadow: var(--shadow-sm);
}

.overview-card.is-done {
  background: color-mix(in srgb, var(--success) 6%, var(--bg-surface));
  border-color: color-mix(in srgb, var(--success) 30%, var(--border-1));
}

.overview-card__num {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-2);
}

.overview-card.is-done .overview-card__num {
  background: var(--success);
  border-color: var(--success);
  color: #fff;
}

.overview-card__body {
  flex: 1;
  min-width: 0;
}

.overview-card__title {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.overview-card__desc {
  margin: 0 0 10px;
  font-size: 12.5px;
  color: var(--ink-3);
  line-height: 1.5;
}

.overview-card__cta {
  background: transparent;
  border: 0;
  padding: 0;
  font: inherit;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--brand);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.overview-card__cta:hover {
  text-decoration: underline;
}

.overview-tab__tips {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 18px 22px;
}

.overview-tab__tips-title {
  margin: 0 0 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-1);
}

.overview-tab__tips ul {
  margin: 0;
  padding-block: 0 0;
  padding-inline: 18px 0;
  font-size: 13px;
  color: var(--ink-2);
  line-height: 1.65;
}
</style>
