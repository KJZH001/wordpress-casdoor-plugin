<?php
/**
 * Plugin Name: Wordpress Casdoor Plugin
 * Plugin URI: https://github.com/KJZH001/wordpress-casdoor-plugin
 * Version: 1.0.0
 * Description: 让Wordpress通过Casdoor完成单点登录
 * Author: 晓空 & casdoor
 * Author URI: https://github.com/KJZH001/
 * License: Apache
 */

 // ABSPATH prevent public user to directly access your .php files through URL.
defined('ABSPATH') or die('No script kiddies please!');

if (!defined('CASDOOR_PLUGIN_DIR')) {
    define('CASDOOR_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
}

// Require the main plugin class
require_once(CASDOOR_PLUGIN_DIR . 'Casdoor.php');

add_action('wp_loaded', 'casdoor_register_files');

function casdoor_register_files()
{
    // Register a CSS stylesheet.
    wp_register_style('casdoor_admin', plugins_url('/assets/css/admin.css', __FILE__));
    // Register a new script.
    // 目前去掉貌似没什么关系
    // wp_register_script('casdoor_admin', plugins_url('/assets/js/admin.js', __FILE__));
}

$casdoor = new Casdoor();
add_action('admin_menu', [$casdoor, 'plugin_init']);
add_action('wp_enqueue_scripts', [$casdoor, 'wp_enqueue']);
add_action('wp_logout', [$casdoor, 'logout']);
register_activation_hook(__FILE__, [$casdoor, 'setup']);
register_activation_hook(__FILE__, [$casdoor, 'upgrade']);
