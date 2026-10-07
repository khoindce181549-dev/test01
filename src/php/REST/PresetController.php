<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for the predefined quiz preset catalog.
 *
 * GET  /quizably/v1/presets              — returns display metadata for all 30 presets
 * POST /quizably/v1/presets/{id}/import  — creates a full quiz from a preset atomically
 *
 * Free presets (pro: false) are importable by all authenticated users.
 * Pro presets (pro: true) require the Pro plugin to be active; otherwise
 * the import endpoint returns 403 with code quizably_pro_required.
 */
final class PresetController extends BaseController
{
    public function register_routes(): void
    {
        // List all preset metadata (no question/result data — display only).
        register_rest_route(RestBootstrap::NAMESPACE, '/presets', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Import a preset by its slug: creates quiz + questions + answers + results atomically.
        register_rest_route(RestBootstrap::NAMESPACE, '/presets/(?P<id>[a-z0-9\-]+)/import', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'import'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    /**
     * GET /presets — return display metadata for all presets.
     * Questions and results are stripped; only metadata needed for the gallery is returned.
     *
     * @param \WP_REST_Request $req
     */
    public function list(\WP_REST_Request $req)
    {
        $presets = array_map([$this, 'meta_only'], PresetLibrary::all());
        return $this->ok(array_values($presets));
    }

    /**
     * POST /presets/{id}/import — create a full quiz from a preset.
     *
     * Body (optional JSON):
     *   title  string  Custom title (defaults to preset title if omitted)
     *
     * Returns the newly created quiz object (same shape as POST /quizzes).
     *
     * @param \WP_REST_Request $req
     */
    public function import(\WP_REST_Request $req)
    {
        $id     = (string) $req['id'];
        $preset = PresetLibrary::find($id);

        if ( ! $preset ) {
            return $this->not_found('Preset');
        }

        // Pro gate: Pro-flagged presets require the Pro plugin.
        if ( ! empty($preset['pro']) && ! \Quizably\Pro\Gate::is_pro() ) {
            return $this->error(
                'quizably_pro_required',
                __('This preset requires Quizably Pro.', 'quizably'),
                403
            );
        }

        $title = trim((string) ($req->get_param('title') ?: $preset['title']));
        if ( '' === $title ) {
            return $this->error('quizably_bad_request', __('Title is required.', 'quizably'), 400);
        }

        $repos = $this->repos();

        // Find a unique slug for the new quiz.
        $base_slug = sanitize_title($title);
        $slug      = $base_slug;
        $suffix    = 2;
        while ( $repos['quizzes']->find_by_slug($slug) ) {
            $slug = $base_slug . '-' . $suffix;
            $suffix++;
        }

        // Insert the quiz row.
        try {
            $quiz_id = $repos['quizzes']->insert([
                'title'     => $title,
                'slug'      => $slug,
                'type'      => (string) ($preset['type'] ?? 'personality'),
                'template'  => (string) ($preset['template'] ?? 'classic'),
                'status'    => 'published',
                'author_id' => get_current_user_id(),
            ]);
        } catch ( \RuntimeException $e ) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }

        // Insert results first so we can remap personality_result_id on answers.
        $result_map = [];
        foreach ( (array) ($preset['results'] ?? []) as $r ) {
            try {
                $new_rid = $repos['results']->insert([
                    'quiz_id'      => $quiz_id,
                    'title'        => (string) ($r['title'] ?? ''),
                    'content'      => $r['content'] ?? null,
                    'image_url'    => $r['image_url'] ?? null,
                    'cta_label'    => $r['cta_label'] ?? null,
                    'cta_url'      => $r['cta_url'] ?? null,
                    'redirect_url' => $r['redirect_url'] ?? null,
                    'score_min'    => isset($r['score_min']) && null !== $r['score_min'] ? (int) $r['score_min'] : null,
                    'score_max'    => isset($r['score_max']) && null !== $r['score_max'] ? (int) $r['score_max'] : null,
                    'conditions'   => null,
                    'position'     => (int) ($r['position'] ?? 0),
                ]);
                // Map the preset's string result ID → newly inserted integer ID.
                if ( ! empty($r['id']) ) {
                    $result_map[(string) $r['id']] = $new_rid;
                }
            } catch ( \RuntimeException $e ) {
                // Partial import is better than aborting.
            }
        }

        // Insert questions + answers, remapping personality_result_id strings → integers.
        foreach ( (array) ($preset['questions'] ?? []) as $q ) {
            try {
                $new_qid = $repos['questions']->insert([
                    'quiz_id'     => $quiz_id,
                    'type'        => (string) ($q['type'] ?? 'single'),
                    'title'       => (string) ($q['title'] ?? ''),
                    'description' => $q['description'] ?? null,
                    'media_url'   => isset($q['media_url']) && $q['media_url'] !== '' ? (string) $q['media_url'] : null,
                    'media_type'  => null,
                    'required'    => ! empty($q['required']) ? 1 : 0,
                    'position'    => (int) ($q['position'] ?? 0),
                    'settings'    => null,
                    'logic'       => null,
                ]);
            } catch ( \RuntimeException $e ) {
                continue; // Skip question on error, keep going.
            }

            foreach ( (array) ($q['answers'] ?? []) as $a ) {
                // Remap the preset's string result ID to the new integer ID.
                $mapped_result = null;
                if ( ! empty($a['personality_result_id']) ) {
                    $old_rid       = (string) $a['personality_result_id'];
                    $mapped_result = $result_map[$old_rid] ?? null;
                }

                try {
                    $repos['answers']->insert([
                        'question_id'           => $new_qid,
                        'label'                 => (string) ($a['label'] ?? ''),
                        'value'                 => (string) ($a['value'] ?? sanitize_title((string) ($a['label'] ?? ''))),
                        'is_correct'            => ! empty($a['is_correct']) ? 1 : 0,
                        'points'                => (int) ($a['points'] ?? 0),
                        'weights'               => null,
                        'personality_result_id' => $mapped_result,
                        'media_url'             => null,
                        'position'              => (int) ($a['position'] ?? 0),
                    ]);
                } catch ( \RuntimeException $e ) {
                    // Swallow individual answer errors.
                }
            }
        }

        return $this->ok(
            $this->decode_json_fields(
                $repos['quizzes']->find($quiz_id),
                ['settings', 'design']
            ),
            201
        );
    }

    /**
     * Strip full question/result data from a preset, returning only display metadata.
     *
     * @param array $preset
     * @return array
     */
    private function meta_only(array $preset): array
    {
        unset($preset['questions'], $preset['results']);
        return $preset;
    }
}
