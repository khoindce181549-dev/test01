/**
 * Predefined quiz preset catalog — display metadata only.
 *
 * This mirrors the server-side PresetLibrary.php but lives in the JS bundle
 * so the gallery renders instantly without a loading state.  The full
 * question/answer/result data lives server-side; import is done via
 * POST /quizably/v1/presets/{id}/import.
 *
 * TYPES:  personality | trivia | survey | poll | weighted | branching
 * TEMPLATES: classic | minimal | fullscreen | splitscreen | cardstack |
 *            conversational | gamified | magazine
 *
 * `pro: true` presets are Pro-tier. Use the `available*()` helpers below (not
 * the raw arrays) to render them: they hide every Pro-tier entry while the
 * free plugin isn't promoting Pro, and show the full catalog to Pro users and
 * to free users while promotion is on (import then answers 403 for those).
 */

import { proFeaturesVisible } from '@admin/api/pro.js';
import { __ } from '@shared/i18n';

// Thumbnails used to hotlink images.unsplash.com directly — flagged by
// WordPress.org's first-submission review (Guideline 6: don't call remote
// files that aren't providing an actual service). All photos are now bundled
// under assets/images/presets/ and served from the plugin's own URL, mirroring
// PresetLibrary.php's img() helper on the PHP side.
const PLUGIN_URL = window.QUIZABLY_ADMIN?.pluginUrl ?? '';
const img = (filename) => `${PLUGIN_URL}assets/images/presets/${filename}`;

export const PRESET_TYPES = [
  { id: 'personality', label: __('Personality'), pro: false },
  { id: 'trivia',      label: __('Trivia'),       pro: false },
  { id: 'survey',      label: __('Survey'),        pro: false },
  { id: 'poll',        label: __('Poll'),          pro: false },
  { id: 'weighted',    label: __('Weighted'),      pro: true  },
  { id: 'branching',   label: __('Branching'),     pro: true  },
];

export const PRESET_TEMPLATES = [
  { id: 'classic',        label: __('Classic'),        pro: false },
  { id: 'minimal',        label: __('Minimal'),         pro: false },
  // Free: Full Screen and Conversational. Keep in step with
  // TemplateController::list() in PHP (a test compares the two).
  { id: 'fullscreen',     label: __('Full Screen'),     pro: false },
  { id: 'splitscreen',    label: __('Split Screen'),    pro: true  },
  { id: 'cardstack',      label: __('Card Stack'),      pro: true  },
  { id: 'conversational', label: __('Conversational'),  pro: false },
  { id: 'gamified',       label: __('Gamified'),        pro: true  },
  { id: 'magazine',       label: __('Magazine'),        pro: true  },
];

/** @type {Array<{
 *   id: string,
 *   title: string,
 *   description: string,
 *   type: string,
 *   template: string,
 *   thumbnail: string,
 *   questionCount: number,
 *   resultCount: number,
 *   tags: string[],
 *   pro: boolean,
 *   gradient: [string, string],
 * }>} */
export const PRESETS = [
  // ── FREE ─── personality / classic ──────────────────────────────────────
  {
    id: 'coffee-personality',
    title: __('Which Coffee Are You?'),
    description: __('A fun personality quiz revealing your coffee spirit — pour over, espresso, latte, or cold brew.'),
    type: 'personality', template: 'classic',
    thumbnail: img('photo-1495474472287-4d71bcdd2085.jpg'),
    questionCount: 5, resultCount: 4,
    tags: ['lifestyle', 'food'], pro: false,
    gradient: ['#92400e', '#b45309'],
  },
  {
    id: 'travel-style',
    title: __("What's Your Travel Style?"),
    description: __('Discover your travel personality — adventure seeker, culture lover, beach bum, or city explorer.'),
    type: 'personality', template: 'classic',
    thumbnail: img('photo-1488646953014-85cb44e25828.jpg'),
    questionCount: 4, resultCount: 4,
    tags: ['travel', 'lifestyle'], pro: false,
    gradient: ['#0369a1', '#0891b2'],
  },
  {
    id: 'season-personality',
    title: __('What Season Matches Your Personality?'),
    description: __('Which season matches your energy — vibrant spring, bold summer, cozy autumn, or quiet winter?'),
    type: 'personality', template: 'classic',
    thumbnail: img('photo-1507003211169-0a1dd7228f2d.jpg'),
    questionCount: 4, resultCount: 4,
    tags: ['lifestyle', 'personality'], pro: false,
    gradient: ['#15803d', '#65a30d'],
  },
  {
    id: 'productivity-style',
    title: __("What's Your Productivity Style?"),
    description: __('Find out how you get things done — deep worker, sprint planner, collaborator, or creative chaos.'),
    type: 'personality', template: 'classic',
    thumbnail: img('photo-1483058712412-4245e9b90334.jpg'),
    questionCount: 5, resultCount: 4,
    tags: ['productivity', 'business'], pro: false,
    gradient: ['#4338ca', '#6366f1'],
  },
  // ── FREE ─── personality / minimal ──────────────────────────────────────
  {
    id: 'superhero-archetype',
    title: __('Which Superhero Archetype Are You?'),
    description: __('Are you the guardian, the avenger, the mastermind, or the wild card? Find your archetype.'),
    type: 'personality', template: 'minimal',
    thumbnail: img('photo-1531259683007-016a7b628fc3.jpg'),
    questionCount: 4, resultCount: 4,
    tags: ['fun', 'pop-culture'], pro: false,
    gradient: ['#7c3aed', '#db2777'],
  },
  {
    id: 'learning-style',
    title: __("What's Your Learning Style?"),
    description: __('Discover how you absorb information best — visual, auditory, reading/writing, or kinesthetic.'),
    type: 'personality', template: 'minimal',
    thumbnail: img('photo-1434030216411-0b793f4b4173.jpg'),
    questionCount: 4, resultCount: 4,
    tags: ['education', 'personal-growth'], pro: false,
    gradient: ['#0f766e', '#0369a1'],
  },
  // ── FREE ─── trivia / classic ────────────────────────────────────────────
  {
    id: 'world-geography',
    title: __('World Geography Speed Run'),
    description: __('Test your world geography knowledge — capitals, rivers, countries, and fascinating facts.'),
    type: 'trivia', template: 'classic',
    thumbnail: img('photo-1524661135-423995f22d0b.jpg'),
    questionCount: 6, resultCount: 3,
    tags: ['geography', 'education'], pro: false,
    gradient: ['#1d4ed8', '#0ea5e9'],
  },
  {
    id: 'science-myths',
    title: __('Science Myths vs Facts'),
    description: __('Separate fact from fiction — can you bust these popular science myths?'),
    type: 'trivia', template: 'classic',
    thumbnail: img('photo-1532187863486-abf9dbad1b69.jpg'),
    questionCount: 6, resultCount: 3,
    tags: ['science', 'education'], pro: false,
    gradient: ['#059669', '#10b981'],
  },
  {
    id: 'pop-culture-2024',
    title: __('Pop Culture Trivia 2024'),
    description: __('How well do you know the biggest moments in movies, music, gaming, and streaming?'),
    type: 'trivia', template: 'classic',
    thumbnail: img('photo-1489599849927-2ee91cede3ba.jpg'),
    questionCount: 6, resultCount: 3,
    tags: ['entertainment', 'pop-culture'], pro: false,
    gradient: ['#9333ea', '#ec4899'],
  },
  // ── FREE ─── trivia / minimal ────────────────────────────────────────────
  {
    id: 'movie-night',
    title: __('Movie Night Quiz'),
    description: __('Lights, camera, action! Test your movie knowledge across genres and decades.'),
    type: 'trivia', template: 'minimal',
    thumbnail: img('photo-1512070679279-8988d32161be.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['movies', 'entertainment'], pro: false,
    gradient: ['#1e1b4b', '#4338ca'],
  },
  {
    id: 'football-legends',
    title: __('Football Legends Challenge'),
    description: __('How much do you know about the beautiful game and its greatest players and moments?'),
    type: 'trivia', template: 'minimal',
    thumbnail: img('photo-1629217855633-79a6925d6c47.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['sports', 'football'], pro: false,
    gradient: ['#166534', '#15803d'],
  },
  // ── FREE ─── survey / classic ────────────────────────────────────────────
  {
    id: 'product-feedback',
    title: __('Product Feedback Survey'),
    description: __('Collect structured customer feedback to improve your product or service.'),
    type: 'survey', template: 'classic',
    thumbnail: img('photo-1556761175-4b46a572b786.jpg'),
    questionCount: 5, resultCount: 1,
    tags: ['business', 'feedback'], pro: false,
    gradient: ['#0284c7', '#0ea5e9'],
  },
  {
    id: 'team-culture',
    title: __('Team Culture Check'),
    description: __('Measure team health with questions on collaboration, communication, and morale.'),
    type: 'survey', template: 'classic',
    thumbnail: img('photo-1522071820081-009f0129c71c.jpg'),
    questionCount: 5, resultCount: 1,
    tags: ['business', 'hr'], pro: false,
    gradient: ['#7c3aed', '#4f46e5'],
  },
  {
    id: 'event-satisfaction',
    title: __('Event Satisfaction Survey'),
    description: __('Gather post-event feedback to improve your future events and experiences.'),
    type: 'survey', template: 'classic',
    thumbnail: img('photo-1540575467063-178a50c2df87.jpg'),
    questionCount: 4, resultCount: 1,
    tags: ['events', 'business'], pro: false,
    gradient: ['#d97706', '#f59e0b'],
  },
  // ── FREE ─── survey / minimal ────────────────────────────────────────────
  {
    id: 'customer-pulse',
    title: __('Quick Customer Pulse'),
    description: __('A quick 3-question check-in on customer satisfaction and top priorities.'),
    type: 'survey', template: 'minimal',
    thumbnail: img('photo-1553729459-efe14ef6055d.jpg'),
    questionCount: 3, resultCount: 1,
    tags: ['business', 'feedback'], pro: false,
    gradient: ['#0891b2', '#06b6d4'],
  },
  // ── FREE ─── poll / classic ──────────────────────────────────────────────
  {
    id: 'js-framework-poll',
    title: __('Best JavaScript Framework Poll'),
    description: __('Which JavaScript framework do you prefer? Cast your vote and see live results.'),
    type: 'poll', template: 'classic',
    thumbnail: img('photo-1627398242454-45a1465c2479.jpg'),
    questionCount: 1, resultCount: 1,
    tags: ['tech', 'dev'], pro: false,
    gradient: ['#ca8a04', '#f59e0b'],
  },
  {
    id: 'remote-office-poll',
    title: __('Remote vs Office Poll'),
    description: __('Remote, hybrid, or office? Share your preferred way of working.'),
    type: 'poll', template: 'classic',
    thumbnail: img('photo-1497366216548-37526070297c.jpg'),
    questionCount: 1, resultCount: 1,
    tags: ['work', 'business'], pro: false,
    gradient: ['#334155', '#475569'],
  },
  // ── FREE ─── poll / minimal ──────────────────────────────────────────────
  {
    id: 'social-platform-poll',
    title: __('Favorite Social Platform Poll'),
    description: __('Which social media platform do you spend the most time on? Vote now!'),
    type: 'poll', template: 'minimal',
    thumbnail: img('photo-1611162617474-5b21e879e113.jpg'),
    questionCount: 1, resultCount: 1,
    tags: ['social', 'lifestyle'], pro: false,
    gradient: ['#db2777', '#f43f5e'],
  },

  // ── PRO ─── personality ──────────────────────────────────────────────────
  {
    id: 'money-personality',
    title: __("What's Your Money Personality?"),
    description: __('Uncover your money personality — are you a saver, investor, spender, or avoider?'),
    type: 'personality', template: 'fullscreen',
    thumbnail: img('photo-1579621970563-ebec7560ff3e.jpg'),
    questionCount: 5, resultCount: 4,
    tags: ['finance', 'lifestyle'], pro: false, // personality on Full Screen: both free
    gradient: ['#15803d', '#65a30d'],
  },
  {
    id: 'career-path',
    title: __('What Career Path Suits You?'),
    description: __('Find the career path that fits your strengths — creator, analyst, leader, or builder.'),
    type: 'personality', template: 'cardstack',
    thumbnail: img('photo-1454165804606-c3d57bc86b40.jpg'),
    questionCount: 5, resultCount: 4,
    tags: ['career', 'personal-growth'], pro: true,
    gradient: ['#4338ca', '#7c3aed'],
  },
  {
    id: 'writing-style',
    title: __('What Writing Style Are You?'),
    description: __('Discover your writing voice — storyteller, analyst, poet, or journalist.'),
    type: 'personality', template: 'magazine',
    thumbnail: img('photo-1455390582262-044cdead277a.jpg'),
    questionCount: 4, resultCount: 4,
    tags: ['writing', 'creative'], pro: true,
    gradient: ['#292524', '#57534e'],
  },
  // ── PRO ─── trivia ───────────────────────────────────────────────────────
  {
    id: 'space-trivia',
    title: __('Space Exploration Trivia'),
    description: __('Blast off with questions about space exploration, planets, and the cosmos.'),
    type: 'trivia', template: 'splitscreen',
    thumbnail: img('photo-1462331940025-496dfbfc7564.jpg'),
    questionCount: 6, resultCount: 3,
    tags: ['science', 'space'], pro: true,
    gradient: ['#1e1b4b', '#312e81'],
  },
  {
    id: 'sports-championship',
    title: __('Sports Championship Challenge'),
    description: __('How much do you know about world sports championships and legendary moments?'),
    type: 'trivia', template: 'gamified',
    thumbnail: img('photo-1461896836934-ffe607ba8211.jpg'),
    questionCount: 6, resultCount: 3,
    tags: ['sports', 'trivia'], pro: true,
    gradient: ['#b45309', '#d97706'],
  },
  // ── PRO ─── weighted ─────────────────────────────────────────────────────
  {
    id: 'marketing-maturity',
    title: __('Marketing Maturity Assessment'),
    description: __('Assess your marketing maturity — strategy, content, data, channels, and automation.'),
    type: 'weighted', template: 'classic',
    thumbnail: img('photo-1460925895917-afdab827c52f.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['business', 'marketing'], pro: true,
    gradient: ['#0369a1', '#0284c7'],
  },
  {
    id: 'leadership-style',
    title: __('Leadership Style Inventory'),
    description: __('Identify your leadership style — visionary, servant leader, or democratic.'),
    type: 'weighted', template: 'splitscreen',
    thumbnail: img('photo-1519389950473-47ba0277781c.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['business', 'leadership'], pro: true,
    gradient: ['#7c3aed', '#4338ca'],
  },
  // ── PRO ─── branching ────────────────────────────────────────────────────
  {
    id: 'product-finder',
    title: __('Product Recommendation Finder'),
    description: __('Guide customers to the right product tier through smart qualifying questions.'),
    type: 'branching', template: 'classic',
    thumbnail: img('photo-1557804506-669a67965ba0.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['business', 'sales'], pro: true,
    gradient: ['#0f766e', '#0369a1'],
  },
  {
    id: 'it-support-triage',
    title: __('IT Support Triage'),
    description: __('Route IT support tickets intelligently based on device, issue type, and urgency.'),
    type: 'branching', template: 'conversational',
    thumbnail: img('photo-1518770660439-4636190af475.jpg'),
    questionCount: 5, resultCount: 3,
    tags: ['tech', 'business'], pro: true,
    gradient: ['#334155', '#1e293b'],
  },
  // ── PRO ─── survey ───────────────────────────────────────────────────────
  {
    id: 'employee-wellbeing',
    title: __('Employee Wellbeing Check'),
    description: __('A confidential wellbeing check covering workload, support, and engagement.'),
    type: 'survey', template: 'conversational',
    thumbnail: img('photo-1552581234-26160f608093.jpg'),
    questionCount: 5, resultCount: 1,
    tags: ['business', 'hr'], pro: false, // survey (rating questions) on Conversational: all free
    gradient: ['#0d9488', '#0891b2'],
  },
  {
    id: 'content-creator-intake',
    title: __('Content Creator Intake'),
    description: __('Qualify new content creator partnerships with this structured intake form.'),
    type: 'survey', template: 'magazine',
    thumbnail: img('photo-1542744173-8e7e53415bb0.jpg'),
    questionCount: 5, resultCount: 1,
    tags: ['marketing', 'creative'], pro: true,
    gradient: ['#9333ea', '#c026d3'],
  },
  // ── PRO ─── poll ─────────────────────────────────────────────────────────
  {
    id: 'dev-tools-poll',
    title: __('Best Dev Tool Showdown'),
    description: __('Which development tool or IDE is your daily driver? Vote and see the results!'),
    type: 'poll', template: 'gamified',
    thumbnail: img('photo-1461749280684-dccba630e2f6.jpg'),
    questionCount: 1, resultCount: 1,
    tags: ['tech', 'dev'], pro: true,
    gradient: ['#1e293b', '#334155'],
  },
];

// ── Pro switch ───────────────────────────────────────────────────────────────
// Read live (not at import time) so a page/test that flips window.QUIZABLY_ADMIN
// between mounts sees the right catalog.

/** Type chips a user may see: Pro-tier types only when Pro features are visible. */
export function availablePresetTypes() {
  return proFeaturesVisible() ? PRESET_TYPES : PRESET_TYPES.filter((t) => !t.pro);
}

/** Template chips a user may see: Pro-tier templates only when Pro features are visible. */
export function availablePresetTemplates() {
  return proFeaturesVisible() ? PRESET_TEMPLATES : PRESET_TEMPLATES.filter((t) => !t.pro);
}

/**
 * Presets a user may see. When Pro features are hidden this drops every
 * `pro: true` preset and, defensively, any preset built on a Pro-tier type or
 * template — so a free-flagged preset can never leave the gallery showing a
 * Pro chip's worth of content that has no chip.
 */
export function availablePresets() {
  if (proFeaturesVisible()) return PRESETS;
  const proTypes = new Set(PRESET_TYPES.filter((t) => t.pro).map((t) => t.id));
  const proTemplates = new Set(PRESET_TEMPLATES.filter((t) => t.pro).map((t) => t.id));
  return PRESETS.filter(
    (p) => !p.pro && !proTypes.has(p.type) && !proTemplates.has(p.template)
  );
}
