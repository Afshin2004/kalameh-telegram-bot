<?php
/**
 * Main plugin class for Kalameh Bot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class KalamehBot {
    
    public function __construct() {
        // Initialize internationalization
        new KalamehBotI18n();
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('publish_post', array($this, 'send_post_to_telegram'), 10, 2);
        add_action('wp_ajax_test_telegram_connection', array($this, 'test_telegram_connection'));
        register_activation_hook(KALAMEH_BOT_PLUGIN_PATH . 'kalameh-bot.php', array($this, 'activate'));
        register_deactivation_hook(KALAMEH_BOT_PLUGIN_PATH . 'kalameh-bot.php', array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('kalameh-bot', false, dirname(plugin_basename(KALAMEH_BOT_PLUGIN_PATH . 'kalameh-bot.php')) . '/languages');
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_kalameh-bot') {
            return;
        }
        
        wp_enqueue_style('kalameh-bot-admin', KALAMEH_BOT_PLUGIN_URL . 'assets/css/admin-style.css', array(), KALAMEH_BOT_VERSION);
        wp_enqueue_script('kalameh-bot-admin', KALAMEH_BOT_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), KALAMEH_BOT_VERSION, true);
        
        // Localize script for translations
        wp_localize_script('kalameh-bot-admin', 'kalameh_bot_i18n', array(
            'complete_settings' => __('Please complete all settings first.', 'kalameh-bot'),
            'testing' => __('Testing...', 'kalameh-bot'),
            'test_connection' => __('Test Connection', 'kalameh-bot'),
            'server_error' => __('Error connecting to server', 'kalameh-bot'),
            'copied' => __('Copied!', 'kalameh-bot'),
            'hide_help' => __('Hide Help', 'kalameh-bot'),
            'show_help' => __('Show Help', 'kalameh-bot'),
            'enter_bot_token' => __('Please enter Telegram bot token.', 'kalameh-bot'),
            'enter_channel_id' => __('Please enter Telegram channel ID.', 'kalameh-bot'),
            'enter_script_url' => __('Please enter Google Apps Script URL.', 'kalameh-bot'),
            'enter_valid_url' => __('Please enter a valid URL for Google Apps Script.', 'kalameh-bot'),
            'saving' => __('Saving...', 'kalameh-bot'),
            'save_settings' => __('Save Settings', 'kalameh-bot'),
            'settings_saved' => __('Settings saved successfully.', 'kalameh-bot'),
            'sample_title' => __('Sample Article Title', 'kalameh-bot'),
            'sample_excerpt' => __('This is a sample short description that will be displayed in the Telegram message.', 'kalameh-bot'),
            'sample_categories' => __('Technology, Programming', 'kalameh-bot'),
            'sample_tags' => __('WordPress, Plugin', 'kalameh-bot'),
            'read_article' => __('Read Article', 'kalameh-bot'),
            'categories_label' => __('Categories:', 'kalameh-bot'),
            'tags_label' => __('Tags:', 'kalameh-bot'),
            'quick_guide' => __('Quick Guide', 'kalameh-bot'),
            'create_bot' => __('1. Create Telegram Bot:', 'kalameh-bot'),
            'create_bot_desc' => __('Send message to @BotFather and use /newbot command.', 'kalameh-bot'),
            'setup_channel' => __('2. Setup Channel:', 'kalameh-bot'),
            'setup_channel_desc' => __('Add bot as admin to your channel.', 'kalameh-bot'),
            'google_script' => __('3. Google Apps Script:', 'kalameh-bot'),
            'google_script_desc' => __('Copy sample code to Google Apps Script.', 'kalameh-bot'),
            'test_settings' => __('4. Test:', 'kalameh-bot'),
            'test_settings_desc' => __('Use "Test Connection" button to verify settings.', 'kalameh-bot')
        ));
    }
    
    public static function activate() {
        // Set default options
        $default_options = array(
            'enable_auto_send' => '1',
            'bot_token' => '',
            'channel_id' => '',
            'use_google_script' => '0',
            'google_script_url' => '',
            'message_template' => '<b>{title}</b>

{excerpt}

📖 {link}

🏷️ {categories}
🏷️ {tags}

{social_links}',
            'social_media_links' => array(
                array('name' => 'Website', 'url' => ''),
                array('name' => 'Instagram', 'url' => ''),
                array('name' => 'Telegram', 'url' => '')
            ),
            'convert_webp_images' => '1'
        );

        add_option('kalameh_bot_options', $default_options);
    }
    
    public function deactivate() {
        // Clean up settings (optional)
        // delete_option('kalameh_bot_options');
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Kalameh Bot Settings', 'kalameh-bot'),
            __('Kalameh Bot', 'kalameh-bot'),
            'manage_options',
            'kalameh-bot',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        $settings = new KalamehBotSettings();
        $settings->init();
    }
    
    public function admin_page() {
        $admin_page = new KalamehBotAdminPage();
        $admin_page->render();
    }
    
    public function test_telegram_connection() {
        $telegram = new KalamehBotTelegram();
        $telegram->test_connection();
    }
    
    public function send_post_to_telegram($post_id, $post) {
        // Debug log
        
        $telegram = new KalamehBotTelegram();
        $telegram->send_post($post_id, $post);
    }
} 