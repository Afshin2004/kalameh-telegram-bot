<?php
/**
 * Plugin Name: Kalameh Telegram Bot - Auto Post to Telegram
 * Plugin URI: https://www.linkedin.com/in/afshinmoradzadeh
 * Description: Automatically send new articles to Telegram channel using Google Apps Script
 * Version: 1.0.0
 * Author: Afshin Moradzadeh
 * Author URI: https://www.linkedin.com/in/afshinmoradzadeh
 * License: GPL v2 or later
 * Text Domain: kalameh-bot
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KALAMEH_BOT_VERSION', '1.0.0');
define('KALAMEH_BOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KALAMEH_BOT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KALAMEH_BOT_PLUGIN_NAME', 'kalameh-bot');

// Load autoloader
require_once KALAMEH_BOT_PLUGIN_PATH . 'includes/autoloader.php';

// Initialize plugin
new KalamehBot(); 