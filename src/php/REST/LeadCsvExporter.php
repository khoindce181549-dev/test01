<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Turns lead rows into a flat CSV table (header + rows) for the Leads export.
 *
 * COLUMN CONTRACT (stable, in this order):
 *
 *   id, email, name, phone, consent_gdpr, created_at,      <- the original six; never reordered
 *   quiz_id, quiz_title, result_title, score,
 *   double_optin_status,                                    <- not_required | pending | verified
 *   utm_source, utm_medium, utm_campaign, utm_term, utm_content,
 *   extra_<key>...                                          <- one per custom lead-form field seen in the export, first-seen order
 *   <question title>...                                     <- one per question of every exported quiz, in quiz then position order
 *
 * Only the fixed prefix is guaranteed; the trailing extra_* and question columns depend on the
 * quizzes being exported. Result, score, UTM and answers come from the lead's latest completed
 * submission, or its latest submission of any status when none finished (a lead can be captured
 * before the quiz is completed).
 *
 * Question cells: choice questions list the chosen option labels joined with "; " (so multi-select
 * stays in one cell); rating, slider and text questions carry the raw value the visitor entered.
 * An unanswered question is an empty cell.
 *
 * CSV injection: every cell that originates from visitor or author input is neutralised per OWASP
 * (a leading = + - @ tab or CR gets a single-quote prefix) so spreadsheets never evaluate it as a
 * formula. System-generated numeric/date columns are left untouched.
 */
final class LeadCsvExporter
{
    public const BASE_COLUMNS = [
        'id', 'email', 'name', 'phone', 'consent_gdpr', 'created_at',
        'quiz_id', 'quiz_title', 'result_title', 'score',
        'double_optin_status',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    ];

    /** Columns whose values are produced by the plugin, not typed by a visitor or author. */
    private const SYSTEM_COLUMNS = ['id', 'consent_gdpr', 'created_at', 'quiz_id', 'score', 'double_optin_status'];

    /** @var array<string,object> */
    private array $repos;
    /** @var array<int,?array> */
    private array $quizzes = [];
    /** @var array<int,?array> */
    private array $results = [];
    /** @var array<int,array<int,array>> quiz_id => questions */
    private array $questions = [];
    /** @var array<int,array<int,string>> question_id => [answer_id => label] */
    private array $labels = [];

    /**
     * @param array<string,object> $repos Plugin repositories (quizzes, results, questions, answers, submissions).
     */
    public function __construct(array $repos)
    {
        $this->repos = $repos;
    }

    /**
     * Neutralise a spreadsheet formula trigger (OWASP "CSV Injection").
     */
    public static function neutralize(string $value): string
    {
        if ('' !== $value && false !== strpos("=+-@\t\r", $value[0])) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * @param array<int,array<string,mixed>> $leads Rows from LeadRepository::list().
     * @return array{header:array<int,string>,rows:array<int,array<int,string>>}
     */
    public function build(array $leads): array
    {
        $records     = [];
        $extra_cols  = [];
        $question_cols = []; // 'q<id>' => title, first-seen quiz order then question position

        foreach ($leads as $lead) {
            $quiz_id = (int) ($lead['quiz_id'] ?? 0);
            $quiz    = $this->quiz($quiz_id);
            $sub     = $this->submission((int) ($lead['id'] ?? 0));

            foreach ($this->questions_for($quiz_id) as $q) {
                $question_cols['q' . (int) $q['id']] = (string) ($q['title'] ?? '');
            }

            $utm = $this->decode($sub['utm'] ?? ($lead['submission_utm'] ?? null));
            $result_title = '';
            if ($sub && ! empty($sub['result_id'])) {
                $result_title = (string) ($this->result((int) $sub['result_id'])['title'] ?? '');
            }

            $cells = [
                'id'                  => (string) ($lead['id'] ?? ''),
                'email'               => (string) ($lead['email'] ?? ''),
                'name'                => (string) ($lead['name'] ?? ''),
                'phone'               => (string) ($lead['phone'] ?? ''),
                'consent_gdpr'        => (string) ($lead['consent_gdpr'] ?? ''),
                'created_at'          => (string) ($lead['created_at'] ?? ''),
                'quiz_id'             => $quiz_id > 0 ? (string) $quiz_id : '',
                'quiz_title'          => (string) ($quiz['title'] ?? ''),
                'result_title'        => $result_title,
                'score'               => isset($sub['score']) ? (string) $sub['score'] : '',
                'double_optin_status' => $this->optin_status($lead['double_optin_verified'] ?? null),
                'utm_source'          => $this->scalar($utm['utm_source'] ?? ''),
                'utm_medium'          => $this->scalar($utm['utm_medium'] ?? ''),
                'utm_campaign'        => $this->scalar($utm['utm_campaign'] ?? ''),
                'utm_term'            => $this->scalar($utm['utm_term'] ?? ''),
                'utm_content'         => $this->scalar($utm['utm_content'] ?? ''),
            ];

            foreach ($this->decode($lead['extra_fields'] ?? null) as $key => $value) {
                $col = 'extra_' . $key;
                $extra_cols[$col] = true;
                $cells[$col]      = $this->scalar($value);
            }

            foreach ($this->answers_by_question($sub) as $qid => $answer) {
                $cells['q' . $qid] = $this->format_answer($qid, $answer);
            }

            $records[] = $cells;
        }

        $extra_keys = array_keys($extra_cols);
        $header     = array_merge(self::BASE_COLUMNS, $extra_keys, array_values($question_cols));
        $keys       = array_merge(self::BASE_COLUMNS, $extra_keys, array_keys($question_cols));

        $rows = [];
        foreach ($records as $cells) {
            $row = [];
            foreach ($keys as $k) {
                $v     = $cells[$k] ?? '';
                $row[] = in_array($k, self::SYSTEM_COLUMNS, true) ? $v : self::neutralize($v);
            }
            $rows[] = $row;
        }

        return [
            'header' => array_map([self::class, 'neutralize'], $header),
            'rows'   => $rows,
        ];
    }

    private function optin_status($raw): string
    {
        if (null === $raw || '' === $raw) {
            return 'not_required';
        }
        return 1 === (int) $raw ? 'verified' : 'pending';
    }

    /**
     * @param mixed $value
     */
    private function scalar($value): string
    {
        if (is_array($value)) {
            return implode('; ', array_map(fn($v) => $this->scalar($v), $value));
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return null === $value ? '' : (string) $value;
    }

    /**
     * @param mixed $raw JSON string or already-decoded array
     * @return array<string,mixed>
     */
    private function decode($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || '' === $raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string,mixed>|null */
    private function submission(int $lead_id): ?array
    {
        if ($lead_id < 1 || empty($this->repos['submissions'])) {
            return null;
        }
        $sub = $this->repos['submissions']->find_latest_by_lead_id($lead_id);
        if (! $sub) {
            $sub = $this->repos['submissions']->find_latest_any_by_lead_id($lead_id);
        }
        return is_array($sub) ? $sub : null;
    }

    /**
     * @param array<string,mixed>|null $sub
     * @return array<int,array<string,mixed>> question_id => answer entry
     */
    private function answers_by_question(?array $sub): array
    {
        $out = [];
        foreach ($this->decode($sub['answers'] ?? null) as $entry) {
            if (is_array($entry) && ! empty($entry['question_id'])) {
                $out[(int) $entry['question_id']] = $entry;
            }
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $answer
     */
    private function format_answer(int $question_id, array $answer): string
    {
        $ids = is_array($answer['answer_ids'] ?? null) ? array_map('intval', $answer['answer_ids']) : [];
        if ($ids) {
            $labels = $this->labels_for($question_id);
            $chosen = [];
            foreach ($ids as $id) {
                if (isset($labels[$id])) {
                    $chosen[] = $labels[$id];
                }
            }
            if ($chosen) {
                return implode('; ', $chosen);
            }
        }
        // Rating, slider and text answers are stored as the raw value, not as an option.
        return $this->scalar($answer['text_value'] ?? '');
    }

    /** @return array<int,string> */
    private function labels_for(int $question_id): array
    {
        if (! isset($this->labels[$question_id])) {
            $map = [];
            if (! empty($this->repos['answers'])) {
                foreach ($this->repos['answers']->find_by_question($question_id) as $o) {
                    $map[(int) $o['id']] = (string) ($o['label'] ?? '');
                }
            }
            $this->labels[$question_id] = $map;
        }
        return $this->labels[$question_id];
    }

    /** @return array<string,mixed>|null */
    private function quiz(int $quiz_id): ?array
    {
        if ($quiz_id < 1 || empty($this->repos['quizzes'])) {
            return null;
        }
        if (! array_key_exists($quiz_id, $this->quizzes)) {
            $this->quizzes[$quiz_id] = $this->repos['quizzes']->find($quiz_id);
        }
        return $this->quizzes[$quiz_id];
    }

    /** @return array<string,mixed>|null */
    private function result(int $result_id): ?array
    {
        if (empty($this->repos['results'])) {
            return null;
        }
        if (! array_key_exists($result_id, $this->results)) {
            $this->results[$result_id] = $this->repos['results']->find($result_id);
        }
        return $this->results[$result_id];
    }

    /** @return array<int,array<string,mixed>> */
    private function questions_for(int $quiz_id): array
    {
        if ($quiz_id < 1 || empty($this->repos['questions'])) {
            return [];
        }
        if (! isset($this->questions[$quiz_id])) {
            $this->questions[$quiz_id] = $this->repos['questions']->find_by_quiz($quiz_id);
        }
        return $this->questions[$quiz_id];
    }
}
