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
 * Socratic AI Tutor block.
 *
 * @package    block_ai_tutor
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_ai_tutor extends block_base {
    /**
     * Initialize title.
     */
    public function init(): void {
        $this->title = get_string('blocktitle', 'block_ai_tutor');
    }

    /**
     * This block can appear in course pages and dashboard.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return [
            'course-view' => true,
            'my' => true,
        ];
    }

    /**
     * Get block content.
     *
     * @return stdClass
     */
    public function get_content(): stdClass {
        global $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $courseid = isset($this->page->course->id) ? (int)$this->page->course->id : 0;
        if ($courseid <= SITEID) {
            $this->content->text .= html_writer::tag('p', get_string('courseonly', 'block_ai_tutor'));
            return $this->content;
        }

        $message = trim(optional_param('ai_tutor_message', '', PARAM_TEXT));
        $submitted = optional_param('ai_tutor_submit', 0, PARAM_BOOL);

        if ($submitted && confirm_sesskey() && $message !== '') {
            $response = $this->mock_chat_response($message, format_string($this->page->course->fullname));
            $record = (object) [
                'courseid' => $courseid,
                'userid' => (int)$USER->id,
                'message' => $message,
                'response' => $response,
                'timecreated' => time(),
            ];
            $DB->insert_record('block_ai_tutor_chat', $record);
        }

        $history = $DB->get_records(
            'block_ai_tutor_chat',
            ['courseid' => $courseid, 'userid' => $USER->id],
            'id DESC',
            '*',
            0,
            8
        );

        $html = html_writer::tag('p', get_string('mocknotice', 'block_ai_tutor'), ['class' => 'small text-muted']);
        $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $this->page->url->out(false)]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'ai_tutor_submit', 'value' => 1]);
        $html .= html_writer::start_div('mb-2');
        $html .= html_writer::tag(
            'label',
            get_string('inputlabel', 'block_ai_tutor'),
            ['for' => 'id_ai_tutor_message', 'class' => 'form-label']
        );
        $html .= html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'ai_tutor_message',
            'id' => 'id_ai_tutor_message',
            'class' => 'form-control',
            'value' => '',
            'maxlength' => 255,
        ]);
        $html .= html_writer::end_div();
        $html .= html_writer::tag('button', get_string('send', 'block_ai_tutor'), ['type' => 'submit', 'class' => 'btn btn-primary btn-sm']);
        $html .= html_writer::end_tag('form');

        $html .= html_writer::tag('h4', get_string('historyheading', 'block_ai_tutor'), ['class' => 'h6 mt-3']);
        if (empty($history)) {
            $html .= html_writer::tag('p', get_string('nohistory', 'block_ai_tutor'));
        } else {
            $items = [];
            foreach (array_reverse($history) as $entry) {
                $items[] = html_writer::start_tag('li', ['class' => 'mb-2']) .
                    html_writer::tag('div', html_writer::tag('strong', 'You: ') . format_string($entry->message), ['class' => 'small']) .
                    html_writer::tag('div', html_writer::tag('strong', 'Tutor: ') . format_string($entry->response), ['class' => 'small']) .
                    html_writer::end_tag('li');
            }
            $html .= html_writer::tag('ul', implode('', $items), ['class' => 'list-unstyled']);
        }

        $this->content->text = $html;
        return $this->content;
    }

    /**
     * Mock chat behavior that simulates the Flask /api/chat endpoint.
     *
     * @param string $message
     * @param string $coursename
     * @return string
     */
    private function mock_chat_response(string $message, string $coursename): string {
        $safequestion = trim($message);
        return "Let's reason through this for {$coursename}. What concept do you think is the first prerequisite for: {$safequestion}?";
    }
}
