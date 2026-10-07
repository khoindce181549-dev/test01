import { createRouter, createWebHashHistory } from 'vue-router';
import { proFeaturesVisible } from './api/pro.js';

/**
 * Router factory. Exported as a function so main.js can mount a fresh
 * router per app and tests can spin up isolated instances.
 *
 * Hash history is the pragmatic choice inside wp-admin: WordPress owns
 * the outer URL (`/wp-admin/admin.php?page=quizably`), so we can't rely on
 * `pushState` without touching server rewrites.
 */
export function makeRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/', redirect: '/dashboard' },
      {
        path: '/dashboard',
        component: () => import('./views/DashboardView.vue'),
      },
      {
        path: '/quizzes',
        component: () => import('./views/QuizzesView.vue'),
      },
      {
        path: '/quiz/:id/:tab?',
        component: () => import('./views/QuizBuilderView.vue'),
        props: (route) => ({
          quizId: Number(route.params.id),
          tab: route.params.tab ?? 'overview',
        }),
      },
      {
        // Pro-only page (workflow editor). With no Pro add-on and Pro
        // promotion off there is nothing to show here — not even a pitch —
        // so a direct visit / old bookmark / `#/logic` link lands on the
        // dashboard instead. Read live so tests can flip the switch.
        path: '/logic',
        component: () => import('./views/LogicView.vue'),
        beforeEnter: () => (proFeaturesVisible() ? true : '/dashboard'),
      },
      {
        path: '/question-bank',
        component: () => import('./views/QuestionBankView.vue'),
      },
      {
        path: '/leads',
        component: () => import('./views/LeadsView.vue'),
      },
      // Redirect legacy /templates link → Settings → Defaults, where the
      // template picker now lives. Pickers also exist in New Quiz modal
      // step 2 and the Quiz Builder Design tab.
      { path: '/templates', redirect: '/settings#defaults' },
      {
        path: '/integrations',
        component: () => import('./views/IntegrationsView.vue'),
      },
      {
        path: '/settings',
        component: () => import('./views/SettingsView.vue'),
      },
    ],
  });
}
