<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class RestBootstrap
{
    public const NAMESPACE = 'quizably/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/ping',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'permission_callback' => '__return_true',
                'callback'            => static function () {
                    return [
                        'ok'      => true,
                        'version' => QUIZABLY_VERSION,
                    ];
                },
            ]
        );

        $controllers = [
            new QuizController(),
            new QuestionController(),
            new QuestionBankController(),
            new AnswerController(),
            new ResultController(),
            new SubmissionController(),
            new LeadController(),
            new SettingsController(),
            new TemplateController(),
            new IntegrationController(),
            new PublicController(),
            new AnalyticsController(),
            new PresetController(),
            new ReviewPromptController(),
        ];
        foreach ($controllers as $c) {
            $c->register_routes();
        }
    }
}
