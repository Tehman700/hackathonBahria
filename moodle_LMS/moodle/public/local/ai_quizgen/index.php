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
 * UI page for local_ai_quizgen.
 *
 * @package    local_ai_quizgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$course = null;
$context = context_system::instance();
$params = [];

if ($courseid > 0) {
    $course = get_course($courseid);
    $context = context_course::instance($courseid);
    require_capability('moodle/course:update', $context);
    $params['courseid'] = $courseid;
} else {
    require_capability('moodle/site:config', $context);
}

$url = new moodle_url('/local/ai_quizgen/index.php', $params);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pageheading', 'local_ai_quizgen'));
$PAGE->set_heading(get_string('pageheading', 'local_ai_quizgen'));

$lessoncontent = optional_param('lessoncontent', '', PARAM_TEXT);
$submitted = optional_param('submitquizgen', 0, PARAM_BOOL);
$previewquiz = [];
$importresult = null;

if ($submitted && confirm_sesskey()) {
    $previewquiz = local_ai_quizgen_generate_mock($lessoncontent);
    local_ai_quizgen_save_draft((int)$USER->id, $courseid, $lessoncontent, $previewquiz);
    $importresult = local_ai_quizgen_mock_import_to_quiz($courseid, $previewquiz);
    redirect($url, get_string('saved', 'local_ai_quizgen'), 0, \core\output\notification::NOTIFY_SUCCESS);
}

$latestrecords = $DB->get_records(
    'local_ai_quizgen',
    ['userid' => $USER->id, 'courseid' => $courseid],
    'timemodified DESC',
    '*',
    0,
    1
);
$latest = $latestrecords ? reset($latestrecords) : false;
if ($latest && !empty($latest->generatedquiz)) {
    $decoded = json_decode($latest->generatedquiz, true);
    if (is_array($decoded)) {
        $previewquiz = $decoded;
        $importresult = local_ai_quizgen_mock_import_to_quiz($courseid, $previewquiz);
    }
}

echo $OUTPUT->header();
if ($course) {
    echo $OUTPUT->heading(format_string($course->fullname), 4);
}
echo $OUTPUT->notification(get_string('mocknotice', 'local_ai_quizgen'), \core\output\notification::NOTIFY_INFO);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitquizgen', 'value' => 1]);
if ($courseid > 0) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
}
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('lessoncontent', 'local_ai_quizgen'), ['for' => 'id_lessoncontent', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($lessoncontent), ['name' => 'lessoncontent', 'id' => 'id_lessoncontent', 'rows' => 6, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::tag('button', get_string('generate', 'local_ai_quizgen'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo html_writer::tag('h3', get_string('previewheading', 'local_ai_quizgen'));
if (empty($previewquiz)) {
    echo html_writer::tag('p', get_string('noquizyet', 'local_ai_quizgen'));
} else {
    foreach ($previewquiz as $index => $item) {
        $question = $item['question'] ?? '';
        $options = $item['options'] ?? [];
        $answer = $item['answer'] ?? '';

        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h4', format_string(($index + 1) . '. ' . $question), ['class' => 'h6']);
        $list = [];
        foreach ($options as $option) {
            $list[] = html_writer::tag('li', format_string($option));
        }
        echo html_writer::tag('ul', implode('', $list));
        echo html_writer::tag('p', 'Answer key: ' . format_string($answer), ['class' => 'mb-0']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    if (is_array($importresult)) {
        echo html_writer::tag('h4', get_string('importresult', 'local_ai_quizgen'), ['class' => 'h6']);
        echo html_writer::tag('p', get_string('importstubsuccess', 'local_ai_quizgen'));
    }
}

echo $OUTPUT->footer();
