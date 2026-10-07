<?php
namespace Quizably\Scoring;

defined( 'ABSPATH' ) || exit;

/**
 * Survey/poll scorer — returns the first configured result as a
 * "thank you" / confirmation screen. Captures answers without a winner.
 */
final class SurveyScorer implements ScorerInterface
{
    /**
     * @inheritDoc
     */
    public function score(array $quiz, array $questions, array $results, array $answers): array
    {
        $first = null;
        if (!empty($results)) {
            $first_row = reset($results);
            if (is_array($first_row) && isset($first_row['id'])) {
                $first = (int) $first_row['id'];
            }
        }

        return [
            'result_id' => $first,
            'score' => null,
            'breakdown' => [
                'answered_questions' => count($answers),
                'total_questions' => count($questions),
            ],
        ];
    }
}
