<?php

define('THEME_TEXTDOMAIN', 'vashfindir-theme');
$theme = wp_get_theme();
$theme_version = $theme ? $theme->get('Version') : '1.0.0-dev';
define('THEME_VERSION', $theme_version ?: '1.0.0-dev');

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/helpers.php';
