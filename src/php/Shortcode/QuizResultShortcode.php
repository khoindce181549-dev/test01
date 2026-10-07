<?php
namespace Quizably\Shortcode;

use Quizably\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class QuizResultShortcode
{
    public const TAG = 'quizably_quiz_result';

    /**
     * Handle for this shortcode's own small, static card CSS — deliberately
     * NOT Frontend\Assets::HANDLE. This shortcode renders a plain PHP/HTML
     * card with no Vue mount point, so it must not pull in the full
     * frontend.js bundle just to have somewhere to attach inline CSS (that
     * would undercut the "Vue bundle only loads on pages with a quiz" promise
     * in readme.txt for a page that has no interactive quiz at all).
     * Registered unconditionally on `wp_enqueue_scripts` — see the identical
     * comment on QuizPopupShortcode::HANDLE for why.
     */
    private const HANDLE = 'quizably-quiz-result';

    public function register(): void
    {
        add_shortcode(self::TAG, [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_inline_assets']);
    }

    public function register_inline_assets(): void
    {
        wp_register_style(self::HANDLE, false, [], QUIZABLY_VERSION);
        wp_enqueue_style(self::HANDLE);
        wp_add_inline_style(self::HANDLE, $this->inline_styles());
    }

    /** @param array<string,mixed>|string $atts */
    public function render($atts = []): string
    {
        // 1. Read ?quizably_result=<uuid> from the URL query string. No nonce: this is
        // a read-only lookup of an already-completed, already-public result meant
        // to be shared via plain link (e.g. on social media) — a nonce would be
        // tied to the visitor who generated it and would break that shareability,
        // and there is no state-changing action here for a nonce to guard.
        $raw_uuid = isset($_GET['quizably_result']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash((string) $_GET['quizably_result'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : '';

        if ('' === $raw_uuid) {
            return '';
        }

        // 2. Look up submission by UUID using the repository.
        $repos = Plugin::instance()->services()['repos'] ?? [];
        if (empty($repos)) {
            return $this->not_found_html();
        }

        /** @var \Quizably\Database\Repository\SubmissionRepository $submissions_repo */
        $submissions_repo = $repos['submissions'] ?? null;
        /** @var \Quizably\Database\Repository\ResultRepository $results_repo */
        $results_repo = $repos['results'] ?? null;
        /** @var \Quizably\Database\Repository\QuizRepository $quizzes_repo */
        $quizzes_repo = $repos['quizzes'] ?? null;

        if ( ! $submissions_repo || ! $results_repo || ! $quizzes_repo) {
            return $this->not_found_html();
        }

        $submission = $submissions_repo->find_by_uuid($raw_uuid);

        // 3. If submission not found or not completed: show "Result not found".
        if ( ! $submission || ($submission['status'] ?? '') !== 'completed') {
            return $this->not_found_html();
        }

        // 4. Look up the result record.
        $result = null;
        $result_id = isset($submission['result_id']) && $submission['result_id'] !== null
            ? (int) $submission['result_id']
            : 0;
        if ($result_id > 0) {
            $result = $results_repo->find($result_id);
        }

        // 5. Look up the quiz.
        $quiz = null;
        $quiz_id = isset($submission['quiz_id']) ? (int) $submission['quiz_id'] : 0;
        if ($quiz_id > 0) {
            $quiz = $quizzes_repo->find($quiz_id);
        }

        // 6. Render the result card.
        return $this->result_html($submission, $result, $quiz);
    }

    /**
     * Render a "result not found" message.
     */
    private function not_found_html(): string
    {
        return sprintf(
            '<div class="quizably-quiz-result quizably-quiz-result--not-found">%s</div>',
            esc_html__('Result not found.', 'quizably')
        );
    }

    /**
     * Render the result card HTML.
     *
     * @param array<string,mixed>      $submission
     * @param array<string,mixed>|null $result
     * @param array<string,mixed>|null $quiz
     */
    private function result_html(array $submission, ?array $result, ?array $quiz): string
    {
        $quiz_title    = $quiz  ? esc_html((string) $quiz['title'])   : '';
        $result_title  = $result ? esc_html((string) $result['title']) : esc_html__('Your Result', 'quizably');
        $result_content = $result && ! empty($result['content'])
            ? wp_kses_post((string) $result['content'])
            : '';

        $score     = isset($submission['score']) && $submission['score'] !== null
            ? (int) $submission['score']
            : null;

        // CTA button (optional).
        $cta = '';
        if ($result && ! empty($result['cta_label']) && ! empty($result['cta_url'])) {
            $cta = sprintf(
                '<p class="quizably-quiz-result__cta"><a href="%s" class="quizably-quiz-result__cta-btn">%s</a></p>',
                esc_url((string) $result['cta_url']),
                esc_html((string) $result['cta_label'])
            );
        }

        // Score line (only when score is not null).
        $score_html = '';
        if ($score !== null) {
            $score_html = sprintf(
                '<p class="quizably-quiz-result__score">%s <strong>%d</strong></p>',
                esc_html__('Score:', 'quizably'),
                $score
            );
        }

        return sprintf(
            '<div class="quizably-quiz-result"><div class="quizably-quiz-result__inner">%s<h2 class="quizably-quiz-result__title">%s</h2><div class="quizably-quiz-result__description">%s</div>%s%s</div></div>',
            $quiz_title !== ''
                ? sprintf('<p class="quizably-quiz-result__quiz-title">%s</p>', $quiz_title)
                : '',
            $result_title,
            $result_content,
            $score_html,
            $cta
        );
    }

    /** Raw CSS only — no wrapping <style> tag, this is handed to wp_add_inline_style(). */
    private function inline_styles(): string
    {
        return '
.quizably-quiz-result{box-sizing:border-box;width:100%;padding:2rem 1rem;display:flex;justify-content:center;font-family:inherit}
.quizably-quiz-result__inner{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:2.5rem 2rem;max-width:640px;width:100%;text-align:center;box-shadow:0 2px 16px rgba(0,0,0,.06)}
.quizably-quiz-result__quiz-title{margin:0 0 .5rem;font-size:.875rem;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
.quizably-quiz-result__title{margin:0 0 1rem;font-size:1.75rem;font-weight:700;color:#111827;line-height:1.25}
.quizably-quiz-result__description{margin:0 0 1.25rem;font-size:1rem;color:#374151;line-height:1.6}
.quizably-quiz-result__description:empty{margin-bottom:0}
.quizably-quiz-result__score{margin:.75rem 0 0;font-size:1rem;color:#374151}
.quizably-quiz-result__cta{margin:1.5rem 0 0}
.quizably-quiz-result__cta-btn{display:inline-block;padding:.625rem 1.5rem;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9375rem;transition:background .15s}
.quizably-quiz-result__cta-btn:hover{background:#1d4ed8;color:#fff}
.quizably-quiz-result--not-found{padding:2rem;text-align:center;color:#6b7280}
';
    }
}
