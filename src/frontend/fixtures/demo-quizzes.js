/**
 * Demo fixtures for the Phase 5 frontend.
 *
 * Shape matches what the Phase 6 REST endpoint will return from
 * GET /public/quiz/:uuid: a quiz record with nested questions (each with
 * nested answers) and a flat results list. Loaded via dynamic import so
 * they don't bloat the production bundle.
 */

export const coffeePersonality = {
  _demo: true,
  id: 1,
  uuid: 'demo-coffee',
  title: 'Which coffee are you?',
  type: 'personality',
  template: 'classic',
  settings: {
    intro: {
      description:
        'Answer three quick questions and discover your true coffee spirit.',
      cta_label: 'Find my brew',
    },
    optin: { placement: 'none' },
    question: { auto_advance: false },
    result: { review_answers: false },
  },
  design: { colors: {} },
  questions: [
    {
      id: 'q1',
      type: 'single',
      title: 'Pick a morning vibe.',
      required: true,
      answers: [
        { id: 'q1a1', label: 'Quiet sunrise, just me', personality_result_id: 'r-pourover' },
        { id: 'q1a2', label: 'Loud playlist, fast pace', personality_result_id: 'r-espresso' },
        { id: 'q1a3', label: 'Cozy blanket, second alarm', personality_result_id: 'r-latte' },
        { id: 'q1a4', label: 'Outside before sunlight', personality_result_id: 'r-cold' },
      ],
    },
    {
      id: 'q2',
      type: 'single',
      title: 'Choose a work setup.',
      required: true,
      answers: [
        { id: 'q2a1', label: 'A single task, fully focused', personality_result_id: 'r-pourover' },
        { id: 'q2a2', label: 'Six tabs and a deadline', personality_result_id: 'r-espresso' },
        { id: 'q2a3', label: 'Calls and collaboration', personality_result_id: 'r-latte' },
        { id: 'q2a4', label: 'Outside the office entirely', personality_result_id: 'r-cold' },
      ],
    },
    {
      id: 'q3',
      type: 'single',
      title: 'Your ideal weekend is...',
      required: true,
      answers: [
        { id: 'q3a1', label: 'A long book and silence', personality_result_id: 'r-pourover' },
        { id: 'q3a2', label: 'Three events in one day', personality_result_id: 'r-espresso' },
        { id: 'q3a3', label: 'Brunch with friends', personality_result_id: 'r-latte' },
        { id: 'q3a4', label: 'A hike or a swim', personality_result_id: 'r-cold' },
      ],
    },
  ],
  results: [
    {
      id: 'r-pourover',
      title: 'You are a Pour Over',
      description:
        '<p>Patient, intentional, and a little nerdy about details. You make time for the good stuff.</p>',
      image_url: '',
    },
    {
      id: 'r-espresso',
      title: 'You are an Espresso',
      description:
        '<p>Short, bold, relentless. You run on momentum and aren\'t afraid of a little bitterness.</p>',
      image_url: '',
    },
    {
      id: 'r-latte',
      title: 'You are a Latte',
      description:
        '<p>Warm, social, comforting. People feel better after five minutes with you.</p>',
      image_url: '',
    },
    {
      id: 'r-cold',
      title: 'You are a Cold Brew',
      description:
        '<p>Low-key, steady, and secretly very caffeinated. You play the long game.</p>',
      image_url: '',
    },
  ],
};

export const geographyTrivia = {
  _demo: true,
  id: 2,
  uuid: 'demo-geo',
  title: 'World geography speed run',
  type: 'trivia',
  template: 'classic',
  settings: {
    intro: {
      description: 'Three questions. One point each. No cheating.',
      cta_label: 'Start the round',
    },
    optin: { placement: 'none' },
    question: { auto_advance: false },
    result: { review_answers: true },
  },
  design: { colors: {} },
  questions: [
    {
      id: 'g1',
      type: 'single',
      title: 'Which country has the most natural lakes?',
      required: true,
      answers: [
        { id: 'g1a1', label: 'United States', is_correct: false, points: 1 },
        { id: 'g1a2', label: 'Canada', is_correct: true, points: 1 },
        { id: 'g1a3', label: 'Russia', is_correct: false, points: 1 },
        { id: 'g1a4', label: 'Finland', is_correct: false, points: 1 },
      ],
    },
    {
      id: 'g2',
      type: 'true_false',
      title: 'Mount Everest is the tallest mountain on Earth measured from base to summit.',
      required: true,
      answers: [
        { id: 'g2a1', label: 'True', is_correct: false, points: 1 },
        { id: 'g2a2', label: 'False', is_correct: true, points: 1 },
      ],
    },
    {
      id: 'g3',
      type: 'single',
      title: 'The Nile flows primarily in which direction?',
      required: true,
      answers: [
        { id: 'g3a1', label: 'South', is_correct: false, points: 1 },
        { id: 'g3a2', label: 'East', is_correct: false, points: 1 },
        { id: 'g3a3', label: 'North', is_correct: true, points: 1 },
        { id: 'g3a4', label: 'West', is_correct: false, points: 1 },
      ],
    },
  ],
  results: [
    {
      id: 'tr-low',
      title: 'Room to grow',
      description: '<p>A rough round. Want another spin?</p>',
      score_min: 0,
      score_max: 1,
      image_url: '',
    },
    {
      id: 'tr-mid',
      title: 'Solid traveler',
      description: '<p>Not bad — you picked up a couple of tough ones.</p>',
      score_min: 2,
      score_max: 2,
      image_url: '',
    },
    {
      id: 'tr-top',
      title: 'Atlas-level',
      description: '<p>Clean sweep. When is your next flight?</p>',
      score_min: 3,
      score_max: 3,
      image_url: '',
    },
  ],
};

export const simpleSurvey = {
  id: 3,
  uuid: 'demo-survey',
  title: 'Quick feedback',
  type: 'survey',
  template: 'minimal',
  settings: {
    intro: { description: 'Two short questions — takes under a minute.' },
    optin: { placement: 'none' },
    question: { auto_advance: false },
  },
  design: { colors: {} },
  questions: [
    {
      id: 's1',
      type: 'single',
      title: 'How did you hear about us?',
      required: true,
      answers: [
        { id: 's1a1', label: 'A friend' },
        { id: 's1a2', label: 'Social media' },
        { id: 's1a3', label: 'Search engine' },
        { id: 's1a4', label: 'Somewhere else' },
      ],
    },
    {
      id: 's2',
      type: 'multi',
      title: 'Which features interest you most? (choose any)',
      required: false,
      answers: [
        { id: 's2a1', label: 'Personality quizzes' },
        { id: 's2a2', label: 'Trivia' },
        { id: 's2a3', label: 'Surveys' },
        { id: 's2a4', label: 'Lead capture' },
      ],
    },
  ],
  results: [
    {
      id: 'sv-done',
      title: 'Thanks for the feedback!',
      description: '<p>Your responses have been recorded.</p>',
      image_url: '',
    },
  ],
};
