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
 * Plugin callbacks for local_ai_quizgen.
 *
 * @package    local_ai_quizgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add AI quiz generator shortcut under course settings navigation.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 * @return void
 */
function local_ai_quizgen_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    $url = new moodle_url('/local/ai_quizgen/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('navlabel', 'local_ai_quizgen'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_ai_quizgen'
    );
}

