<?php
namespace Quizably\Admin;

use Quizably\REST\BaseController;

defined( 'ABSPATH' ) || exit;

/**
 * "Enjoying Quizably? Leave a review" prompt on the admin dashboard.
 *
 * Shown only after the plugin has done something useful for the site, never
 * on install day, and only to users who can manage quizzes. Every choice is
 * stored per user and honoured permanently (or for the snooze window), as
 * the WordPress.org guidelines for review requests require: the prompt is
 * dismissible, never blocks anything, offers nothing in return, and doesn't
 * ask for a particular star rating.
 *
 * Free feature: it exists to grow the WordPress.org listing, so it ships in
 * the free plugin and is not gated behind Pro.
 *
 * Decision record (2026-10-05):
 * - Triggers, in priority order: at least one lead captured, at least
 *   SUBMISSIONS_THRESHOLD completed submissions, or a published quiz on a
 *   site that has had the plugin for ACTIVE_DAYS. All three also require
 *   MIN_AGE_DAYS since install, so a site owner's own test runs on day one
 *   don't trigger it.
 * - "Maybe later" hides it for SNOOZE_DAYS; "Leave a review" and
 *   "Don't ask again" hide it for good.
 * - The install date is stored on activation. Sites that updated from a
 *   version without it get it set the first time an admin page loads, which
 *   starts the clock from then — erring on the side of asking later.
 */
final class ReviewPrompt
{
    public const META_KEY         = 'quizably_review_prompt';
    public const INSTALLED_OPTION = 'quizably_installed_at';
    public const REVIEW_URL       = 'https://wordpress.org/support/plugin/quizably/reviews/#new-post';

    public const MIN_AGE_DAYS          = 3;
    public const ACTIVE_DAYS           = 14;
    public const SNOOZE_DAYS           = 14;
    public const SUBMISSIONS_THRESHOLD = 10;

    public const TRIGGER_LEADS       = 'leads';
    public const TRIGGER_SUBMISSIONS = 'submissions';
    public const TRIGGER_ACTIVE      = 'active';

    public const ACTION_LATER    = 'later';
    public const ACTION_DISMISS  = 'dismiss';
    public const ACTION_REVIEWED = 'reviewed';
    public const ACTIONS         = [self::ACTION_LATER, self::ACTION_DISMISS, self::ACTION_REVIEWED];

    private const DAY = 86400;

    /** @var array<string,object> */
    private array $repos;

    /** @param array<string,object> $repos */
    public function __construct(array $repos)
    {
        $this->repos = $repos;
    }

    public function register(): void
    {
        add_action('admin_init', [self::class, 'ensure_installed_at']);
    }

    /** Record the install time once; never overwrites an existing value. */
    public static function ensure_installed_at(): void
    {
        if (false === get_option(self::INSTALLED_OPTION, false)) {
            add_option(self::INSTALLED_OPTION, time(), '', false);
        }
    }

    /**
     * Pure decision: which trigger (if any) applies.
     *
     * @param array{installed_at:int,leads:int,completed_submissions:int,published_quizzes:int} $stats
     * @param array{status?:string,until?:int}                                                     $user_state
     */
    public static function decide(array $stats, array $user_state, int $now): ?string
    {
        if (self::is_suppressed($user_state, $now)) {
            return null;
        }

        $installed_at = (int) $stats['installed_at'];
        if ($installed_at <= 0) {
            return null;
        }
        $age = $now - $installed_at;
        if ($age < self::MIN_AGE_DAYS * self::DAY) {
            return null;
        }

        if ((int) $stats['leads'] >= 1) {
            return self::TRIGGER_LEADS;
        }
        if ((int) $stats['completed_submissions'] >= self::SUBMISSIONS_THRESHOLD) {
            return self::TRIGGER_SUBMISSIONS;
        }
        if ((int) $stats['published_quizzes'] >= 1 && $age >= self::ACTIVE_DAYS * self::DAY) {
            return self::TRIGGER_ACTIVE;
        }
        return null;
    }

    /**
     * Whether the user's own choice hides the prompt right now.
     *
     * @param array{status?:string,until?:int} $user_state
     */
    public static function is_suppressed(array $user_state, int $now): bool
    {
        $status = $user_state['status'] ?? '';
        if (self::ACTION_DISMISS === $status || self::ACTION_REVIEWED === $status) {
            return true;
        }
        return self::ACTION_LATER === $status && $now < (int) ($user_state['until'] ?? 0);
    }

    /**
     * New per-user state after the user picks an action.
     *
     * @return array{status:string,until:int}
     */
    public static function next_state(string $action, int $now): array
    {
        return [
            'status' => $action,
            'until'  => self::ACTION_LATER === $action ? $now + self::SNOOZE_DAYS * self::DAY : 0,
        ];
    }

    /**
     * Data for the admin SPA, or null when the prompt shouldn't show.
     *
     * @return array{trigger:string,leads:int,submissions:int,reviewUrl:string}|null
     */
    public function for_user(int $user_id): ?array
    {
        if ($user_id <= 0 || ! user_can($user_id, BaseController::CAPABILITY)) {
            return null;
        }

        $user_state = $this->user_state($user_id);
        $now        = time();

        // Skip the count queries once the user has answered — the common
        // case after the prompt has been seen.
        if (self::is_suppressed($user_state, $now)) {
            return null;
        }

        $stats = [
            'installed_at'          => (int) get_option(self::INSTALLED_OPTION, 0),
            'leads'                 => $this->repos['leads']->count(),
            'completed_submissions' => $this->repos['submissions']->count('completed'),
            'published_quizzes'     => $this->repos['quizzes']->count(['status' => 'published']),
        ];

        $trigger = self::decide($stats, $user_state, $now);
        if (null === $trigger) {
            return null;
        }

        return [
            'trigger'     => $trigger,
            'leads'       => $stats['leads'],
            'submissions' => $stats['completed_submissions'],
            'reviewUrl'   => self::REVIEW_URL,
        ];
    }

    public function record(int $user_id, string $action): void
    {
        update_user_meta($user_id, self::META_KEY, self::next_state($action, time()));
    }

    /** @return array{status?:string,until?:int} */
    private function user_state(int $user_id): array
    {
        $state = get_user_meta($user_id, self::META_KEY, true);
        return is_array($state) ? $state : [];
    }
}
