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
 * UI page for local_ai_pathgen.
 *
 * @package    local_ai_pathgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

$context = context_system::instance();
$url = new moodle_url('/local/ai_pathgen/index.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pageheading', 'local_ai_pathgen'));
$PAGE->set_heading(get_string('pageheading', 'local_ai_pathgen'));

$goal = optional_param('goal', get_user_preferences('local_ai_pathgen_goal', ''), PARAM_TEXT);
$interests = optional_param('interests', get_user_preferences('local_ai_pathgen_interests', ''), PARAM_TEXT);
$skills = optional_param('skills', get_user_preferences('local_ai_pathgen_skills', ''), PARAM_TEXT);
$submitted = optional_param('submitpathgen', 0, PARAM_BOOL);

if ($submitted && confirm_sesskey()) {
    $pathitems = local_ai_pathgen_generate_path_mock($goal, $skills, $interests);
    local_ai_pathgen_save_generated_path((int)$USER->id, $goal, $skills, $interests, $pathitems);
    redirect($url, get_string('saved', 'local_ai_pathgen'), 0, \core\output\notification::NOTIFY_SUCCESS);
}

$latestrecords = $DB->get_records('local_ai_pathgen', ['userid' => $USER->id], 'timemodified DESC', '*', 0, 1);
$latest = $latestrecords ? reset($latestrecords) : false;
$latestitems = [];
if ($latest && !empty($latest->generatedpath)) {
    $decoded = json_decode($latest->generatedpath, true);
    if (is_array($decoded)) {
        $latestitems = $decoded;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('mocknotice', 'local_ai_pathgen'), \core\output\notification::NOTIFY_INFO);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('goal', 'local_ai_pathgen'), ['for' => 'id_goal', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($goal), ['name' => 'goal', 'id' => 'id_goal', 'rows' => 3, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('interests', 'local_ai_pathgen'), ['for' => 'id_interests', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($interests), ['name' => 'interests', 'id' => 'id_interests', 'rows' => 3, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('skills', 'local_ai_pathgen'), ['for' => 'id_skills', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($skills), ['name' => 'skills', 'id' => 'id_skills', 'rows' => 3, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitpathgen', 'value' => 1]);
echo html_writer::tag('button', get_string('generate', 'local_ai_pathgen'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo html_writer::tag('h3', get_string('latestpath', 'local_ai_pathgen'));
if (empty($latestitems)) {
    echo html_writer::tag('p', get_string('nopathyet', 'local_ai_pathgen'));
} else {
    $items = [];
    foreach ($latestitems as $item) {
        $items[] = html_writer::tag('li', format_string($item));
    }
    echo html_writer::tag('ol', implode('', $items));
}

echo $OUTPUT->footer();
