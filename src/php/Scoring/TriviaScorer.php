<?php
namespace Quizably\Scoring;

defined( 'ABSPATH' ) || exit;

final class TriviaScorer implements ScorerInterface
{
    /**
     * Question types where the visitor types or rates something. They can't be
     * right or wrong, so they take no part in the score and don't count toward
     * "x of y correct". Keep in step with VALUE_QUESTION_TYPES in
     * src/shared/questionTypes.js.
     */
    private const UNGRADED_TYPES = ['short_text', 'rating', 'slider'];

    /**
     * Sum `answer.points` for selected answers where `is_correct === 1`.
     * Match the first result whose [score_min, score_max] range contains the
     * score. NULL bounds are treated as open-ended on that side.
     *
     * @inheritDoc
     */
    public function score(array $quiz, array $questions, array $results, array $answers): array
    {
        $score = 0;
        $correctCount = 0;
        // Only questions that can be answered correctly count toward the total.
        $totalQuestions = count(array_filter(
            $questions,
            static fn ($q) => ! in_array($q['type'] ?? '', self::UNGRADED_TYPES, true)
        ));

        // Build answer_id => [is_correct, points] map.
        $answerMap = [];
        foreach ($questions as $q) {
            $qAnswers = isset($q['answers']) && is_array($q['answers']) ? $q['answers'] : [];
            foreach ($qAnswers as $a) {
                $aid = (int) ($a['id'] ?? 0);
                if ($aid > 0) {
                    $answerMap[$aid] = [
                        'is_correct' => (int) ($a['is_correct'] ?? 0) === 1,
                        'points' => (int) ($a['points'] ?? 0),
                    ];
                }
            }
        }

        foreach ($answers as $entry) {
            $selected = isset($entry['answer_ids']) && is_array($entry['answer_ids']) ? $entry['answer_ids'] : [];
            $questionScoredCorrect = false;
            foreach ($selected as $aid) {
                $aid = (int) $aid;
                if (isset($answerMap[$aid]) && $answerMap[$aid]['is_correct']) {
                    $score += $answerMap[$aid]['points'];
                    $questionScoredCorrect = true;
                }
            }
            if ($questionScoredCorrect) {
                $correctCount++;
            }
        }

        // Match first result whose bounds accept the score; NULL bounds are open-ended.
        $matched = null;
        foreach ($results as $r) {
            $min = array_key_exists('score_min', $r) && $r['score_min'] !== null ? (int) $r['score_min'] : null;
            $max = array_key_exists('score_max', $r) && $r['score_max'] !== null ? (int) $r['score_max'] : null;
            $okMin = $min === null || $score >= $min;
            $okMax = $max === null || $score <= $max;
            if ($okMin && $okMax) {
                $matched = (int) ($r['id'] ?? 0);
                break;
            }
        }

        return [
            'result_id' => $matched,
            'score' => $score,
            'breakdown' => [
                'score' => $score,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
            ],
        ];
    }
}
