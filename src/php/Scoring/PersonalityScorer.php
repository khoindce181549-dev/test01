<?php
namespace Quizably\Scoring;

defined( 'ABSPATH' ) || exit;

final class PersonalityScorer implements ScorerInterface
{
    /**
     * Tally `answer.personality_result_id` across the user's selected answers.
     * Winner is the result with the most votes. Ties broken by `position` ASC,
     * falling back to `id` ASC (stable with repo sort order).
     *
     * @inheritDoc
     */
    public function score(array $quiz, array $questions, array $results, array $answers): array
    {
        $tally = [];

        // Build an answer_id => personality_result_id map for fast lookup.
        $answerMap = [];
        foreach ($questions as $q) {
            $qAnswers = isset($q['answers']) && is_array($q['answers']) ? $q['answers'] : [];
            foreach ($qAnswers as $a) {
                $aid = (int) ($a['id'] ?? 0);
                $rid = isset($a['personality_result_id']) && $a['personality_result_id'] !== null
                    ? (int) $a['personality_result_id']
                    : null;
                if ($aid > 0) {
                    $answerMap[$aid] = $rid;
                }
            }
        }

        foreach ($answers as $entry) {
            $selected = isset($entry['answer_ids']) && is_array($entry['answer_ids']) ? $entry['answer_ids'] : [];
            foreach ($selected as $aid) {
                $aid = (int) $aid;
                if (isset($answerMap[$aid]) && $answerMap[$aid] !== null) {
                    $rid = $answerMap[$aid];
                    $tally[$rid] = ($tally[$rid] ?? 0) + 1;
                }
            }
        }

        $breakdown = [
            'tally' => $tally,
            'total_answers' => array_sum($tally),
        ];

        if (empty($tally)) {
            return ['result_id' => null, 'score' => null, 'breakdown' => $breakdown];
        }

        // Find max votes.
        $maxVotes = max($tally);
        $candidates = [];
        foreach ($tally as $rid => $votes) {
            if ($votes === $maxVotes) {
                $candidates[] = (int) $rid;
            }
        }

        // Tie-break by the order results appear (position ASC — repo already sorts by position,id).
        $winner = null;
        foreach ($results as $r) {
            $rid = (int) ($r['id'] ?? 0);
            if (in_array($rid, $candidates, true)) {
                $winner = $rid;
                break;
            }
        }

        return [
            'result_id' => $winner,
            'score' => null,
            'breakdown' => $breakdown,
        ];
    }
}
