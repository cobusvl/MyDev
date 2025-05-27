<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Add settings fields to the quiz settings form.
 *
 * @param mod_quiz_mod_form $quizform The quiz form object.
 * @param MoodleQuickForm $mform The form itself.
 */
function quizaccess_redirectonpass_add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
    $mform->addElement('advcheckbox', 'redirectonpass_enabled',
        get_string('redirectonpass_enabled', 'quizaccess_redirectonpass'));

    $course = $quizform->get_course();
    $options = quizaccess_redirectonpass_get_course_activity_options($course->id);

    $mform->addElement('select', 'redirectonpass_target',
        get_string('redirectonpass_target', 'quizaccess_redirectonpass'), $options);

    $mform->hideIf('redirectonpass_target', 'redirectonpass_enabled', 'notchecked');
}

/**
 * Save settings submitted via the quiz settings form.
 *
 * @param stdClass $quiz The quiz object.
 * @param array $formdata Submitted form data (typically $_POST).
 */
function quizaccess_redirectonpass_save_quiz_settings($quiz, array $formdata) {
    global $DB;

    $record = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $quiz->id]);

    $data = (object)[
        'quizid' => $quiz->id,
        'enabled' => isset($formdata['redirectonpass_enabled']) ? 1 : 0,
        'targetcmid' => $formdata['redirectonpass_target'] ?? 0
    ];

    if ($record) {
        $data->id = $record->id;
        $DB->update_record('quizaccess_redirectonpass', $data);
    } else {
        $DB->insert_record('quizaccess_redirectonpass', $data);
    }
}

/**
 * Generate a list of available course modules for redirection.
 *
 * @param int $courseid The ID of the course containing the quiz.
 * @return array An array of course module id => activity name pairs.
 */
function quizaccess_redirectonpass_get_course_activity_options($courseid) {
    $modinfo = get_fast_modinfo($courseid);
    $options = [];

    foreach ($modinfo->cms as $cm) {
        if (!$cm->uservisible || !$cm->has_view()) {
            continue;
        }
        $options[$cm->id] = $cm->get_formatted_name();
    }

    return $options;
}
