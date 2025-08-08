<?php
/**
 * Telegram management class for Kalameh Bot plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class KalamehBotTelegram {
    
    public function test_connection() {
        $options = get_option('kalameh_bot_options');
        
        if (empty($options['bot_token']) || empty($options['channel_id'])) {
            wp_send_json_error(__('Please complete bot token and channel ID settings first.', 'kalameh-bot'));
        }
        
        // Check if using Google Script
        if (!empty($options['use_google_script']) && $options['use_google_script'] == '1') {
            if (empty($options['google_script_url'])) {
                wp_send_json_error(__('Please enter Google Apps Script URL when using Google Script method.', 'kalameh-bot'));
            }
            $this->test_google_script_connection($options);
        } else {
            // For direct API testing, validate bot access first
            $validation_result = $this->validate_bot_access($options['bot_token'], $options['channel_id']);
            if (!$validation_result['success']) {
                wp_send_json_error($validation_result['error']);
            }
            $this->test_direct_api_connection($options);
        }
    }
    
    private function validate_bot_access($bot_token, $channel_id) {
        // Test bot token by getting bot info
        $bot_info_url = "https://api.telegram.org/bot" . $bot_token . "/getMe";
        $bot_response = $this->make_curl_request($bot_info_url, 'GET');
        
        if (!$bot_response['success']) {
            return array('success' => false, 'error' => __('Invalid bot token or network error.', 'kalameh-bot'));
        }
        
        $bot_result = json_decode($bot_response['body'], true);
        if (!$bot_result || !isset($bot_result['ok']) || !$bot_result['ok']) {
            return array('success' => false, 'error' => __('Invalid bot token. Please check your bot token.', 'kalameh-bot'));
        }
        
        // Test channel access by getting chat info
        $chat_info_url = "https://api.telegram.org/bot" . $bot_token . "/getChat?chat_id=" . urlencode($channel_id);
        $chat_response = $this->make_curl_request($chat_info_url, 'GET');
        
        if (!$chat_response['success']) {
            return array('success' => false, 'error' => __('Cannot access channel. Please check channel ID and bot permissions.', 'kalameh-bot'));
        }
        
        $chat_result = json_decode($chat_response['body'], true);
        if (!$chat_result || !isset($chat_result['ok']) || !$chat_result['ok']) {
            return array('success' => false, 'error' => __('Invalid channel ID or bot is not admin of this channel.', 'kalameh-bot'));
        }
        
        return array('success' => true);
    }
    
    private function test_google_script_connection($options) {
        // Prepare test data with proper format for Google Apps Script
        $test_data = array(
            'bot_token' => $options['bot_token'],
            'channel_id' => $options['channel_id'],
            'message' => '🧪 ' . __('Kalameh Bot Google Script Test', 'kalameh-bot') . ' - ' . date('Y-m-d H:i:s'),
            'image_url' => '',
            'image_data' => null
        );
        
        // Send test request to Google Apps Script
        $response = $this->make_curl_request(
            $options['google_script_url'], 
            'POST', 
            json_encode($test_data), 
            array('Content-Type: application/json')
        );
        
        // Check if request was successful
        if (!$response['success']) {
            wp_send_json_error(__('Failed to connect to Google Apps Script:', 'kalameh-bot') . ' ' . $response['error']);
        }
        
        // Parse Google Apps Script response
        $result = json_decode($response['body'], true);
        
        // Check if response is valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid response from Google Apps Script:', 'kalameh-bot') . ' ' . $response['body']);
        }
        
        // Check if Google Apps Script returned success
        if ($result && isset($result['success']) && $result['success'] === true) {
            wp_send_json_success(__('✅ Google Apps Script connection successful! Test message sent to Telegram.', 'kalameh-bot'));
        } else {
            // Get error message from Google Apps Script response
            $error_message = '';
            if (isset($result['error'])) {
                $error_message = $result['error'];
            } elseif (isset($result['description'])) {
                $error_message = $result['description'];
            } else {
                $error_message = __('Unknown error from Google Apps Script', 'kalameh-bot');
            }
            
            wp_send_json_error(__('❌ Google Apps Script error:', 'kalameh-bot') . ' ' . $error_message);
        }
    }
    
    private function test_direct_api_connection($options) {
        // Test connection by sending test message directly to Telegram API
        $test_data = array(
            'bot_token' => $options['bot_token'],
            'channel_id' => $options['channel_id'],
            'message' => '🧪 Kalameh Bot connection test - ' . date('Y-m-d H:i:s'),
            'image_url' => '',
            'image_data' => null
        );
        
        $result = $this->send_direct_to_telegram($test_data);
        
        if ($result['success']) {
            wp_send_json_success(__('Connection established successfully! Test message sent directly to Telegram.', 'kalameh-bot'));
        } else {
            wp_send_json_error(__('Message sending error:', 'kalameh-bot') . ' ' . $result['error']);
        }
    }
    
    public function send_post($post_id, $post) {
        // Check if auto send is enabled
        $options = get_option('kalameh_bot_options');
        if (empty($options['enable_auto_send']) || $options['enable_auto_send'] != '1') {
            return;
        }
        
        // Check if basic settings are complete
        if (empty($options['bot_token']) || empty($options['channel_id'])) {
            return;
        }
        
        // Check Google Script settings if using that method
        if (!empty($options['use_google_script']) && $options['use_google_script'] == '1') {
            if (empty($options['google_script_url'])) {
                return;
            }
        }
        
        // Only for new articles
        if ($post->post_status !== 'publish') {
            return;
        }

        // Check if this is the first time this post is being published
        // Get the previous post status from post revision or check if post was previously published
        $previous_status = get_post_meta($post_id, '_kalameh_bot_previous_status', true);
        $was_published_before = get_post_meta($post_id, '_kalameh_bot_published_before', true);
        
        // If post was published before, don't send again
        if ($was_published_before === 'yes') {
            return;
        }
        
        // Check if this is a status change from draft/pending to publish
        if ($previous_status && $previous_status === 'publish') {
            return;
        }
        
        // Mark this post as published for future reference
        update_post_meta($post_id, '_kalameh_bot_published_before', 'yes');
        update_post_meta($post_id, '_kalameh_bot_previous_status', 'publish');
        
        
        // Prepare message data
        $message_data = $this->prepare_message_data($post);
        
        // Send message based on selected method
        if (!empty($options['use_google_script']) && $options['use_google_script'] == '1') {
            $result = $this->send_to_google_script($message_data);
        } else {
            $result = $this->send_direct_to_telegram($message_data);
        }
    }
    
    private function prepare_message_data($post) {
        $options = get_option('kalameh_bot_options');
        
        // Get featured image
        $image_url = '';
        $image_data = null;
        
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'large');
            
            // Log the image URL for debugging
            if (!empty($image_url)) {
                
                // Check if it's a WebP image that needs conversion
                $url_lower = strtolower($image_url);
                if (strpos($url_lower, '.webp') !== false && !empty($options['convert_webp_images']) && $options['convert_webp_images'] == '1') {
                    
                    // Convert WebP to JPEG and get the file path
                    $converted_file_path = $this->convert_webp_to_jpeg_file_path($image_url, $post->ID);
                    
                    if (!empty($converted_file_path) && file_exists($converted_file_path)) {
                        
                        // Read the converted file directly and convert to binary
                        $image_data = $this->convert_file_to_binary($converted_file_path);
                        
                        if ($image_data) {
                            // Update image URL to the converted file URL for reference
                            $upload_dir = wp_upload_dir();
                            $image_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $converted_file_path);
                        } else {
                            $image_url = '';
                        }
                    } else {
                        // Fallback to original URL
                        $image_data = $this->convert_image_to_binary($image_url);
                        if ($image_data) {
                        } else {
                            $image_url = '';
                        }
                    }
                } else {
                    // For non-WebP images, convert directly to binary
                    $image_data = $this->convert_image_to_binary($image_url);
                    if ($image_data) {
                    } else {
                        $image_url = '';
                    }
                }
            }
        }
        
        // If no featured image, don't use any image
        if (empty($image_url) && empty($image_data)) {
        }
        
        // Prepare message using template
        $message = $this->format_message_with_template($post, $options['message_template']);
        
        return array(
            'bot_token' => $options['bot_token'],
            'channel_id' => $options['channel_id'],
            'message' => $message,
            'image_url' => $image_url,
            'image_data' => $image_data
        );
    }
    
    private function send_to_google_script($data) {
        $options = get_option('kalameh_bot_options');
        
        // Log the data being sent to Google Script for debugging
        
        // Log binary image data info
        if (!empty($data['image_data'])) {
        } else {
        }
        
        $response = $this->make_curl_request($options['google_script_url'], 'POST', json_encode($data), array('Content-Type: application/json'));
        
        if (!$response['success']) {
            return false;
        }
        
        $result = json_decode($response['body'], true);
        
        // Log the full Google Script response for debugging
        
        if ($result && isset($result['success']) && $result['success']) {
            return true;
        } else {
            $error = isset($result['error']) ? $result['error'] : 'Unknown error';
            return false;
        }
    }
    
    private function send_direct_to_telegram($data) {
        // Log the data being sent for debugging
        
        // Check if we have binary image data (same logic as Google Script)
        if (!empty($data['image_data']) && !empty($data['image_data']['data'])) {
            
            // Build Telegram API URL for photo
            $telegram_url = "https://api.telegram.org/bot" . $data['bot_token'] . "/sendPhoto";
            
            // Create a temporary file from binary data
            $temp_file = tempnam(sys_get_temp_dir(), 'kalameh_bot_');
            $binary_data = base64_decode($data['image_data']['data']);
            file_put_contents($temp_file, $binary_data);
            
            // Prepare payload with binary data using CURLFile
            $payload = array(
                'chat_id' => $data['channel_id'],
                'photo' => new CURLFile($temp_file, $data['image_data']['mime_type'], 'image.jpg'),
                'caption' => $data['message'],
                'parse_mode' => 'HTML'
            );
            
        } else {
            
            // Build Telegram API URL for message only (same as Google Script)
            $telegram_url = "https://api.telegram.org/bot" . $data['bot_token'] . "/sendMessage";
            
            // Prepare payload for text message only
            $payload = array(
                'chat_id' => $data['channel_id'],
                'text' => $data['message'],
                'parse_mode' => 'HTML'
            );
        }
        
        
        // Send request using cURL
        $response = $this->make_curl_request($telegram_url, 'POST', $payload);
        
        // Clean up temporary file if it was created
        if (!empty($temp_file) && file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        if (!$response['success']) {
            return array('success' => false, 'error' => $response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        // Log the full response for debugging
        
        if ($result && isset($result['ok']) && $result['ok']) {
            return array('success' => true);
        } else {
            $error = isset($result['description']) ? $result['description'] : 'Unknown error';
            $error_code = isset($result['error_code']) ? ' (Code: ' . $result['error_code'] . ')' : '';
            return array('success' => false, 'error' => $error . $error_code);
        }
    }
    
    private function format_social_links($social_links) {
        if (empty($social_links)) {
            return '';
        }
        
        $formatted_links = array();
        
        foreach ($social_links as $link) {
            if (!empty($link['platform']) && !empty($link['url'])) {
                $platform = $link['platform'];
                $url = $link['url'];
                
                // Use a generic icon for all platforms
                $icon = '🔗';
                
                // Capitalize first letter of platform name
                $platform_name = ucfirst($platform);
                
                $formatted_links[] = ' <a href="' . esc_url($url) . '">' . $platform_name . '</a>';
            }
        }
        
        if (!empty($formatted_links)) {
            return implode(' • ', $formatted_links);
        }
        
        return '';
    }
    
    private function get_compatible_image_url($url, $post_id) {
        $options = get_option('kalameh_bot_options');
        $convert_webp = isset($options['convert_webp_images']) ? $options['convert_webp_images'] : '1';
        
        // Check if URL contains problematic extensions
        $url_lower = strtolower($url);
        $problematic_extensions = array('.svg', '.ico', '.webp');
        
        foreach ($problematic_extensions as $ext) {
            if (strpos($url_lower, $ext) !== false) {
                
                // For WebP images, try to convert them to JPEG first
                if ($ext === '.webp') {
                    
                    if ($convert_webp == '1') {
                        
                        $converted_url = $this->convert_webp_to_jpeg($url, $post_id);
                        if (!empty($converted_url)) {
                            // Test if the converted image is accessible
                            if ($this->test_image_accessibility($converted_url)) {
                                return $converted_url;
                            } else {
                            }
                        } else {
                        }
                    } else {
                    }
                }
                
                // For other problematic formats, try to find alternatives
                // Try to get JPEG version from WordPress
                $jpeg_url = get_the_post_thumbnail_url($post_id, 'large');
                if (!empty($jpeg_url) && strpos(strtolower($jpeg_url), '.webp') === false) {
                    return $jpeg_url;
                }
                
                // Try medium size
                $medium_url = get_the_post_thumbnail_url($post_id, 'medium');
                if (!empty($medium_url) && strpos(strtolower($medium_url), '.webp') === false) {
                    return $medium_url;
                }
                
                // If no alternative found, return empty to use default image
                return '';
            }
        }
        
        // If no problematic extensions found, return original URL
        return $url;
    }
    
    private function convert_webp_to_jpeg($webp_url, $post_id) {
        
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return '';
        }
        
        // Check if WebP support is available
        if (!function_exists('imagecreatefromwebp')) {
            return '';
        }
        
        try {
            // Download the WebP image
            
            $response = $this->make_curl_request($webp_url, 'GET');
            if (!$response['success']) {
                return '';
            }
            
            // Create image from WebP data
            
            $webp_image = imagecreatefromstring($response['body']);
            if (!$webp_image) {
                return '';
            }
            
            // Get image dimensions
            $width = imagesx($webp_image);
            $height = imagesy($webp_image);
            
            // Create uploads directory path - use the standard uploads directory for better accessibility
            $upload_dir = wp_upload_dir();
            $kalameh_bot_dir = $upload_dir['basedir'] . '/kalameh-bot-converted';
            
            // Create directory if it doesn't exist
            if (!file_exists($kalameh_bot_dir)) {
                $mkdir_result = wp_mkdir_p($kalameh_bot_dir);
                if (!$mkdir_result) {
                    imagedestroy($webp_image);
                    return '';
                }
                
                // Create .htaccess file to ensure the directory is accessible
                $htaccess_content = "Options -Indexes\n<Files *>\n    Order Allow,Deny\n    Allow from all\n</Files>";
                $htaccess_file = $kalameh_bot_dir . '/.htaccess';
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Also try to create an index.php file to prevent directory listing
            $index_content = "<?php\n// Silence is golden.";
            $index_file = $kalameh_bot_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
            
            // Generate unique filename
            $filename = 'webp-converted-' . $post_id . '-' . time() . '.jpg';
            $file_path = $kalameh_bot_dir . '/' . $filename;
            
            // Alternative: try to save to main uploads directory if custom directory fails
            $fallback_file_path = $upload_dir['basedir'] . '/' . $filename;
            
            // Convert to JPEG
            $jpeg_quality = 85; // Good quality
            
            $success = imagejpeg($webp_image, $file_path, $jpeg_quality);
            
            // Free memory
            imagedestroy($webp_image);
            
            if (!$success) {
                return '';
            }
            
            // Verify file was created
            if (!file_exists($file_path)) {
                return '';
            }
            
            // Try fallback path if main path fails accessibility test
            $file_url = $upload_dir['baseurl'] . '/kalameh-bot-converted/' . $filename;
            $fallback_url = $upload_dir['baseurl'] . '/' . $filename;
            
            $file_size = filesize($file_path);
            
            // Get the URL for the converted image
            $file_url = $upload_dir['baseurl'] . '/kalameh-bot-converted/' . $filename;
            
            // Test if the converted image URL is accessible
            $accessibility_test = $this->test_image_accessibility($file_url);
            
            if ($accessibility_test) {
                return $file_url;
            } else {
                
                // Try to ensure the file is publicly accessible by setting proper permissions
                chmod($file_path, 0644);
                
                // Test again
                $accessibility_test_2 = $this->test_image_accessibility($file_url);
                
                if ($accessibility_test_2) {
                    return $file_url;
                } else {
                    // Try fallback: copy file to main uploads directory
                    
                    if (copy($file_path, $fallback_file_path)) {
                        chmod($fallback_file_path, 0644);
                        
                        $fallback_accessibility_test = $this->test_image_accessibility($fallback_url);
                        
                        if ($fallback_accessibility_test) {
                            return $fallback_url;
                        }
                    }
                    
                    return '';
                }
            }
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    private function test_image_accessibility($url) {
        
        // Make a HEAD request to check if the image is accessible
        $response = $this->make_curl_request($url, 'HEAD');
        
        if (!$response['success']) {
            return false;
        }
        
        $http_code = $response['http_code'];
        $content_type = $response['content_type'];
        
        if ($http_code >= 200 && $http_code < 300) {
            // Check if content type is image
            if (strpos($content_type, 'image/') === 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    private function is_valid_image_url($url) {
        // Check if URL is valid
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check for problematic image formats (excluding WebP since we can convert it)
        $url_lower = strtolower($url);
        $problematic_extensions = array('.svg', '.ico');
        foreach ($problematic_extensions as $ext) {
            if (strpos($url_lower, $ext) !== false) {
                return false;
            }
        }
        
        // For local WordPress images, trust them only if they're not problematic formats
        if (strpos($url, get_site_url()) === 0) {
            return true;
        }
        
        // Check if URL is accessible using cURL
        $response = $this->make_curl_request($url, 'HEAD');
        if (!$response['success']) {
            return false;
        }
        
        $status_code = $response['http_code'];
        $content_type = $response['content_type'];
        
        // Check if status is OK and content type is image
        $is_valid = $status_code === 200 && strpos($content_type, 'image/') === 0;
        
        if (!$is_valid) {
        }
        
        return $is_valid;
    }
    
    private function convert_image_to_binary($image_url) {
        
        try {
            // Download image using cURL
            $response = $this->make_curl_request($image_url, 'GET');
            
            if (!$response['success']) {
                return null;
            }
            
            $image_data = $response['body'];
            $content_type = $response['content_type'];
            
            // Validate that it's actually an image
            if (strpos($content_type, 'image/') !== 0) {
                return null;
            }
            
            // Convert to base64 for easy transmission
            $base64_data = base64_encode($image_data);
            $mime_type = $content_type;
            
            return array(
                'data' => $base64_data,
                'mime_type' => $mime_type,
                'size' => strlen($image_data)
            );
            
        } catch (Exception $e) {
            return null;
        }
    }

    private function convert_webp_to_jpeg_file_path($webp_url, $post_id) {
        
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return '';
        }
        
        // Check if WebP support is available
        if (!function_exists('imagecreatefromwebp')) {
            return '';
        }
        
        try {
            // Download the WebP image
            
            $response = $this->make_curl_request($webp_url, 'GET');
            if (!$response['success']) {
                return '';
            }
            
            // Create image from WebP data
            
            $webp_image = imagecreatefromstring($response['body']);
            if (!$webp_image) {
                return '';
            }
            
            // Get image dimensions
            $width = imagesx($webp_image);
            $height = imagesy($webp_image);
            
            // Create uploads directory path
            $upload_dir = wp_upload_dir();
            $kalameh_bot_dir = $upload_dir['basedir'] . '/kalameh-bot-converted';
            
            // Create directory if it doesn't exist
            if (!file_exists($kalameh_bot_dir)) {
                $mkdir_result = wp_mkdir_p($kalameh_bot_dir);
                if (!$mkdir_result) {
                    imagedestroy($webp_image);
                    return '';
                }
                
                // Create .htaccess file to ensure the directory is accessible
                $htaccess_content = "Options -Indexes\n<Files *>\n    Order Allow,Deny\n    Allow from all\n</Files>";
                $htaccess_file = $kalameh_bot_dir . '/.htaccess';
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Also try to create an index.php file to prevent directory listing
            $index_content = "<?php\n// Silence is golden.";
            $index_file = $kalameh_bot_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
            
            // Generate unique filename
            $filename = 'webp-converted-' . $post_id . '-' . time() . '.jpg';
            $file_path = $kalameh_bot_dir . '/' . $filename;
            
            // Convert to JPEG
            $jpeg_quality = 85; // Good quality
            
            $success = imagejpeg($webp_image, $file_path, $jpeg_quality);
            
            // Free memory
            imagedestroy($webp_image);
            
            if (!$success) {
                return '';
            }
            
            // Verify file was created
            if (!file_exists($file_path)) {
                return '';
            }
            
            // Set proper permissions
            chmod($file_path, 0644);
            
            $file_size = filesize($file_path);
            
            // Get the URL for the converted image
            $file_url = $upload_dir['baseurl'] . '/kalameh-bot-converted/' . $filename;
            
            return $file_path; // Return file path for binary conversion
            
        } catch (Exception $e) {
            return '';
        }
    }

    private function convert_file_to_binary($file_path) {
        
        try {
            // Read the file content
            $file_content = file_get_contents($file_path);
            
            if ($file_content === false) {
                return null;
            }
            
            $mime_type = mime_content_type($file_path);
            
            // Convert to base64 for easy transmission
            $base64_data = base64_encode($file_content);
            
            return array(
                'data' => $base64_data,
                'mime_type' => $mime_type,
                'size' => strlen($file_content)
            );
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function make_curl_request($url, $method = 'GET', $data = null, $headers = array()) {
        // Initialize cURL
        $ch = curl_init();
        
        // Set basic options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KalamehBot/1.0');
        
        // Set method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        
        // Set headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        // For HEAD requests, we need to get header size before closing
        $header_size = 0;
        if ($method === 'HEAD') {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        }
        
        curl_close($ch);
        
        // Check for errors
        if ($response === false || !empty($error)) {
            return array(
                'success' => false,
                'error' => $error ?: 'cURL request failed'
            );
        }
        
        // For HEAD requests, we need to parse headers
        if ($method === 'HEAD') {
            $headers_text = substr($response, 0, $header_size);
            
            // Extract content-type from headers
            if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers_text, $matches)) {
                $content_type = trim($matches[1]);
            }
            
            return array(
                'success' => true,
                'http_code' => $http_code,
                'content_type' => $content_type,
                'body' => ''
            );
        }
        
        return array(
            'success' => true,
            'http_code' => $http_code,
            'content_type' => $content_type,
            'body' => $response
        );
    }

    private function format_message_with_template($post, $template) {
        // Prepare data for shortcodes
        $title = $post->post_title;
        
        // Short description
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_strip_all_tags($post->post_content);
        }
        $excerpt = wp_trim_words($excerpt, 50, '...');
        
        // Article link
        $post_url = get_permalink($post->ID);
        
        // Categories
        $categories = '';
            $post_categories = get_the_category($post->ID);
            if (!empty($post_categories)) {
                $category_names = array();
                foreach ($post_categories as $category) {
                    $category_names[] = $category->name;
                }
            $categories = implode(', ', $category_names);
        }
        
        // Tags
        $tags = '';
            $post_tags = get_the_tags($post->ID);
            if (!empty($post_tags)) {
                $tag_names = array();
                foreach ($post_tags as $tag) {
                $tag_names[] = '#' . str_replace(' ', '_', $tag->name);
            }
            $tags = implode(', ', $tag_names);
        }
        
        // Social media links
        $options = get_option('kalameh_bot_options');
        $social_links = '';
        if (!empty($options['social_media_links'])) {
            $social_links = $this->format_social_links($options['social_media_links']);
        }
        
        // Replace shortcodes in template
        $message = str_replace(
            array('{title}', '{excerpt}', '{link}', '{categories}', '{tags}', '{social_links}'),
            array($title, $excerpt, $post_url, $categories, $tags, $social_links),
            $template
        );
        
        return $message;
    }
} 