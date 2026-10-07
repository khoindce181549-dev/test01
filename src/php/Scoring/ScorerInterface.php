<?php
namespace Quizably\Scoring;

defined( 'ABSPATH' ) || exit;

interface ScorerInterface
{
    /**
     * @param array $quiz     Full quiz row (with 'type')
     * @param array $questions Array of question rows, each with nested 'answers'
     * @param array $results  Array of result rows
     * @param array $answers  [{question_id:int, answer_ids:int[], text_value?:string}]
     * @return array ['result_id' => int|null, 'score' => int|null, 'breakdown' => array]
     */
    public function score(array $quiz, array $questions, array $results, array $answers): array;
}
