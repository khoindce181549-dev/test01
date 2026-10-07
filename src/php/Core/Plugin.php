<?php
namespace Quizably\Core;

defined( 'ABSPATH' ) || exit;

final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    /** @var array<string,object> */
    private array $services = [];

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->register_services();

        do_action('quizably_booted', $this);

        $this->booted = true;
    }

    // No manual load_plugin_textdomain() call: WordPress has auto-loaded
    // translations for .org-hosted plugins by slug since 4.6, using the
    // Text Domain / Domain Path headers already declared in the main plugin
    // file. A manual call is discouraged (Plugin Check flags it) and can
    // load translations earlier than, and out of step with, that mechanism.

    /** @return array<string,object> */
    public function services(): array
    {
        return $this->services;
    }

    private function register_services(): void
    {
        // Repositories first (no WP dependencies beyond $wpdb being available).
        $this->services['repos'] = [
            'quizzes'       => new \Quizably\Database\Repository\QuizRepository(),
            'questions'     => new \Quizably\Database\Repository\QuestionRepository(),
            'answers'       => new \Quizably\Database\Repository\AnswerRepository(),
            'results'       => new \Quizably\Database\Repository\ResultRepository(),
            'submissions'   => new \Quizably\Database\Repository\SubmissionRepository(),
            'leads'         => new \Quizably\Database\Repository\LeadRepository(),
            'settings'      => new \Quizably\Database\Repository\SettingsRepository(),
            'question_bank' => new \Quizably\Database\Repository\QuestionBankRepository(),
        ];

        // Feature services (each has a register() method).
        $this->services['rest']            = new \Quizably\REST\RestBootstrap();
        $this->services['admin_menu']      = new \Quizably\Admin\Menu();
        $this->services['review_prompt']   = new \Quizably\Admin\ReviewPrompt($this->services['repos']);
        $this->services['admin_assets']    = new \Quizably\Admin\Assets($this->services['review_prompt']);
        $this->services['frontend_assets'] = new \Quizably\Frontend\Assets();
        $this->services['shortcode']       = new \Quizably\Shortcode\QuizShortcode(
            $this->services['frontend_assets']
        );
        $this->services['quiz_result_shortcode'] = new \Quizably\Shortcode\QuizResultShortcode();
        $this->services['quiz_popup_shortcode']  = new \Quizably\Shortcode\QuizPopupShortcode(
            $this->services['frontend_assets']
        );
        $this->services['quiz_slidein_shortcode'] = new \Quizably\Shortcode\QuizSlideinShortcode(
            $this->services['frontend_assets']
        );
        $this->services['quiz_embed_handler'] = new \Quizably\Shortcode\QuizEmbedHandler();
        $this->services['optin_confirmation'] = new \Quizably\Frontend\OptinConfirmation();
        $this->services['poll_results']       = new \Quizably\Polls\PollResults();
        $this->services['integration_dispatcher'] = new \Quizably\Integration\Dispatcher();
        $this->services['mailer']          = new \Quizably\Notification\Mailer(
            $this->services['repos']['settings'],
            $this->services['repos']
        );
        $this->services['block']           = new \Quizably\Block\QuizBlock(
            $this->services['shortcode']
        );

        foreach ($this->services as $service) {
            if (is_object($service) && method_exists($service, 'register')) {
                $service->register();
            }
        }

        // Run pending migrations on admin boot (covers plugin-update case).
        if (function_exists('is_admin') && is_admin()) {
            (new \Quizably\Database\Installer())->maybe_upgrade();
        }
    }

    private function __construct() {}
    private function __clone() {}
}
