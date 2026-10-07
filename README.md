# Quizably

Modern Vue-powered WordPress quiz plugin. Personality, trivia, survey and poll
quizzes; two frontend templates; webhook integration; forms; leads
dashboard with CSV export; analytics; Gutenberg block and shortcode.

## Requirements

- PHP >= 7.4
- WordPress >= 6.0
- Node.js >= 18 (for building assets)
- Composer (for PHP dev deps)

## Quick start for contributors

```bash
composer install
npm install
npm run build
```

Then activate the plugin from `wp-admin/plugins.php` and visit
**Quiz Builder** in the sidebar.

### Committing the build

`assets/dist/` (the Vite build output) is committed to this repo, not
gitignored. This plugin is installed on shared hosting via a plain zip
upload or WordPress.org's "Install Now" — there's no build step the end
user can run, so whatever they get has to already contain a working build.
That means:

- If your change touches `src/admin/`, `src/frontend/`, `src/block-editor/`
  or `src/shared/`, run `npm run build` and **commit the resulting
  `assets/dist/` changes in the same commit** as the source change.
- CI enforces this (`.github/workflows/test.yml` → "Assert committed
  assets/dist/ matches a fresh build of src/"): it rebuilds from your
  committed source and fails if the result differs from what you committed.
  If you only changed `src/php/`, `assets/dist/` won't change and there's
  nothing to re-commit.

## Scripts

- `npm run dev` — Vite dev server with HMR (standalone demo mount)
- `npm run build` — production build to `assets/dist/`
- `npm run lint` — ESLint over `src/**/*.{js,vue}`
- `npm test` — Vitest (JS unit + smoke)
- `npm run i18n:pot` — regenerate `languages/quizably.pot`
- `npm run release` — build, then package a customer-installable zip (see Releasing below)
- `composer test` — PHPUnit unit suite
- `composer test:integration` — PHPUnit integration suite (needs a WP test env)

## Releasing

Because `assets/dist/` is committed (see above), even a raw `git clone`,
GitHub "Download ZIP", or WordPress.org SVN export already contains a
working build — none of them need any setup step to run. `npm run release`
exists on top of that for a *clean* customer zip — one that also strips out
everything a customer doesn't need (`node_modules`, `tests`, `docs`, dev
configs, etc.) rather than shipping the whole repo:

```bash
npm run release
```

This runs `npm run build` and then `scripts/build-release.mjs`, which fails
loudly if the build didn't produce `assets/dist/admin.js`, and packages only
the runtime files (`quizably.php`, `uninstall.php`, `LICENSE`,
`readme.txt`, `languages/`, `assets/dist|icons|templates/`, `src/php/`) into
`build/quizably-{version}.zip`, laid out with `quizably/`
as the top-level folder so it installs correctly via
**Plugins → Add New → Upload Plugin**.

Pushing a `v*.*.*` tag runs this automatically in CI
(`.github/workflows/release.yml`) and attaches the zip to the GitHub Release,
so a tagged release never depends on someone remembering this step.

## End-to-End Tests

E2E tests run against your local WordPress install via Playwright (Chromium, headless).

```bash
npm run test:e2e         # headless
npm run test:e2e:headed  # see the browser
npm run test:e2e:ui      # Playwright UI mode
```

Set `QUIZABLY_E2E_BASE_URL`, `QUIZABLY_E2E_USER`, `QUIZABLY_E2E_PASS` env vars to override
defaults (default `http://localhost/wp/`, `admin/admin`).

Tests skip gracefully when WordPress is unreachable or when no published quiz
exists for the frontend test. Set `QUIZABLY_E2E_FRONTEND_URL` to a page that embeds
`[quizably_quiz id="N"]` to enable the frontend rendering check.

## Architecture at a glance

```
quizably.php      Bootstrap: defines constants, registers
                              activation hooks, registers a small built-in
                              PSR-4 autoloader (no Composer at runtime),
                              calls Plugin::boot() on `plugins_loaded`.

src/php/                      Namespaced PHP classes (PSR-4 Quizably\)
  Core/                       Plugin container, Activator, Deactivator
  Database/                   Installer (dbDelta), Repository/ (one per table),
                              Schema
  REST/                       Admin + public REST controllers
  Scoring/                    Per-type scorers (personality / trivia / survey)
  Integration/                Dispatcher, Webhook, Registry
  Shortcode/                  `[quizably_quiz]` renderer
  Block/                      Gutenberg server-render block
  Admin/                      Menu, Assets, app-root.php view
  Frontend/                   Frontend Assets enqueuer
  RateLimit/                  IP-hash token bucket

src/admin/                    Vue 3 admin SPA (Pinia + vue-router)
src/frontend/                 Vue 3 quiz renderer (multi-instance mount)
src/block-editor/             Gutenberg block editor script
src/shared/                   Shared fonts, CSS tokens, base.css

tests/unit/                   PHPUnit unit tests (no WP bootstrap)
tests/integration/            PHPUnit integration tests (needs WP test env)
tests/js/                     Vitest tests
```

See [`docs/architecture.md`](docs/architecture.md) for the quiz-taking flow,
data model, and extension points.

## Extension API (stubs for Pro addon authors)

The following PHP and JS hooks are stable and will remain backward-compatible
through 1.x:

### PHP

```php
// Register a new quiz type (Pro: weighted, branching, ...).
add_action('quizably_booted', function () {
    \Quizably\Scoring\Registry::register('weighted', new MyWeightedScorer());
});

// Register a new integration (Pro: Mailchimp, ConvertKit, ...).
add_action('quizably_booted', function () {
    \Quizably\Integration\Registry::register(new MailchimpIntegration());
});

// Observe submissions.
add_action('quizably_submission_completed', function ($submission_uuid, $quiz, $result, $score) {
    // ...
}, 10, 4);

// Observe lead captures.
add_action('quizably_lead_captured', function ($lead_id, $submission, $request) {
    // ...
}, 10, 3);
```

### JavaScript

```js
// Register a new template (Pro: Full-Screen, Split-Screen, ...).
window.Quizably.frontendHooks.registerTemplate('fullscreen', FullScreenTemplateComponent);

// Read the JS API version (pinned to the plugin version at build time).
console.log(window.Quizably.version); // "1.0.0"
```

## REST endpoints (summary)

All endpoints live under `/wp-json/quizably/v1/`.

| Route                                                    | Method     | Notes                                       |
| -------------------------------------------------------- | ---------- | ------------------------------------------- |
| `/quizzes`                                               | GET / POST | admin only (cap: `quizably_manage_quizzes`)      |
| `/quizzes/:id`                                           | GET / PATCH / DELETE |                                       |
| `/quizzes/:id/questions`                                 | GET / POST |                                             |
| `/questions/:id`                                         | GET / PATCH / DELETE |                                       |
| `/questions/:id/answers/reorder`                         | POST       |                                             |
| `/settings`                                              | GET / PATCH |                                            |
| `/analytics/overview`                                    | GET        | aggregate across all quizzes                |
| `/analytics/quiz/:id`                                    | GET        |                                             |
| `/leads`                                                 | GET        |                                             |
| `/leads/export`                                          | GET        | CSV                                         |
| `/public/quiz/:uuid`                                     | GET        | anonymous, rate-limited                     |
| `/public/submissions/start`                              | POST       | anonymous                                   |
| `/public/submissions/:uuid/answer`                       | POST       | anonymous                                   |
| `/public/submissions/:uuid/complete`                     | POST       | anonymous; runs server-side scorer          |
| `/public/submissions/:uuid/optin`                        | POST       | anonymous; creates Lead                     |

## i18n

Text domain: `quizably`. Regenerate the `.pot` with:

```bash
npm run i18n:pot
```

Translations live under `languages/` and load via `load_plugin_textdomain`
on the `init` hook.

## License

GPL-2.0-or-later.
