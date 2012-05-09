<?php

/**
 * Plugin Name: Simple Ads
 * Plugin URI: https://github.com/lavoiesl/wp-simple-ads
 * Description: Manage and display custom ads manually
 * Author: Sébastien Lavoie
 * Version: 1.0
 * Author URI: http://sebastien.lavoie.sl/
 */

require __DIR__ . '/classes/plugin.php';
require __DIR__ . '/classes/custom_post.php';
require __DIR__ . '/classes/ad.php';
require __DIR__ . '/classes/custom_term.php';
require __DIR__ . '/classes/format.php';
require __DIR__ . '/classes/location.php';

add_action('init', array('SimpleAds\Plugin', 'plugin_init'));
