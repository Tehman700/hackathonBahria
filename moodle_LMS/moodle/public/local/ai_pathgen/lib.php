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
 * Plugin callbacks for local_ai_pathgen.
 *
 * @package    local_ai_pathgen
 * @copyright  2026 Atomcamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add the plugin link to user settings navigation.
 *
 * @param navigation_node $navigation
 * @param stdClass $user
 * @param context_user $usercontext
 * @param stdClass $course
 * @param context_course $coursecontext
 * @return void
 */
function local_ai_pathgen_extend_navigation_user_settings(
    navigation_node $navigation,
    stdClass $user,
    $usercontext,
    stdClass $course,
    $coursecontext
): void {
    global $USER;

    if (!isloggedin() || isguestuser() || (int)$USER->id !== (int)$user->id) {
        return;
    }

    $url = new moodle_url('/local/ai_pathgen/index.php');
    $navigation->add(
        get_string('navlabel', 'local_ai_pathgen'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_ai_pathgen'
    );
}

/**
 * Add onboarding survey fields to signup.
 *
 * @param mform $mform
 * @return void
 */
function local_ai_pathgen_extend_signup_form($mform): void {
    $mform->addElement('header', 'local_ai_pathgen_onboarding_header', get_string('onboardingsurvey', 'local_ai_pathgen'));

    $mform->addElement('textarea', 'local_ai_pathgen_goal', get_string('onboardinggoal', 'local_ai_pathgen'), [
        'rows' => 3,
    ]);
    $mform->setType('local_ai_pathgen_goal', PARAM_TEXT);
    $mform->addRule('local_ai_pathgen_goal', null, 'required', null, 'client');

    $mform->addElement('textarea', 'local_ai_pathgen_interests', get_string('onboardinginterests', 'local_ai_pathgen'), [
        'rows' => 3,
    ]);
    $mform->setType('local_ai_pathgen_interests', PARAM_TEXT);
    $mform->addRule('local_ai_pathgen_interests', null, 'required', null, 'client');

    $mform->addElement('textarea', 'local_ai_pathgen_skills', get_string('onboardingskills', 'local_ai_pathgen'), [
        'rows' => 3,
    ]);
    $mform->setType('local_ai_pathgen_skills', PARAM_TEXT);
    $mform->addRule('local_ai_pathgen_skills', null, 'required', null, 'client');
}

/**
 * Validate onboarding signup survey.
 *
 * @param stdClass $data
 * @return array
 */
function local_ai_pathgen_validate_extend_signup_form(stdClass $data): array {
    $errors = [];
    if (trim((string)($data->local_ai_pathgen_goal ?? '')) === '') {
        $errors['local_ai_pathgen_goal'] = get_string('required');
    }
    if (trim((string)($data->local_ai_pathgen_interests ?? '')) === '') {
        $errors['local_ai_pathgen_interests'] = get_string('required');
    }
    if (trim((string)($data->local_ai_pathgen_skills ?? '')) === '') {
        $errors['local_ai_pathgen_skills'] = get_string('required');
    }
    return $errors;
}

/**
 * Queue onboarding answers from signup for post-create personalization.
 *
 * @param stdClass $data
 * @return void
 */
function local_ai_pathgen_post_signup_requests(stdClass $data): void {
    global $SESSION;

    $goal = trim((string)($data->local_ai_pathgen_goal ?? ''));
    $interests = trim((string)($data->local_ai_pathgen_interests ?? ''));
    $skills = trim((string)($data->local_ai_pathgen_skills ?? ''));
    if ($goal === '' && $interests === '' && $skills === '') {
        return;
    }

    if (!isset($SESSION->local_ai_pathgen_onboarding)) {
        $SESSION->local_ai_pathgen_onboarding = [];
    }

    $payload = [
        'goal' => $goal,
        'interests' => $interests,
        'skills' => $skills,
    ];

    if (!empty($data->email)) {
        $SESSION->local_ai_pathgen_onboarding['email:' . core_text::strtolower($data->email)] = $payload;
    }

    if (!empty($data->username)) {
        $SESSION->local_ai_pathgen_onboarding['username:' . core_text::strtolower($data->username)] = $payload;
    }

    $data->interests = $interests;
}

/**
 * Persist onboarding answers after user account creation and seed first AI path.
 *
 * @param \core\event\user_created $event
 * @return void
 */
function local_ai_pathgen_observer_user_created(\core\event\user_created $event): void {
    global $DB, $SESSION;

    if (empty($SESSION->local_ai_pathgen_onboarding) || !is_array($SESSION->local_ai_pathgen_onboarding)) {
        return;
    }

    $user = $DB->get_record('user', ['id' => $event->objectid], 'id,email,username', IGNORE_MISSING);
    if (!$user) {
        return;
    }

    $keys = [
        'email:' . core_text::strtolower((string)$user->email),
        'username:' . core_text::strtolower((string)$user->username),
    ];

    $survey = null;
    foreach ($keys as $key) {
        if (!empty($SESSION->local_ai_pathgen_onboarding[$key])) {
            $survey = $SESSION->local_ai_pathgen_onboarding[$key];
            break;
        }
    }

    if (!$survey) {
        return;
    }

    foreach ($keys as $key) {
        unset($SESSION->local_ai_pathgen_onboarding[$key]);
    }

    require_once(__DIR__ . '/locallib.php');

    $goal = (string)($survey['goal'] ?? '');
    $interests = (string)($survey['interests'] ?? '');
    $skills = (string)($survey['skills'] ?? '');

    $pathitems = local_ai_pathgen_generate_path_mock($goal, $skills, $interests);
    local_ai_pathgen_save_generated_path((int)$user->id, $goal, $skills, $interests, $pathitems);
    $DB->set_field('user', 'interests', $interests, ['id' => $user->id]);
}
