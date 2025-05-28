<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'quizaccess_redirectonpass';
$plugin->version   = 2025052802; // Increment to force reinstall/upgrade
$plugin->requires  = 2022041900; // Moodle 4.1
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '2.8.02';
$plugin->plugin_type = 'quizaccessrule'; // <--- CRITICAL ADDITION
