<?php
/**
 * Settings class for Kalameh Bot plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class KalamehBotSettings {
    
    public function init() {
        register_setting('kalameh_bot_options', 'kalameh_bot_options');
        
        add_settings_section(
            'kalameh_bot_main',
            __('Main Settings', 'kalameh-bot'),
            array($this, 'section_callback'),
            'kalameh-bot'
        );
        
        add_settings_section(
            'kalameh_bot_social',
            __('Social Media Links', 'kalameh-bot'),
            array($this, 'social_section_callback'),
            'kalameh-bot'
        );
        
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        $fields = array(
            'bot_token' => array(
                'title' => __('Bot Token', 'kalameh-bot'),
                'callback' => 'bot_token_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'channel_id' => array(
                'title' => __('Channel ID', 'kalameh-bot'),
                'callback' => 'channel_id_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'use_google_script' => array(
                'title' => __('Use Google Apps Script', 'kalameh-bot'),
                'callback' => 'use_google_script_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'google_script_url' => array(
                'title' => __('Google Apps Script URL', 'kalameh-bot'),
                'callback' => 'google_script_url_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'enable_auto_send' => array(
                'title' => __('Auto Send', 'kalameh-bot'),
                'callback' => 'enable_auto_send_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'message_template' => array(
                'title' => __('Message Template', 'kalameh-bot'),
                'callback' => 'message_template_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'max_description_length' => array(
                'title' => __('Max Description Length', 'kalameh-bot'),
                'callback' => 'max_description_length_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'include_featured_image' => array(
                'title' => __('Include Featured Image', 'kalameh-bot'),
                'callback' => 'include_featured_image_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'include_categories' => array(
                'title' => __('Include Categories', 'kalameh-bot'),
                'callback' => 'include_categories_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'include_tags' => array(
                'title' => __('Include Tags', 'kalameh-bot'),
                'callback' => 'include_tags_callback',
                'description' => '',
                'section' => 'kalameh_bot_main'
            ),
            'convert_webp_images' => array(
                'title' => __('Convert WebP Images', 'kalameh-bot'),
                'callback' => 'convert_webp_images_callback',
                'description' => __('Convert WebP images to JPEG format for Telegram compatibility', 'kalameh-bot'),
                'section' => 'kalameh_bot_main'
            ),
            'social_media_links' => array(
                'title' => __('Social Media Links', 'kalameh-bot'),
                'callback' => 'social_media_links_callback',
                'description' => '',
                'section' => 'kalameh_bot_social'
            )
        );
        
        foreach ($fields as $field_id => $field) {
            add_settings_field(
                $field_id,
                $field['title'],
                array($this, $field['callback']),
                'kalameh-bot',
                $field['section']
            );
        }
    }
    
    public function section_callback() {
        echo '<p>' . __('Settings for automatic article sending to Telegram channel', 'kalameh-bot') . '</p>';
    }
    
    public function social_section_callback() {
        echo '<p>' . __('Add your social media links to be included in Telegram messages', 'kalameh-bot') . '</p>';
    }
    
    public function bot_token_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="text" id="bot_token" name="kalameh_bot_options[bot_token]" value="' . esc_attr($options['bot_token']) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Telegram bot token (example: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz)', 'kalameh-bot') . '</p>';
    }
    
    public function channel_id_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="text" id="channel_id" name="kalameh_bot_options[channel_id]" value="' . esc_attr($options['channel_id']) . '" class="regular-text" />';
        echo '<p class="description">' . __('Telegram channel ID (example: @mychannel or -1001234567890)', 'kalameh-bot') . '</p>';
    }
    
    public function google_script_url_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="url" id="google_script_url" name="kalameh_bot_options[google_script_url]" value="' . esc_attr($options['google_script_url']) . '" class="regular-text" />';
        echo '<p class="description">' . __('Google Apps Script URL that we will create next', 'kalameh-bot') . '</p>';
    }
    
    public function use_google_script_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="checkbox" id="use_google_script" name="kalameh_bot_options[use_google_script]" value="1" ' . checked(1, $options['use_google_script'], false) . ' />';
        echo '<label for="use_google_script">' . __('Use Google Apps Script as intermediary (recommended for better security)', 'kalameh-bot') . '</label>';
        echo '<p class="description">' . __('If disabled, messages will be sent directly to Telegram API', 'kalameh-bot') . '</p>';
    }
    
    public function message_template_callback() {
        $options = get_option('kalameh_bot_options');
        $default_template = '<b>{title}</b>

{excerpt}

{categories}
{tags}

🔗 <a href="{link}">' . __('Read Article', 'kalameh-bot') . '</a>

{social_links}';
        
        $template = isset($options['message_template']) ? $options['message_template'] : $default_template;
        
        echo '<textarea id="message_template" name="kalameh_bot_options[message_template]" rows="10" cols="50" class="large-text">' . esc_textarea($template) . '</textarea>';
        echo '<p class="description">' . __('Available shortcodes: {title}, {excerpt}, {link}, {categories}, {tags}, {author}, {date}, {social_links}', 'kalameh-bot') . '</p>';
        echo '<div class="template-preview">';
        echo '<h4>' . __('Template Preview:', 'kalameh-bot') . '</h4>';
        echo '<div id="template-preview-content" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-top: 10px;"></div>';
        echo '</div>';
    }
    
    public function enable_auto_send_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="checkbox" id="enable_auto_send" name="kalameh_bot_options[enable_auto_send]" value="1" ' . checked(1, $options['enable_auto_send'], false) . ' />';
        echo '<label for="enable_auto_send">' . __('Enable automatic sending of new articles', 'kalameh-bot') . '</label>';
    }
    
    public function max_description_length_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="number" id="max_description_length" name="kalameh_bot_options[max_description_length]" value="' . esc_attr($options['max_description_length']) . '" min="50" max="500" />';
        echo '<p class="description">' . __('Maximum characters for short description', 'kalameh-bot') . '</p>';
    }
    
    public function include_featured_image_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="checkbox" id="include_featured_image" name="kalameh_bot_options[include_featured_image]" value="1" ' . checked(1, $options['include_featured_image'], false) . ' />';
        echo '<label for="include_featured_image">' . __('Include featured image in message', 'kalameh-bot') . '</label>';
    }
    
    public function include_categories_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="checkbox" id="include_categories" name="kalameh_bot_options[include_categories]" value="1" ' . checked(1, $options['include_categories'], false) . ' />';
        echo '<label for="include_categories">' . __('Include article categories', 'kalameh-bot') . '</label>';
    }
    
    public function include_tags_callback() {
        $options = get_option('kalameh_bot_options');
        echo '<input type="checkbox" id="include_tags" name="kalameh_bot_options[include_tags]" value="1" ' . checked(1, $options['include_tags'], false) . ' />';
        echo '<label for="include_tags">' . __('Include article tags', 'kalameh-bot') . '</label>';
    }
    
    public function convert_webp_images_callback() {
        $options = get_option('kalameh_bot_options');
        $value = isset($options['convert_webp_images']) ? $options['convert_webp_images'] : '1';
        echo '<input type="checkbox" id="convert_webp_images" name="kalameh_bot_options[convert_webp_images]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="convert_webp_images">' . __('Convert WebP images to JPEG', 'kalameh-bot') . '</label>';
        echo '<p class="description">' . __('Automatically convert WebP images to JPEG format for Telegram compatibility', 'kalameh-bot') . '</p>';
    }
    
    public function social_media_links_callback() {
        $options = get_option('kalameh_bot_options');
        $social_links = isset($options['social_media_links']) ? $options['social_media_links'] : array();
        
        echo '<div id="social-media-links-container">';
        
        if (!empty($social_links)) {
            foreach ($social_links as $index => $link) {
                $this->render_social_link_field($index, $link);
            }
        } else {
            // Add one empty field by default
            $this->render_social_link_field(0, array('platform' => '', 'url' => ''));
        }
        
        echo '</div>';
        
        echo '<button type="button" id="add-social-link" class="button button-secondary">' . __('Add Social Media Link', 'kalameh-bot') . '</button>';
        echo '<p class="description">' . __('Add your social media links. These will be available as shortcodes in your message template: {social_links}', 'kalameh-bot') . '</p>';
    }
    
    private function render_social_link_field($index, $link) {
        $platform = isset($link['platform']) ? $link['platform'] : '';
        $url = isset($link['url']) ? $link['url'] : '';
        
        echo '<div class="social-link-field" data-index="' . $index . '">';
        echo '<div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">';
        
        // Platform text input
        echo '<input type="text" name="kalameh_bot_options[social_media_links][' . $index . '][platform]" value="' . esc_attr($platform) . '" placeholder="' . __('Platform name (e.g., Telegram, Instagram)', 'kalameh-bot') . '" style="width: 200px;" />';
        
        // URL input
        echo '<input type="url" name="kalameh_bot_options[social_media_links][' . $index . '][url]" value="' . esc_attr($url) . '" placeholder="' . __('Enter URL', 'kalameh-bot') . '" style="flex: 1;" />';
        
        // Remove button
        echo '<button type="button" class="button button-small remove-social-link" style="color: #dc3232;">' . __('Remove', 'kalameh-bot') . '</button>';
        
        echo '</div>';
        echo '</div>';
    }
} 