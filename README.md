# Kalameh Telegram Bot - Auto Post to Telegram

A powerful WordPress plugin that automatically sends new articles to Telegram channels using Google Apps Script as a bridge.

## 🌟 Features

- **Automatic Posting**: Automatically sends new WordPress articles to Telegram channels
- **Dual Sending Methods**: Support for both direct Telegram API and Google Apps Script
- **Image Support**: Handles featured images with WebP to JPEG conversion
- **Customizable Messages**: Flexible message templates with shortcodes
- **Social Media Integration**: Add social media links to your messages
- **Multi-language Support**: Persian and English support
- **Binary Image Transfer**: Sends images as files, not URLs for better compatibility
- **Error Handling**: Comprehensive error handling and logging

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- GD extension enabled (for WebP conversion)
- Google Apps Script (optional, for bridge functionality)

## 🚀 Installation

### Method 1: WordPress Admin Panel

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate Plugin"

### Method 2: Manual Installation

1. Extract the plugin files to `/wp-content/plugins/kalameh_bot/`
2. Go to WordPress Admin → Plugins
3. Find "Kalameh Telegram Bot" and click "Activate"

## ⚙️ Configuration

### Step 1: Create Telegram Bot

1. Message [@BotFather](https://t.me/botfather) on Telegram
2. Send `/newbot` command
3. Follow the instructions to create your bot
4. Copy the bot token (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Step 2: Get Channel ID

1. Add your bot to your Telegram channel as an administrator
2. Send a message to your channel
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Find your channel ID (format: `@channelname` or `-1001234567890`)

### Step 3: Configure Plugin

1. Go to WordPress Admin → Kalameh Bot Settings
2. Enter your bot token and channel ID
3. Choose sending method (Direct API or Google Apps Script)
4. Customize message template and other settings
5. Save settings

## 🔧 Google Apps Script Setup (Optional)

### Step 1: Create Google Apps Script

1. Go to [Google Apps Script](https://script.google.com)
2. Create a new project
3. Copy the code from `google-apps-script.js` file
4. Save the project

### Step 2: Deploy Script

1. Click "Deploy" → "New deployment"
2. Choose "Web app" as deployment type
3. Set "Execute as" to "Me"
4. Set "Who has access" to "Anyone"
5. Click "Deploy"
6. Copy the deployment URL

### Step 3: Configure Plugin

1. In plugin settings, enable "Use Google Apps Script"
2. Paste the deployment URL in "Google Apps Script URL" field
3. Save settings

## 📝 Message Template

The plugin supports customizable message templates with shortcodes:

### Available Shortcodes

- `{title}` - Article title
- `{excerpt}` - Article excerpt (auto-generated if empty)
- `{link}` - Article URL
- `{categories}` - Article categories
- `{tags}` - Article tags
- `{social_links}` - Social media links

### Default Template

```
<b>{title}</b>

{excerpt}

📖 {link}

🏷️ {categories}
🏷️ {tags}

{social_links}
```

## 🖼️ Image Handling

### Supported Formats

- JPEG, PNG, GIF (direct support)
- WebP (converted to JPEG automatically)

### Image Processing

- **Direct API**: Images sent as binary files using cURL
- **Google Apps Script**: Images converted to base64 and sent as files
- **WebP Conversion**: Automatic conversion to JPEG for Telegram compatibility

## 🔗 Social Media Links

Add social media links to your messages:

1. Go to plugin settings → Social Media Links
2. Click "Add Social Media Link"
3. Enter platform name and URL
4. Links will appear in messages using `{social_links}` shortcode

## 🌐 Multi-language Support

The plugin supports Persian and English:

- **Persian**: Right-to-left (RTL) support
- **English**: Left-to-right (LTR) support
- **Auto-detection**: Based on WordPress admin language

## 🧪 Testing

### Test Connection

1. Go to plugin settings
2. Click "Test Connection" button
3. Check the result message

### Test Methods

- **Direct API**: Tests direct connection to Telegram
- **Google Apps Script**: Tests connection via Google Apps Script

## 📁 File Structure

```
kalameh_bot/
├── kalameh-bot.php              # Main plugin file
├── README.md                    # This file
├── google-apps-script.js        # Google Apps Script code
├── assets/
│   ├── css/
│   │   └── admin-style.css      # Admin panel styles
│   └── js/
│       └── admin-script.js      # Admin panel JavaScript
├── includes/
│   ├── autoloader.php           # Class autoloader
│   ├── class-kalamehbot.php     # Main plugin class
│   ├── class-kalamehbotsettings.php    # Settings management
│   ├── class-kalamehbotadminpage.php   # Admin page rendering
│   ├── class-kalamehbottelegram.php    # Telegram API handling
│   └── class-kalamehboti18n.php        # Internationalization
└── languages/
    ├── kalameh-bot-fa_IR.po     # Persian translations
    └── kalameh-bot-fa_IR.mo     # Persian compiled translations
```

## 🔧 Technical Details

### Sending Methods

#### Direct API

- Uses WordPress cURL functions
- Sends images as binary files
- Direct communication with Telegram API
- Faster but may not work on restricted servers

#### Google Apps Script

- Uses Google Apps Script as bridge
- Converts images to base64
- Works on servers with restricted Telegram access
- Slightly slower but more reliable

### Image Processing

- **Download**: Images downloaded from WordPress
- **Convert**: WebP images converted to JPEG
- **Encode**: Images encoded to base64 for transmission
- **Send**: Images sent as files to Telegram

### Error Handling

- Comprehensive error logging
- User-friendly error messages
- Fallback mechanisms for failed operations
- Detailed debugging information

## 🐛 Troubleshooting

### Common Issues

#### "Chat not found" Error

- Ensure bot is added to channel as administrator
- Check channel ID format (should start with @ or -100)
- Verify bot token is correct

#### "Bad Request: wrong type of the web page content"

- Enable "Convert WebP Images" setting
- Check image accessibility
- Try using Google Apps Script method

#### Connection Test Fails

- Check bot token and channel ID
- Verify Google Apps Script URL (if using)
- Check server's internet connectivity
- Review error logs for details

#### Images Not Sending

- Ensure GD extension is enabled
- Check file permissions for uploads directory
- Try different image formats
- Enable image conversion settings

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 Performance

### Optimization Tips

- Use appropriate image sizes
- Enable WebP conversion only when needed
- Use Google Apps Script for restricted servers
- Monitor error logs regularly

### Resource Usage

- **Memory**: ~10-50MB per post (depending on image size)
- **Time**: 2-10 seconds per post
- **Storage**: Temporary files cleaned automatically

## 🔒 Security

### Data Protection

- Bot tokens stored securely in WordPress options
- No sensitive data logged
- Temporary files cleaned after use
- Input sanitization and validation

### Access Control

- Admin-only settings access
- AJAX nonce verification
- Capability checks for all operations

## 📞 Support

### Documentation

- This README file
- Inline code comments
- WordPress admin help sections

### Contact

- **Author**: Afshin Moradzadeh
- **LinkedIn**: [Afshin Moradzadeh](https://www.linkedin.com/in/afshinmoradzadeh)

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🔄 Changelog

### Version 1.0.0

- Initial release
- Direct Telegram API support
- Google Apps Script bridge
- WebP to JPEG conversion
- Customizable message templates
- Social media links
- Multi-language support
- Comprehensive error handling

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## ⭐ Rating

If you find this plugin useful, please consider rating it on WordPress.org or giving it a star on GitHub.

---

**Made with ❤️ for the WordPress community**
