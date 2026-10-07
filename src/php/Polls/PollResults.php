<?php
namespace Quizably\Polls;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Vote counts for a poll, shown to visitors after they vote.
 *
 * A poll has one question. The tally is built from completed submissions: every answer option
 * that was picked counts one vote. The result is cached briefly because a popular poll is read
 * by every visitor right after voting, and counting means reading each completed submission.
 */
final class PollResults
{
    /** Seconds the tally is cached. */
    public const CACHE_TTL = 20;

    /** Most completed submissions read per tally, so one huge poll cannot stall the site. */
    private const MAX_SUBMISSIONS = 20000;

    public function register(): void
    {
        // A finished vote must show up in the very next tally, including the voter's own.
        add_action('quizably_submission_completed', [$this, 'forget'], 1, 2);
    }

    /**
     * @param string              $uuid Submission uuid.
     * @param array<string,mixed> $quiz
     */
    public function forget(string $uuid, array $quiz): void
    {
        if (isset($quiz['id'])) {
            delete_transient(self::cache_key((int) $quiz['id']));
        }
    }

    private static function cache_key(int $quiz_id): string
    {
        return 'quizably_poll_' . $quiz_id;
    }

    /**
     * @param array<string,mixed>  $quiz  Quiz row (type must be "poll").
     * @param array<string,object> $repos Plugin repositories.
     * @return array{question_id:int,total:int,options:array<int,array{answer_id:int,label:string,votes:int,percent:float}>}|null
     *         Null when the quiz is not a poll or has no question yet.
     */
    public static function for_quiz( array $quiz, array $repos ): ?array
    {
        if ( 'poll' !== ( $quiz['type'] ?? '' ) ) {
            return null;
        }

        $quiz_id   = (int) $quiz['id'];
        $questions = $repos['questions']->find_by_quiz( $quiz_id );
        if ( empty( $questions ) ) {
            return null;
        }
        $question    = $questions[0];
        $question_id = (int) $question['id'];

        $answers = $repos['answers']->find_by_question( $question_id );
        if ( empty( $answers ) ) {
            return null;
        }

        $cache_key = self::cache_key($quiz_id);
        $votes     = get_transient( $cache_key );
        if ( ! is_array( $votes ) ) {
            $votes = self::count_votes( $quiz_id, $question_id );
            set_transient( $cache_key, $votes, self::CACHE_TTL );
        }

        $total = array_sum( $votes );
        $out   = [];
        foreach ( $answers as $a ) {
            $id    = (int) $a['id'];
            $n     = (int) ( $votes[ $id ] ?? 0 );
            $out[] = [
                'answer_id' => $id,
                'label'     => (string) ( $a['label'] ?? '' ),
                'votes'     => $n,
                'percent'   => $total > 0 ? round( $n / $total * 100, 1 ) : 0.0,
            ];
        }

        return [
            'question_id' => $question_id,
            'total'       => (int) $total,
            'options'     => $out,
        ];
    }

    /**
     * Votes per answer id for one question, from completed submissions.
     *
     * @return array<int,int>
     */
    private static function count_votes( int $quiz_id, int $question_id ): array
    {
        global $wpdb;
        $table = Schema::table_name( 'submissions', $wpdb );
        $rows  = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT answers FROM {$table} WHERE quiz_id = %d AND status = 'completed' ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
                $quiz_id,
                self::MAX_SUBMISSIONS
            )
        );

        $votes = [];
        foreach ( (array) $rows as $json ) {
            $decoded = json_decode( (string) $json, true );
            if ( ! is_array( $decoded ) ) {
                continue;
            }
            foreach ( $decoded as $entry ) {
                if ( ! is_array( $entry ) || (int) ( $entry['question_id'] ?? 0 ) !== $question_id ) {
                    continue;
                }
                foreach ( (array) ( $entry['answer_ids'] ?? [] ) as $aid ) {
                    $aid           = (int) $aid;
                    $votes[ $aid ] = ( $votes[ $aid ] ?? 0 ) + 1;
                }
            }
        }
        return $votes;
    }
}
