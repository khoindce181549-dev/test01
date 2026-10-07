<?php
namespace Quizably\Scoring;

defined( 'ABSPATH' ) || exit;

/**
 * Factory that returns the appropriate scorer for a quiz type.
 * Allows third-party plugins to register their own scorers via the
 * `quizably_scorer` filter.
 */
final class Registry
{
    public static function get(string $type): ?ScorerInterface
    {
        $scorer = null;
        switch ($type) {
            case 'personality':
                $scorer = new PersonalityScorer();
                break;
            case 'trivia':
                $scorer = new TriviaScorer();
                break;
            case 'survey':
            case 'poll':
                $scorer = new SurveyScorer();
                break;
        }

        if (function_exists('apply_filters')) {
            /** @var ScorerInterface|null $scorer */
            $scorer = apply_filters('quizably_scorer', $scorer, $type);
        }

        return $scorer instanceof ScorerInterface ? $scorer : null;
    }
}
