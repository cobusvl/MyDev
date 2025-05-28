<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
require_once(__DIR__ . '/lib.php'); // Include your lib.php file where helper functions are defined.

/**
 * Quiz access rule that redirects users on pass.
 *
 * @package quizaccess_redirectonpass
 * @copyright 2024 Your Name
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_redirectonpass extends quiz_access_rule_base {

    public static function is_enabled($quizsettings) {
        error_log('RedirectOnPass: is_enabled called');
        return true;
    }
    
    public function __construct($quizobj, $timenow, $canignoretimelimits) {
        parent::__construct($quizobj, $timenow, $canignoretimelimits);
        error_log('RedirectOnPass: Rule constructed for quizid '.$quizobj->get_quizid());
    }

    /**
     * Check if this rule prevents the user from starting a new attempt.
     *
     * @param int $numprevattempts Number of previous attempts.
     * @param stdClass $lastattempt The last attempt object, or null.
     * @return bool True if a new attempt is prevented, false otherwise.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        // This rule does not prevent new attempts.
        return false;
    }

    /**
     * Check if this rule prevents the user from accessing the quiz at all.
     * This method is called before a user attempts the quiz.
     *
     * @return \quiz_rule_result
     */
    public function prevent_access() {
        // This rule does not prevent initial access.
        // The actual redirect logic for passing is handled by the event handler.
        return false;
    }

    /**
     * Check if this rule prevents the user from accessing the quiz at all.
     * This method is called before a user attempts the quiz.
     *
     * @return \quiz_rule_result
     */
    public function check_access() {
        global $DB, $USER, $CFG;

//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access method called by Moodle.');

        $result = new \quiz_rule_result();

        // Load the settings for this specific quiz.
        $settings = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $this->quiz->id]);

        // If settings are not enabled or target is not set, allow access to the quiz.
        if (!$settings || empty($settings->enabled) || empty($settings->targetcmid)) {
//            error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access - Settings not enabled or incomplete. Allowing access to quiz.');
            return $result->set_allowed();
        }

        // If the user has passed the quiz and we are to redirect.
        // This part is mainly for the initial access check. The event handler handles post-submission redirect.
        require_once($CFG->libdir . '/gradelib.php');
        $gradeinfo = grade_get_grades($this->quiz->course, 'mod', 'quiz', $this->quiz->id, $USER->id);
        $finalgrade = $gradeinfo->items[0]->grades[$USER->id]->finalgrade ?? null;

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $this->quiz->id
        ]);

        if ($finalgrade !== null && $gradeitem && $gradeitem->gradepass !== null && $gradeitem->gradepass > 0 && $finalgrade >= $gradeitem->gradepass) {
//            error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access - User has already passed this quiz. Attempting to redirect to target activity.');
            $targetcm = \get_coursemodule_from_id(null, $settings->targetcmid);
            if ($targetcm) {
                $url = new \moodle_url('/mod/' . $targetcm->modname . '/view.php', ['id' => $settings->targetcmid]);
                $result->set_redirect_url($url);
//                error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access - Redirect URL set to ' . $url->out());
            } else {
//                error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access - Target course module not found (ID: ' . $settings->targetcmid . '). Allowing access to quiz.');
                $result->set_allowed();
            }
        } else {
//            error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: check_access - User has not passed the quiz (or no pass grade set). Allowing access to quiz.');
            $result->set_allowed();
        }

        return $result;
    }

    /**
     * Define the form elements for this rule in the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform The quiz settings form object.
     * @param MoodleQuickForm $mform The MoodleQuickForm instance.
     */
    public static function add_settings_form_fields(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::add_settings_form_fields method called.');
        // Call the helper function from lib.php to add the form fields.
        \quizaccess_redirectonpass_add_settings_form_fields($quizform, $mform);
    }
public function setup_attempt_page($page) {
    global $USER, $DB, $CFG;

    $targetcmid = get_user_preferences('quizaccess_redirectonpass_redirect', null, $USER->id);
    if ($targetcmid) {
        // Check grade
        $settings = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $this->quiz->id]);
        require_once($CFG->libdir . '/gradelib.php');
        $gradeinfo = grade_get_grades($this->quiz->course, 'mod', 'quiz', $this->quiz->id, $USER->id);
        $finalgrade = $gradeinfo->items[0]->grades[$USER->id]->finalgrade ?? null;
        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $this->quiz->id
        ]);
        
        if ($finalgrade !== null && $gradeitem && $gradeitem->gradepass !== null && $gradeitem->gradepass > 0 && $finalgrade >= $gradeitem->gradepass) {
            unset_user_preference('quizaccess_redirectonpass_redirect', $USER->id);
            $targetcm = \get_coursemodule_from_id(null, $targetcmid);
            if ($targetcm) {
                $url = new \moodle_url('/mod/' . $targetcm->modname . '/view.php', ['id' => $targetcmid]);
                $page->requires->js_init_code('window.setTimeout(function(){window.location="' . $url->out() . '";}, 2000);');
            }
        }
    }
}
public function setup_review_page($attempt, $page) {
    global $DB, $PAGE;

    $settings = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $this->quiz->id]);
    if (!$settings || empty($settings->enabled) || empty($settings->targetcmid)) {
        return;
    }

    $targetcm = \get_coursemodule_from_id(null, $settings->targetcmid);
	error_log('RedirectOnPass RULE: setup_attempt_page() called. targetcmid=' . $targetcmid . ' finalgrade=' . var_export($finalgrade, true) . ' gradepass=' . ($gradeitem->gradepass ?? 'null'));
    if ($targetcm) {
        // Only trigger if user has passed
        require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
        global $USER;
        $gradeinfo = grade_get_grades($this->quiz->course, 'mod', 'quiz', $this->quiz->id, $USER->id);
        $finalgrade = $gradeinfo->items[0]->grades[$USER->id]->finalgrade ?? null;
        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $this->quiz->id
        ]);
        if ($finalgrade !== null && $gradeitem && $gradeitem->gradepass !== null
            && $gradeitem->gradepass > 0 && $finalgrade >= $gradeitem->gradepass) {

            // Call your AMD module with modname and cmid
            $PAGE->requires->js_call_amd('quizaccess_redirectonpass/redirect', 'init', [$targetcm->modname, $settings->targetcmid]);
        }
    }
}
/**
     * Load settings for this rule from the database.
     * Moodle calls this to populate the form fields when editing a quiz.
     *
     * @param object $quiz The quiz object. (Using 'object' for compatibility)
     * @return \stdClass An object containing the loaded settings.
     */
    public static function load_settings($quiz) {
        global $DB;
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::load_settings called for Quiz ID: ' . $quiz->id);
        $settings_obj = new stdClass();
        $record = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $quiz->id]);
//	error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::load_settings - Raw record from DB: ' . print_r($record, true));
        if ($record) {
            $settings_obj->redirectonpass_enabled = (int)$record->enabled;
            $settings_obj->redirectonpass_target = (int)$record->targetcmid;
//	error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::load_settings - Mapped settings_obj: ' . print_r($settings_obj, true));
        } else {
            // Set defaults if no record found, so the form can display them.
            $settings_obj->redirectonpass_enabled = 0;
            $settings_obj->redirectonpass_target = 0;
//            error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::load_settings - No existing settings found for Quiz ID: ' . $quiz->id . '. Using defaults.');
        }
	        // --- NEW DEBUGGING LINE ---
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::load_settings - FINAL settings_obj before return: ' . print_r($settings_obj, true));
        // --- END NEW DEBUGGING LINE ---
        return $settings_obj;
    }

    /**
     * Save settings for this rule to the database.
     * Moodle calls this when the quiz settings form is submitted.
     *
     * @param object $quiz The quiz object. (Using 'object' for compatibility)
     */
    public static function save_settings($quiz) {
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::save_settings called. Quiz ID: ' . $quiz->id);
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::save_settings - Received $quiz object: ' . print_r($quiz, true));
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::save_settings - Raw $_POST data (before calling helper): ' . print_r($_POST, true));

        // Call the helper function from lib.php to save the settings.
        // We pass $_POST directly as the form data array.
        \quizaccess_redirectonpass_save_quiz_settings($quiz, $_POST);
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: rule.php::save_settings finished calling helper.');
    }

    /**
     * Delete settings for this rule when a quiz is deleted.
     *
     * @param object $quiz The quiz object being deleted. (Using 'object' for compatibility)
     */
    public static function delete_settings($quiz) {
        global $DB;
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: delete_settings method in rule.php called for Quiz ID: ' . $quiz->id);
        $DB->delete_records('quizaccess_redirectonpass', ['quizid' => $quiz->id]);
//        error_log(date('Y-m-d H:i:s') . ' - Redirect on Pass DEBUG: Deleted settings for Quiz ID: ' . $quiz->id);
    }

    /**
     * Returns the unique name of this access rule.
     *
     * @return string
     */
    public function get_name() {
        return 'redirectonpass';
    }

    /**
     * Returns the human-readable display name for this access rule.
     *
     * @return string
     */
    public function get_display_name() {
        return get_string('pluginname', 'quizaccess_redirectonpass');
    }

    /**
     * Is this access rule supported on this Moodle version?
     *
     * @return bool True if supported, false otherwise.
     */
    public static function is_supported() {
        return true;
    }

    /**
     * Is this access rule applicable to a given quiz?
     *
     * @param \quiz $quiz
     * @return bool
     */
    public function is_applicable(\quiz $quiz) {
        return true; // This rule is generally applicable to all quizzes.
    }

    /**
     * Get SQL for displaying rule settings in reports.
     * Not strictly necessary for basic functionality but good practice.
     *
     * @param int $quizid
     * @return array With two elements: [string $fields, string $joins, array $params]
     */
    public static function get_settings_sql($quizid) {
        return array(
            'qap.enabled AS redirectonpass_enabled, qap.targetcmid AS redirectonpass_target',
            'LEFT JOIN {quizaccess_redirectonpass} qap ON qap.quizid = quiz.id',
            array()
        );
    }
    /**
     * Get any extra settings not loaded by SQL.
     *
     * @param int $quizid
     * @return array Associative array of setting_name => value.
     */
    public static function get_extra_settings($quizid) {
        return array();
    }

/**
 * Outputs a redirect message and JS if the user has just passed and the flag is set.
 * This is shown on the quiz summary/review page after attempt submission.
 * @param quiz_attempt $attempt
 * @param mod_quiz_display_options $displayoptions
 * @return string HTML to output (for redirect or message)
 */
public function end_of_attempt($attempt, $displayoptions) {
    return '<script>alert("REDIRECT");window.location="https://www.google.com";</script>';
}
    /**
     * Outputs a redirect message and JS if the user has just passed and the flag is set.
     * This is shown on the quiz summary/review page after attempt submission.
     * @return string HTML to output (for redirect or message)
     */
public function description() {
    global $DB, $USER, $CFG;
    $targetcmid = get_user_preferences('quizaccess_redirectonpass_redirect', null, $USER->id);
    if ($targetcmid) {
        $settings = $DB->get_record('quizaccess_redirectonpass', ['quizid' => $this->quiz->id]);
        require_once($CFG->libdir . '/gradelib.php');
        $gradeinfo = grade_get_grades($this->quiz->course, 'mod', 'quiz', $this->quiz->id, $USER->id);
        $finalgrade = $gradeinfo->items[0]->grades[$USER->id]->finalgrade ?? null;
        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $this->quiz->id
        ]);
        error_log('RedirectOnPass RULE: description() called. targetcmid=' . $targetcmid . ' finalgrade=' . var_export($finalgrade, true) . ' gradepass=' . ($gradeitem->gradepass ?? 'null'));
        if ($finalgrade !== null && $gradeitem && $gradeitem->gradepass !== null && $gradeitem->gradepass > 0 && $finalgrade >= $gradeitem->gradepass) {
            unset_user_preference('quizaccess_redirectonpass_redirect', $USER->id);
            $targetcm = \get_coursemodule_from_id(null, $targetcmid);
            if ($targetcm) {
                $url = new \moodle_url('/mod/' . $targetcm->modname . '/view.php', ['id' => $targetcmid]);
                $out = html_writer::div('Redirecting...');
                $out .= html_writer::script('window.setTimeout(function(){window.location="' . $url->out() . '";}, 2000);');
                return $out;
            }
        }
    }
    return '';
}
}
