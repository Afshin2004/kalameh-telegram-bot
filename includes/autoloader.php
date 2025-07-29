<?php
/**
 * Autoloader for Kalameh Bot plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    // Only load classes that start with KalamehBot
    if (strpos($class, 'KalamehBot') !== 0) {
        return;
    }

    // Convert class name to file path
    $file = str_replace('_', '-', strtolower($class));
    $file = 'class-' . $file . '.php';
    
    $file_path = KALAMEH_BOT_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . $file;

    // Debug: Log attempted file loads to help troubleshoot

    if (file_exists($file_path)) {
        require_once $file_path;
    } 
}); 