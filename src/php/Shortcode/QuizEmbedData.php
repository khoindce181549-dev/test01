<?php
namespace Quizably\Shortcode;

use Quizably\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * What every way of showing a quiz on a page needs: find the published quiz from the id someone
 * typed, and hand the player the quiz data it renders from.
 *
 * The inline shortcode, the Gutenberg block, the popup and the slide-in all go through here, so a
 * quiz embedded behind a button gets exactly the same data as one embedded inline. (The player
 * reads the quiz from a JSON script tag in the page; it does not fetch it.)
 */
final class QuizEmbedData
{
    /** @var array<string,bool> Quiz uuids whose data script is already in this page. */
    private static array $emitted = [];

    /**
     * Find a published quiz by numeric id or UUID.
     *
     * @return array<string,mixed>|null
     */
    public static function find_published( string $id ): ?array
    {
        $id = trim( $id );
        if ( '' === $id ) {
            return null;
        }

        $repos = Plugin::instance()->services()['repos'] ?? [];
        if ( empty( $repos['quizzes'] ) ) {
            return null;
        }

        // Accept either a numeric quiz ID or a UUID (v4-ish: 36 chars, hex + hyphens).
        if ( preg_match( '/^[a-f0-9\-]{36}$/i', $id ) ) {
            $quiz = $repos['quizzes']->find_by_uuid( $id );
        } elseif ( is_numeric( $id ) ) {
            $quiz = $repos['quizzes']->find( (int) $id );
        } else {
            $quiz = null;
        }

        if ( ! $quiz || ( $quiz['status'] ?? '' ) !== 'published' ) {
            return null;
        }
        return $quiz;
    }

    /**
     * The JSON script tag the player reads the quiz from. A page can show the same quiz more than
     * once (an inline copy and a popup, say), so the tag is only printed the first time.
     *
     * @param array<string,mixed> $quiz
     */
    public static function data_script( array $quiz ): string
    {
        $uuid = (string) ( $quiz['uuid'] ?? '' );
        if ( '' === $uuid || isset( self::$emitted[ $uuid ] ) ) {
            return '';
        }

        $repos = Plugin::instance()->services()['repos'] ?? [];
        if ( ! $repos ) {
            return '';
        }
        self::$emitted[ $uuid ] = true;

        // JSON_HEX_TAG escapes every `<`/`>` as </>, so nothing in
        // quiz/question/answer text (however it got into the DB) can contain a
        // literal `</script>` in any case combination and prematurely close
        // this element — a case-sensitive str_replace('</script', ...) blacklist
        // does not catch `</SCRIPT>`, which HTML's tag-name matching treats as
        // an equally valid closing tag. JSON_HEX_AMP stops the same trick via
        // an already-escaped `<` reassembled from `&lt;` + raw `;` etc.
        $json = wp_json_encode( self::public_payload( $quiz, $repos ), JSON_HEX_TAG | JSON_HEX_AMP );
        if ( false === $json ) {
            $json = '{}';
        }

        return sprintf(
            '<script type="application/json" id="quizably-data-%s">%s</script>',
            esc_attr( $uuid ),
            $json
        );
    }

    /** Forget which quizzes already have a data script (for tests that render several pages in one process). */
    public static function reset(): void
    {
        self::$emitted = [];
    }

    /**
     * Build the same "public-safe" quiz payload that `GET /public/quiz/{uuid}`
     * serves — strips answer scoring metadata and result conditions before
     * handing the data to the client.
     *
     * Branching logic rules ARE included: they contain only navigational data
     * (answer_id → go_to_question_id) which is not sensitive — answer IDs are
     * already rendered in the DOM, and revealing "if you pick A, skip to Q5"
     * does not expose correct answers or scoring weights.  The frontend's
     * nextQuestionIndexFor() evaluates them client-side so no server round-trip
     * is needed on every "Next" click.
     *
     * @param array<string,mixed>  $quiz
     * @param array<string,object> $repos
     * @return array<string,mixed>
     */
    public static function public_payload( array $quiz, array $repos ): array
    {
        $quiz = self::decode_json( $quiz, [ 'settings', 'design' ] );
        unset( $quiz['author_id'] );

        $questions = [];
        foreach ( $repos['questions']->find_by_quiz( (int) $quiz['id'] ) as $q ) {
            $q = self::decode_json( $q, [ 'settings', 'logic' ] );

            $answers = [];
            foreach ( $repos['answers']->find_by_question( (int) $q['id'] ) as $a ) {
                unset( $a['is_correct'], $a['points'], $a['weights'] );
                $answers[] = $a;
            }
            $q['answers'] = $answers;
            $questions[]  = $q;
        }

        $results = [];
        foreach ( $repos['results']->find_by_quiz( (int) $quiz['id'] ) as $r ) {
            // Decode `settings` so the frontend receives an object (not a JSON
            // string) — required for per-result background, alignment, etc.
            $r = self::decode_json( $r, [ 'settings' ] );
            unset( $r['conditions'] );
            $results[] = $r;
        }

        $quiz['questions'] = $questions;
        $quiz['results']   = $results;
        return $quiz;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,string>   $fields
     * @return array<string,mixed>
     */
    private static function decode_json( array $row, array $fields ): array
    {
        foreach ( $fields as $f ) {
            if ( isset( $row[ $f ] ) && is_string( $row[ $f ] ) && '' !== $row[ $f ] ) {
                $decoded = json_decode( $row[ $f ], true );
                $row[ $f ] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : null;
            }
        }
        return $row;
    }
}
