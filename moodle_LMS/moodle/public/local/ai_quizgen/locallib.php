<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local helper functions for local_ai_quizgen.
 *
 * @package    local_ai_quizgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Mock quiz generation for /api/generate-quiz.
 *
 * @param string $lessoncontent
 * @return array
 */
function local_ai_quizgen_generate_mock(string $lessoncontent): array {
    $topic = trim($lessoncontent) !== '' ? trim($lessoncontent) : 'the submitted lesson content';

    return [
        [
            'question' => "What is the primary outcome expected from {$topic}?",
            'options' => ['Memorization only', 'Conceptual understanding', 'No measurable outcome', 'Skip prerequisites'],
            'answer' => 'Conceptual understanding',
        ],
        [
            'question' => 'Which action best supports adaptive learning in an LMS?',
            'options' => ['Same content for all learners', 'Ignore quiz outcomes', 'Adjust next activity using performance data', 'Disable feedback loops'],
            'answer' => 'Adjust next activity using performance data',
        ],
        [
            'question' => 'In Socratic tutoring, the AI should mostly:',
            'options' => ['Provide direct final answers', 'Ask guiding questions', 'Avoid context', 'Hide learner progress'],
            'answer' => 'Ask guiding questions',
        ],
    ];
}

/**
 * Save generated quiz draft.
 *
 * @param int $userid
 * @param int $courseid
 * @param string $lessoncontent
 * @param array $quizdraft
 * @return void
 */
function local_ai_quizgen_save_draft(int $userid, int $courseid, string $lessoncontent, array $quizdraft): void {
    global $DB;

    $now = time();
    $record = (object) [
        'userid' => $userid,
        'courseid' => $courseid,
        'lessoncontent' => $lessoncontent,
        'generatedquiz' => json_encode($quizdraft, JSON_UNESCAPED_UNICODE),
        'timecreated' => $now,
        'timemodified' => $now,
    ];

    $DB->insert_record('local_ai_quizgen', $record);
}

/**
 * Stub for importing generated questions into Moodle quiz activity.
 *
 * @param int $courseid
 * @param array $quizdraft
 * @return array
 */
function local_ai_quizgen_mock_import_to_quiz(int $courseid, array $quizdraft): array {
    return [
        'status' => 'stubbed',
        'courseid' => $courseid,
        'questioncount' => count($quizdraft),
    ];
}

