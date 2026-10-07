<?php
namespace Quizably\REST;

use Quizably\Admin\ReviewPrompt;

defined( 'ABSPATH' ) || exit;

/**
 * POST /quizably/v1/review-prompt  { "action": "later" | "dismiss" | "reviewed" }
 *
 * Stores the current user's answer to the dashboard review prompt.
 */
final class ReviewPromptController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/review-prompt', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'record'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'action' => [
                        'type'     => 'string',
                        'enum'     => ReviewPrompt::ACTIONS,
                        'required' => true,
                    ],
                ],
            ],
        ]);
    }

    public function record(\WP_REST_Request $req)
    {
        (new ReviewPrompt($this->repos()))->record(get_current_user_id(), (string) $req->get_param('action'));
        return $this->ok(['saved' => true]);
    }
}
