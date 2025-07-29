<?php
/**
 * Internationalization class for Kalameh Bot plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class KalamehBotI18n {
    
    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'load_admin_textdomain'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'kalameh-bot',
            false,
            dirname(plugin_basename(KALAMEH_BOT_PLUGIN_PATH . 'kalameh-bot.php')) . '/languages'
        );
    }
    
    /**
     * Load admin textdomain
     */
    public function load_admin_textdomain() {
        if (is_admin()) {
            load_plugin_textdomain(
                'kalameh-bot',
                false,
                dirname(plugin_basename(KALAMEH_BOT_PLUGIN_PATH . 'kalameh-bot.php')) . '/languages'
            );
        }
    }
    
    /**
     * Get current language
     */
    public function get_current_language() {
        return get_locale();
    }
    
    /**
     * Check if current language is RTL
     */
    public function is_rtl() {
        $rtl_languages = array('fa_IR', 'ar', 'he_IL', 'ur');
        return in_array($this->get_current_language(), $rtl_languages);
    }
    
    /**
     * Get language direction
     */
    public function get_text_direction() {
        return $this->is_rtl() ? 'rtl' : 'ltr';
    }
    
    /**
     * Get available languages
     */
    public function get_available_languages() {
        return array(
            'en_US' => array(
                'name' => 'English',
                'flag' => '🇺🇸',
                'rtl' => false
            ),
            'fa_IR' => array(
                'name' => 'فارسی',
                'flag' => '🇮🇷',
                'rtl' => true
            )
        );
    }
    
    /**
     * Get language name by locale
     */
    public function get_language_name($locale) {
        $languages = $this->get_available_languages();
        return isset($languages[$locale]) ? $languages[$locale]['name'] : $locale;
    }
    
    /**
     * Get language flag by locale
     */
    public function get_language_flag($locale) {
        $languages = $this->get_available_languages();
        return isset($languages[$locale]) ? $languages[$locale]['flag'] : '🌐';
    }
} 