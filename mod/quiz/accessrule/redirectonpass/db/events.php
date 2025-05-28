<?php
defined('MOODLE_INTERNAL') || die();

return [
    [
        'eventname'   => '\\core\\output\\event\\before_http_headers',
        'callback'    => 'quizaccess_redirectonpass\\observer::inject_meta',
        'priority'    => 9999,
        'internal'    => false,
    ],
];
