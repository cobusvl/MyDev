<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'quizaccess_redirectonpass';
$plugin->version   = 2025052501; // Increment to force reinstall/upgrade
$plugin->requires  = 2022041900; // Moodle 4.1
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '2.5.01';
$plugin->plugin_type = 'quizaccessrule'; // <--- CRITICAL ADDITION
