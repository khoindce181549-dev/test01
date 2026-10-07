=== Quizably – Quiz Maker, Personality Quiz & Survey Builder ===
Contributors: advancewpbuilder
Tags: quiz, quiz maker, personality quiz, survey, lead generation
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Quiz maker for WordPress: build personality quizzes, trivia, surveys & polls that capture leads — drag-and-drop builder, 4 modern templates.

== Description ==

**Quizably is a WordPress quiz maker and quiz builder plugin** for anyone who wants to turn visitors into leads instead of just page views. Build a personality quiz, a trivia test, a survey, or a poll in minutes with a drag-and-drop builder, then embed it anywhere with a shortcode or Gutenberg block. Every quiz you publish gets its own modern, mobile-friendly design — no theme conflicts, no clunky default-WordPress look.

**Links:** [Plugin page](https://wpgrowkit.com/plugins/quizably/) · [Documentation](https://wpgrowkit.com/plugins/quizably/docs/) · [Live demo and templates](https://wpgrowkit.com/plugins/quizably/templates/)

If you've ever wanted a "what type of ___ are you?" quiz, a scored trivia challenge, or a short survey that captures an email address before showing the result, this plugin is built for exactly that.

= What people build with it =

* **Online stores** run a "find your perfect [product]" product recommendation quiz that routes each result to a matching product or collection page.
* **Coaches and creators** use a lead magnet quiz or self-assessment — a few fun questions, a personalized result, and an email captured along the way.
* **Bloggers and communities** run trivia and "which character are you" quizzes to boost time on page and shares.
* **Educators and trainers** use a scored quiz as an online test or knowledge check before or after a lesson.
* **Course and membership sites** use a short quiz to route new members toward the right starting content.

= Why site owners use a quiz to grow their list =

Quizzes convert better than static opt-in forms because people want to see their result. Quizably is built around that: ask a few questions, show a personalized result, and capture a name and email along the way — either before the quiz starts or right before the result appears, your choice.

= What you can build =

* **4 quiz types**: Personality / Result quizzes, Trivia / Score-based quizzes, Surveys, and Polls
* **4 modern templates**: Classic, Minimal, Full Screen (dark and immersive), and Conversational (chat-style)
* **6 question types**: Single choice, Multiple choice, True / False, Dropdown, Short text, and Rating (stars, numbers, or faces)

= Everything you'd expect from a quiz maker, done properly =

* **Drag-reorder question builder** with a live preview — see exactly what visitors will see as you build
* **Custom backgrounds** — a different image and overlay per question and per result
* **Randomize answers and question order** — shuffled per visitor, stable within their session
* **Per-question timer** — auto-advances when it runs out, useful for trivia
* **Dynamic results** based on answers — personality tallies for "which type are you" quizzes, score ranges for trivia
* **Lead capture form** — shown before or after the quiz, skippable or required, name + email fields
* **Double opt-in** — send a confirmation email and only count a lead once they click through
* **Leads dashboard** — search, filter, and export every captured lead as CSV
* **Webhook integration** — send each submission to Zapier, Make, or any URL the moment it happens
* **Analytics** — submission count, completion rate, average score, result distribution, per-question drop-off, and answer distribution, all exportable
* **Quiz export / import** — back up a quiz as JSON or move it to another site
* **Gutenberg block + shortcode** — `[quizably_quiz id="..."]` anywhere, or as a popup / slide-in with `[quizably_quiz_popup id="..."]` / `[quizably_quiz_slidein id="..."]`
* **Fast by default** — the Vue bundle only loads on pages that actually have a quiz on them

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/quizably`, or install it through the Plugins menu in your WordPress dashboard.
2. Activate the plugin through the 'Plugins' screen.
3. Go to **Quiz Builder** in the sidebar to create your first quiz.
4. Pick a quiz type and a template, add your questions, then publish and drop the shortcode or block on any page.

== Frequently Asked Questions ==

= Is Quizably free? =
Yes. Everything listed above — all 4 quiz types, all 4 templates, lead capture, double opt-in, webhooks, and analytics — is in the free plugin. There's no artificial cap on the number of quizzes, questions, or leads.

= Does this work with any WordPress theme? =
Yes. The quiz UI is scoped to its own CSS classes, so it won't inherit or fight with your theme's styles — what you design in the builder is what visitors see, regardless of theme.

= Can I add a quiz with the block editor, or do I need a shortcode? =
Both work. Use the Quizably block in the Gutenberg editor, or paste `[quizably_quiz id="..."]` into any page, post, or widget area that supports shortcodes.

= Can I show the quiz as a popup instead of embedding it in the page? =
Yes — `[quizably_quiz_popup id="..."]` opens it in a modal, and `[quizably_quiz_slidein id="..."]` slides it in from the edge of the screen.

= Where do captured leads go? =
Into the Leads dashboard inside the plugin, where you can search, filter, and export them as CSV. If you use email marketing software, the webhook integration can also forward every submission to Zapier, Make, or any URL of your choice in real time.

= Where can I find documentation and a live demo? =
Full documentation is at [wpgrowkit.com/plugins/quizably/docs](https://wpgrowkit.com/plugins/quizably/docs/). You can try every template in the [live demo](https://wpgrowkit.com/plugins/quizably/templates/), and the [plugin page](https://wpgrowkit.com/plugins/quizably/) has the overview.

= Can I translate the plugin? =
Yes — the whole interface, including the quiz builder and the quizzes your visitors see, is translatable, and right-to-left languages are supported. A `.pot` file ships in `/languages/`, ready for Loco Translate, Poedit, or WordPress.org's own translation platform.

= How are visitor emails and IP addresses handled? =
Only users with the right capability can view captured leads. IP addresses are hashed with SHA-256 before they're stored — Quizably never logs a raw IP address.

= Can I use it as a product finder for my store? =
Yes — a personality quiz is a natural product-recommendation tool. Map each result to a specific product, collection, or category page using the result's button link, so "Your result: The Minimalist" ends with a click straight through to the matching products.

= Can I run a scored quiz for training or a course? =
Yes — set the quiz type to Trivia / Score-based, assign points per answer, and set score ranges on each result ("0-5: Review the basics", "6-10: You're ready for the next module"). Works well before or after a lesson.

= Does it connect to my email marketing tool? =
Yes, through the webhook integration — every submission can be sent to Zapier or Make the moment it happens, and from there to Mailchimp, ActiveCampaign, a CRM, or virtually any tool that accepts a webhook.

= Will it slow down my site? =
No. The quiz scripts and styles only load on pages that actually contain a quiz — every other page on your site stays exactly as fast as it was. Webhooks are sent in the background, so submitting a quiz doesn't wait on Zapier, Make, or any other service.

= Is the plugin's source code included? =
Yes. Every file the plugin runs is shipped inside the plugin itself, fully readable — nothing is hidden behind a build step you don't have access to.

== Screenshots ==

1. A published quiz on the Classic template, ready for a visitor to start.
2. The Classic template mid-quiz — clean cards, a progress bar, and answer options a visitor can move through in seconds.
3. The Minimal template — the same quiz, typography-led with no card chrome, for a more editorial feel.
4. The Conversational template — chat-style bubbles for a quiz that feels like a message thread.
5. The Full Screen template's result screen — dark, immersive, and built around a custom result image.
6. The quiz builder's Questions tab: drag-reorder questions, edit answers inline, and preview exactly what visitors will see.
7. The Results tab: write each personality result, add an image, and map which answers lead there.
8. The Form tab: turn on lead capture, choose Name/Email fields, and decide whether it appears before the quiz or right before the result.
9. The Quizzes dashboard — every quiz at a glance, with submissions, leads, and completion rate tracked automatically.

== Changelog ==

= 1.2.3 =
* Accessibility: small secondary text in quizzes (intro labels like Questions and Takes, hints and placeholders) is darker, so it meets the WCAG AA contrast minimum on light and dark backgrounds.

= 1.2.2 =
* Fix: quizzes could not be started on sites running MySQL 8 (and in WordPress Playground). A database index name that MySQL 8 reserves stopped the submissions table from being created. Updating creates the missing table automatically on the next admin page load; existing submissions are kept.
* Fix: quiz intro, result, thank-you and resume titles are now H2 headings instead of H1, so a page with a quiz keeps a single H1 for its own title. Nothing changes visually.

= 1.2.1 =
* New: a small, dismissible review request on the Quizably dashboard, shown only after the plugin has been useful (a lead captured, 10 completed submissions, or a published quiz after two weeks) and never in the first 3 days. "Maybe later" hides it for 14 days; "Leave a review" and "Don't ask again" hide it for good.

= 1.2.0 =
* New: quiz resume - visitors can pick up where they left off after a reload (stored in their browser for 7 days; names, emails and free-text answers are never stored).
* New: popup and slide-in embeds can open on a delay, after scrolling, or on exit intent (`trigger`, `delay`, `scroll`, `once` attributes). Keyboard accessible.
* New: the block's Auto-start option now works.
* New: the leads CSV export includes quiz, result, score, opt-in status, UTM fields, custom fields, and every question's answer.
* New: the whole interface is translatable, and the admin and quizzes support right-to-left languages.
* Improved: webhooks are sent in the background so quiz submissions are faster. Sites with DISABLE_WP_CRON need a real cron for webhook delivery.
* Improved: new quizzes use your Defaults and Branding settings. Removed settings that had no effect.

= 1.1.0 =
* Changed: deleting the plugin no longer removes your quizzes, leads, or settings. To erase everything on deletion, add `define( 'QUIZABLY_REMOVE_ALL_DATA', true );` to wp-config.php first.

= 1.0.1 =
* Fixed: deleting the plugin from wp-admin no longer ends in a critical error.
* Fixed: popup and slide-in shortcodes now render the quiz (numeric ID or UUID).
* Fixed: double opt-in now works end to end - signed confirmation link, confirmation page, and the lead is held until confirmed.
* Fixed: polls show live results with your own vote marked.
* Fixed: surveys and quizzes with no result end on the thank-you screen instead of returning to the intro.
* Fixed: webhook payloads include an event field and the lead; the default webhook URL is saved, validated and used.
* Fixed: duplicating or importing a quiz keeps result settings, and duplicated questions keep their answers.
* Fixed: lead counts in analytics now match the Leads list.
* Security: confirmation tokens can no longer be forged before a signing secret exists.

= 1.0.0 =
* Initial release. Personality, trivia, survey, and poll quizzes.
* 4 templates: Classic, Minimal, Full Screen, and Conversational.
* 6 question types: single choice, multiple choice, true/false, dropdown, short text, and rating.
* Custom per-question and per-result backgrounds, answer and question randomization, and a per-question timer.
* Lead capture forms (shown before or after the quiz) with a rich-text HTML + plain-text submission email.
* Leads dashboard with search, filters, and CSV export.
* Webhook integration for sending every submission to Zapier, Make, or any URL.
* Analytics: site overview and per-quiz metrics (submissions, completion rate, average score, result distribution).
* Gutenberg block and `[quizably_quiz]` shortcode.
* Full Vue 3 admin SPA across Overview, Questions, Results, Intro, Form, Integrations, Settings, and Summary tabs.

== Upgrade Notice ==

= 1.2.3 =
Improves the contrast of small secondary text in quizzes. No changes to your quizzes or settings.

= 1.2.2 =
Fixes quizzes failing to start on MySQL 8 hosts. Recommended for all sites.

= 1.2.1 =
Adds an optional, dismissible review request to the Quizably dashboard. No changes to your quizzes or settings.

= 1.2.0 =
Adds quiz resume, popup/slide-in triggers, a fuller leads export, background webhooks, and translation and right-to-left support.

= 1.1.0 =
Deleting the plugin now keeps your quizzes, leads and settings. Fixes a critical error when deleting the plugin from wp-admin.

= 1.0.1 =
Bug-fix release: double opt-in, popup/slide-in shortcodes, poll results and webhook fixes. Recommended for all users.

= 1.0.0 =
First stable release.
