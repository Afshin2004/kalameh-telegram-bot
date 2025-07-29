/**
 * Google Apps Script - Telegram Bridge
 *
 * This script acts as a bridge between servers that cannot directly access Telegram
 * and the Telegram Bot API. It receives binary image data and sends it as files
 * to Telegram channels.
 *
 * Features:
 * - Receives binary image data from PHP
 * - Converts base64 to binary file
 * - Sends files to Telegram (not URLs)
 * - Handles both image and text messages
 * - Comprehensive error handling and logging
 */

function doPost(e) {
  try {
    // Parse incoming data
    var data = JSON.parse(e.postData.contents);

    // Validate required data
    if (!data.bot_token || !data.channel_id || !data.message) {
      return createErrorResponse(
        "Missing required data: bot_token, channel_id, or message"
      );
    }

    // Extract data
    var botToken = data.bot_token;
    var channelId = data.channel_id;
    var message = data.message;
    var imageData = data.image_data; // Binary image data from PHP

    // Process image data
    var imageBlob = null;
    if (imageData && imageData.data && imageData.mime_type) {
      try {
        // Decode base64 to binary
        var binaryData = Utilities.base64Decode(imageData.data);

        // Create blob from binary data
        imageBlob = Utilities.newBlob(
          binaryData,
          imageData.mime_type,
          "image.jpg"
        );
      } catch (blobError) {
        // Continue without image
      }
    }

    // Determine Telegram API endpoint
    var telegramUrl;
    var payload;

    if (imageBlob) {
      // Send photo with caption
      telegramUrl = "https://api.telegram.org/bot" + botToken + "/sendPhoto";
      payload = {
        chat_id: channelId,
        photo: imageBlob,
        caption: message,
        parse_mode: "HTML",
      };
    } else {
      // Send text message only
      telegramUrl = "https://api.telegram.org/bot" + botToken + "/sendMessage";
      payload = {
        chat_id: channelId,
        text: message,
        parse_mode: "HTML",
      };
    }

    // Configure request options
    var options = {
      method: "post",
      payload: payload,
      muteHttpExceptions: true,
      validateHttpsCertificates: true,
      followRedirects: true,
      headers: {
        "User-Agent": "KalamehBot-Bridge/1.0",
      },
    };

    // Send request to Telegram
    var response = UrlFetchApp.fetch(telegramUrl, options);
    var responseCode = response.getResponseCode();
    var responseText = response.getContentText();

    // Check HTTP response
    if (responseCode !== 200) {
      return createErrorResponse(
        "HTTP Error: " + responseCode + " - " + responseText
      );
    }

    // Parse Telegram response
    var result = JSON.parse(responseText);

    // Check Telegram API result
    if (result.ok) {
      return createSuccessResponse(
        "Message sent successfully to Telegram",
        result.result ? result.result.message_id : null
      );
    } else {
      return createErrorResponse(
        result.description || "Unknown Telegram API error",
        result.error_code || null
      );
    }
  } catch (error) {
    return createErrorResponse("Exception: " + error.toString());
  }
}

/**
 * Handle GET requests (for testing)
 */
function doGet(e) {
  var response = {
    status: "online",
    service: "Kalameh Bot Telegram Bridge",
    version: "1.0.0",
    timestamp: new Date().toISOString(),
    features: [
      "Binary image processing",
      "File upload to Telegram",
      "HTML message support",
      "Comprehensive error handling",
    ],
  };

  return ContentService.createTextOutput(
    JSON.stringify(response, null, 2)
  ).setMimeType(ContentService.MimeType.JSON);
}

/**
 * Create success response
 */
function createSuccessResponse(message, messageId) {
  var response = {
    success: true,
    message: message,
    message_id: messageId,
    timestamp: new Date().toISOString(),
  };

  return ContentService.createTextOutput(JSON.stringify(response)).setMimeType(
    ContentService.MimeType.JSON
  );
}

/**
 * Create error response
 */
function createErrorResponse(error, errorCode) {
  var response = {
    success: false,
    error: error,
    error_code: errorCode,
    timestamp: new Date().toISOString(),
  };

  return ContentService.createTextOutput(JSON.stringify(response)).setMimeType(
    ContentService.MimeType.JSON
  );
}

/**
 * Test function for development
 * This function should be called with actual bot token and channel ID
 */
function testConnection(botToken, channelId) {
  // Validate parameters
  if (!botToken || !channelId) {
    return createErrorResponse(
      "Bot token and channel ID are required for testing"
    );
  }

  var testData = {
    bot_token: botToken,
    channel_id: channelId,
    message:
      "🧪 Test message from Google Apps Script Bridge - " +
      new Date().toISOString(),
    image_data: null,
  };

  // Simulate POST request
  var mockEvent = {
    postData: {
      contents: JSON.stringify(testData),
    },
  };

  var result = doPost(mockEvent);
  return result;
}

/**
 * Usage Guide:
 *
 * 1. Go to https://script.google.com
 * 2. Create a new project
 * 3. Copy this code to Code.gs file
 * 4. Click Deploy
 * 5. Select New deployment
 * 6. Choose deployment type as Web app
 * 7. Set Execute as to Me
 * 8. Set Who has access to Anyone
 * 9. Click Deploy
 * 10. Copy the URL address
 *
 * This script acts as a bridge between your server and Telegram,
 * allowing you to send files and messages even if your server
 * cannot directly access Telegram's API.
 */
