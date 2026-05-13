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
 * Local helper functions for local_ai_pathgen.
 *
 * @package    local_ai_pathgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Mock generator that simulates Flask /api/generate-path response.
 *
 * @param string $goal
 * @param string $skills
 * @return array
 */
function local_ai_pathgen_generate_path_mock(string $goal, string $skills): array {
    $goaltext = trim($goal) !== '' ? trim($goal) : 'your selected target';
    $skilltext = trim($skills) !== '' ? trim($skills) : 'your current baseline';

    return [
        "Week 1: Foundations aligned to {$goaltext}",
        "Week 2: Practice track based on {$skilltext}",
        'Week 3: Guided project sprint with Socratic tutor checkpoints',
        'Week 4: Quiz + reflection + adaptive next-step recommendations',
    ];
}

/**
 * Persist path output and mirror latest result in user profile preferences.
 *
 * @param int $userid
 * @param string $goal
 * @param string $skills
 * @param array $pathitems
 * @return void
 */
function local_ai_pathgen_save_generated_path(int $userid, string $goal, string $skills, array $pathitems): void {
    global $DB;

    $now = time();
    $record = (object) [
        'userid' => $userid,
        'goal' => $goal,
        'skills' => $skills,
        'generatedpath' => json_encode(array_values($pathitems), JSON_UNESCAPED_UNICODE),
        'timecreated' => $now,
        'timemodified' => $now,
    ];

    $DB->insert_record('local_ai_pathgen', $record);

    set_user_preference('local_ai_pathgen_goal', $goal, $userid);
    set_user_preference('local_ai_pathgen_skills', $skills, $userid);
    set_user_preference('local_ai_pathgen_latest_path', $record->generatedpath, $userid);
}

