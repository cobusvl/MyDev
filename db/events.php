<?php
defined('MOODLE_INTERNAL') || die();

$handlers = array(
    'core_quiz_access_rule_after_access_rule' => array(
        'handlerfile'      => '/mod/quiz/accessrule/redirectonpass/lib.php',
        'handlerfunction'  => 'quizaccess_redirectonpass_handle_after_access_rule',
        'schedule'         => 'instant',
        'internal'         => true,
    ),
);
