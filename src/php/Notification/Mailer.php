<?php
namespace Quizably\Notification;

use Quizably\Database\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Sends admin notification emails on quiz completion and lead capture.
 *
 * Behaviour is controlled by settings stored in quizably_settings:
 *   notifications_email_on_submission  "1"|"0"
 *   notifications_email_on_lead        "1"|"0"
 *   notifications_recipient            email address (defaults to admin email)
 *   email_reply_to_lead                "1"|"0" - Reply-To is the lead's email, when there is one
 *   email_template_subject             plain-text subject template
 *   email_template_body                body template - HTML from the editor, or
 *                                      plain text saved before the editor existed
 *
 * Subject and body templates accept the tokens listed in EmailTemplate::TOKENS,
 * written {quiz_title} (the older {{quiz_title}} form still works):
 *   quiz_title, result_title, score, user_name, user_email,
 *   submitted_at, site_title, admin_url
 * plus quiz_type and submission_uuid.
 */
final class Mailer
{
    private SettingsRepository $settings;

    /** @var array<string,object> Repositories keyed by name (quizzes, leads, submissions). */
    private array $repos;

    /**
     * @param array<string,object> $repos
     */
    public function __construct(SettingsRepository $settings, array $repos = [])
    {
        $this->settings = $settings;
        $this->repos    = $repos;
    }

    public function register(): void
    {
        add_action('quizably_submission_completed', [$this, 'on_submission_completed'], 20, 4);
        add_action('quizably_lead_captured', [$this, 'on_lead_captured'], 20, 3);
    }

    /**
     * @param array<string,mixed>      $quiz
     * @param array<string,mixed>|null $result
     * @param array<string,mixed>      $score
     */
    public function on_submission_completed(string $uuid, array $quiz, $result, array $score): void
    {
        if (! $this->is_enabled('notifications_email_on_submission')) {
            return;
        }

        // The visitor may have filled in the form before or at the end of the
        // quiz (it is attached to the submission), or skipped it - in which
        // case there is simply no name or email to show.
        $lead = $this->lead_for_submission($uuid);

        $this->send($this->vars([
            'quiz_title'      => (string) ($quiz['title'] ?? ''),
            'quiz_type'       => (string) ($quiz['type'] ?? ''),
            'result_title'    => is_array($result) ? (string) ($result['title'] ?? '') : '',
            'score'           => isset($score['score']) && $score['score'] !== null ? (string) $score['score'] : 'N/A',
            'user_name'       => $lead['name'],
            'user_email'      => $lead['email'],
            'submission_uuid' => $uuid,
        ]));
    }

    /**
     * @param array<string,mixed> $sub
     * @param mixed               $req  WP_REST_Request - not used here
     */
    public function on_lead_captured(int $lead_id, array $sub, $req): void
    {
        if (! $this->is_enabled('notifications_email_on_lead')) {
            return;
        }

        $lead = $this->find_lead($lead_id);
        $quiz = $this->find('quizzes', (int) ($sub['quiz_id'] ?? 0));

        $this->send($this->vars([
            'quiz_title'      => (string) ($quiz['title'] ?? ''),
            'quiz_type'       => (string) ($quiz['type'] ?? ''),
            'user_name'       => $lead['name'],
            'user_email'      => $lead['email'],
            'submission_uuid' => (string) ($sub['uuid'] ?? ''),
        ]), 'lead');
    }

    /**
     * Every token the templates understand, with sensible blanks.
     *
     * @param array<string,string> $overrides
     * @return array<string,string>
     */
    private function vars(array $overrides): array
    {
        return array_merge([
            'quiz_title'      => '',
            'quiz_type'       => '',
            'result_title'    => '',
            'score'           => 'N/A',
            'user_name'       => '',
            'user_email'      => '',
            'submitted_at'    => wp_date(get_option('date_format', 'F j, Y') . ' ' . get_option('time_format', 'g:i a')),
            'site_title'      => wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
            'admin_url'       => admin_url('admin.php?page=quizably#/leads'),
            'submission_uuid' => '',
        ], $overrides);
    }

    /**
     * @param array<string,string> $vars
     */
    private function send(array $vars, string $context = 'submission'): void
    {
        $to = trim((string) $this->settings->get('notifications_recipient', ''));
        if ('' === $to) {
            $to = get_option('admin_email', '');
        }
        if (! is_email($to)) {
            return;
        }

        $default_subject = $context === 'lead'
            ? __('New quiz lead: {quiz_title} ({user_email})', 'quizably')
            : __('New quiz submission: {quiz_title}', 'quizably');

        $default_body = $context === 'lead'
            ? implode("\n", [
                __('A new lead has been captured.', 'quizably'),
                '',
                __('Email: {user_email}', 'quizably'),
                __('Name:  {user_name}',  'quizably'),
                __('Quiz:  {quiz_title}', 'quizably'),
                __('Submission: {submission_uuid}', 'quizably'),
            ])
            : implode("\n", [
                __('A quiz has been completed.', 'quizably'),
                '',
                __('Quiz:   {quiz_title}', 'quizably'),
                __('Type:   {quiz_type}',  'quizably'),
                __('Result: {result_title}', 'quizably'),
                __('Score:  {score}',       'quizably'),
                __('UUID:   {submission_uuid}', 'quizably'),
            ]);

        $stored_subject = (string) $this->settings->get('email_template_subject', '');
        $stored_body    = (string) $this->settings->get('email_template_body',    '');

        $subject = EmailTemplate::subject($stored_subject !== '' ? $stored_subject : $default_subject, $vars);

        $from_name    = (string) $this->settings->get('email_from_name',    get_option('blogname', 'WordPress'));
        $from_address = (string) $this->settings->get('email_from_address', get_option('admin_email', ''));
        if (! is_email($from_address)) {
            $from_address = get_option('admin_email', '');
        }

        // Re-cleaned here as well as on save: the value could have reached the
        // database some other way (import, direct edit), and this is the last
        // place before it goes into someone's inbox.
        $template = $stored_body !== '' ? EmailTemplate::sanitize_body($stored_body) : $default_body;
        $is_html  = $stored_body !== '' && EmailTemplate::is_html($template);

        $headers = [sprintf('From: %s <%s>', $from_name, $from_address)];

        // Settings -> Email -> "Reply-To matches lead email": replying to the notification goes
        // straight to the visitor. Skipped when there is no lead email (they skipped the form).
        $reply_to = trim((string) ($vars['user_email'] ?? ''));
        if ($this->is_enabled('email_reply_to_lead') && is_email($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        if (! $is_html) {
            // Plain text (the built-in default, or a template saved before the
            // editor existed): sent exactly as it always was.
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            wp_mail($to, $subject, EmailTemplate::render($template, $vars, false), $headers);
            return;
        }

        $body = EmailTemplate::render($template, $vars, true);
        $text = EmailTemplate::to_text($body);
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        // wp_mail() has no multipart API; PHPMailer does. Hand it the text
        // version for this one message only, then take the hook back off so it
        // can't leak into any other email the site sends.
        $add_text_part = static function ($phpmailer) use ($text): void {
            $phpmailer->AltBody = $text;
        };
        add_action('phpmailer_init', $add_text_part);
        try {
            wp_mail($to, $subject, EmailTemplate::wrap_html($body), $headers);
        } finally {
            remove_action('phpmailer_init', $add_text_part);
        }
    }

    /**
     * Name and email of the lead attached to a submission, or blanks.
     *
     * @return array{name:string,email:string}
     */
    private function lead_for_submission(string $uuid): array
    {
        $sub = $this->repos['submissions'] ?? null;
        if (! $sub || ! method_exists($sub, 'find_by_uuid')) {
            return ['name' => '', 'email' => ''];
        }
        $row = $sub->find_by_uuid($uuid);

        return $this->find_lead((int) ($row['lead_id'] ?? 0));
    }

    /**
     * @return array{name:string,email:string}
     */
    private function find_lead(int $lead_id): array
    {
        $lead = $lead_id > 0 ? $this->find('leads', $lead_id) : [];

        return [
            'name'  => (string) ($lead['name'] ?? ''),
            'email' => (string) ($lead['email'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function find(string $repo, int $id): array
    {
        $r = $this->repos[$repo] ?? null;
        if ($id <= 0 || ! $r || ! method_exists($r, 'find')) {
            return [];
        }
        $row = $r->find($id);

        return is_array($row) ? $row : [];
    }

    private function is_enabled(string $key): bool
    {
        $v = $this->settings->get($key, '0');
        return in_array((string) $v, ['1', 'true', 'yes'], true);
    }
}
