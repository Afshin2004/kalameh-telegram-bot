/**
 * JavaScript for Kalameh Bot admin panel
 */

jQuery(document).ready(function ($) {
  // Toggle Google Script URL field visibility
  function toggleGoogleScriptField() {
    var useGoogleScript = $("#use_google_script").is(":checked");
    var googleScriptRow = $("#google_script_url").closest("tr");

    if (useGoogleScript) {
      googleScriptRow.show();
    } else {
      googleScriptRow.hide();
    }
  }

  // Initialize field visibility
  toggleGoogleScriptField();

  // Handle checkbox change
  $("#use_google_script").change(function () {
    toggleGoogleScriptField();
  });

  // Test connection
  $("#test-connection").click(function () {
    var button = $(this);
    var resultDiv = $("#test-result");

    // Check if basic settings are complete
    var botToken = $("#bot_token").val();
    var channelId = $("#channel_id").val();
    var useGoogleScript = $("#use_google_script").is(":checked");
    var scriptUrl = $("#google_script_url").val();

    if (!botToken || !channelId) {
      resultDiv.html(
        '<div class="notice notice-error"><p>❌ ' +
          kalameh_bot_i18n.complete_settings +
          "</p></div>"
      );
      return;
    }

    // Check Google Script URL if using that method
    if (useGoogleScript && !scriptUrl) {
      resultDiv.html(
        '<div class="notice notice-error"><p>❌ ' +
          kalameh_bot_i18n.enter_script_url +
          "</p></div>"
      );
      return;
    }

    // Validate Google Script URL format if provided
    if (useGoogleScript && scriptUrl) {
      if (!isValidUrl(scriptUrl)) {
        resultDiv.html(
          '<div class="notice notice-error"><p>❌ ' +
            kalameh_bot_i18n.enter_valid_url +
            "</p></div>"
        );
        return;
      }
    }

    // Change button appearance
    button
      .prop("disabled", true)
      .addClass("loading")
      .text("🔄 " + kalameh_bot_i18n.testing);
    resultDiv.html("");

    // Show testing message
    resultDiv.html(
      '<div class="notice notice-info"><p>🔄 ' +
        (useGoogleScript ? "Testing Google Apps Script connection..." : "Testing direct Telegram connection...") +
        "</p></div>"
    );

    // Send test request
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "test_telegram_connection",
        nonce: kalameh_bot_i18n.nonce
      },
      success: function (response) {
        if (response.success) {
          resultDiv.html(
            '<div class="notice notice-success"><p>✅ ' +
              response.data +
              "</p></div>"
          );
        } else {
          resultDiv.html(
            '<div class="notice notice-error"><p>❌ ' +
              response.data +
              "</p></div>"
          );
        }
      },
      error: function (xhr, status, error) {
        var errorMessage = "Server error occurred.";
        
        // Try to get more specific error information
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseText) {
          try {
            var errorResponse = JSON.parse(xhr.responseText);
            if (errorResponse.data) {
              errorMessage = errorResponse.data;
            }
          } catch (e) {
            errorMessage = "Connection failed. Please check your settings.";
          }
        }
        
        resultDiv.html(
          '<div class="notice notice-error"><p>❌ ' +
            errorMessage +
            "</p></div>"
        );
      },
      complete: function () {
        button
          .prop("disabled", false)
          .removeClass("loading")
          .text("🔍 " + kalameh_bot_i18n.test_connection);
      },
    });
  });

  // Copy Google Apps Script code
  $("#copy-script").click(function () {
    var textarea = $("textarea[readonly]");
    textarea.select();
    document.execCommand("copy");

    var button = $(this);
    var originalText = button.text();
    button.text(kalameh_bot_i18n.copied).addClass("button-primary");

    setTimeout(function () {
      button.text(originalText).removeClass("button-primary");
    }, 2000);
  });

  // Show/hide help
  $(".toggle-help").click(function () {
    var helpSection = $(this).next(".help-section");
    helpSection.slideToggle();

    var button = $(this);
    if (helpSection.is(":visible")) {
      button.text(kalameh_bot_i18n.hide_help);
    } else {
      button.text(kalameh_bot_i18n.show_help);
    }
  });

  // Form validation
  $("form").submit(function () {
    var botToken = $("#bot_token").val();
    var channelId = $("#channel_id").val();
    var useGoogleScript = $("#use_google_script").is(":checked");
    var scriptUrl = $("#google_script_url").val();

    if (!botToken) {
      alert(kalameh_bot_i18n.enter_bot_token);
      $("#bot_token").focus();
      return false;
    }

    if (!channelId) {
      alert(kalameh_bot_i18n.enter_channel_id);
      $("#channel_id").focus();
      return false;
    }

    // Only validate Google Script URL if using that method
    if (useGoogleScript) {
      if (!scriptUrl) {
        alert(kalameh_bot_i18n.enter_script_url);
        $("#google_script_url").focus();
        return false;
      }

      // URL validation
      if (!isValidUrl(scriptUrl)) {
        alert(kalameh_bot_i18n.enter_valid_url);
        $("#google_script_url").focus();
        return false;
      }
    }

    return true;
  });

  // URL validation function
  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }

  // Show message preview
  $("#preview-message").click(function () {
    var title = $("#preview_title").val() || kalameh_bot_i18n.sample_title;
    var excerpt =
      $("#preview_excerpt").val() || kalameh_bot_i18n.sample_excerpt;
    var categories =
      $("#preview_categories").val() || kalameh_bot_i18n.sample_categories;
    var tags = $("#preview_tags").val() || kalameh_bot_i18n.sample_tags;

    var message = "<b>" + title + "</b>\n\n";
    message += excerpt + "\n\n";

    if (categories) {
      message +=
        "📂 " + kalameh_bot_i18n.categories_label + " " + categories + "\n";
    }

    if (tags) {
      message += "🏷️ " + kalameh_bot_i18n.tags_label + " " + tags + "\n";
    }

    message +=
      '\n🔗 <a href="https://example.com">' +
      kalameh_bot_i18n.read_article +
      "</a>";

    $("#message-preview")
      .html("<pre>" + message + "</pre>")
      .show();
  });

  // Set description length
  $("#max_description_length").on("input", function () {
    var length = $(this).val();
    $("#length-display").text(length);
  });

  // Show additional information
  $(".info-toggle").click(function () {
    var infoBox = $(this).siblings(".info-box");
    infoBox.slideToggle();
  });

  // Animation for saving settings
  $("form").submit(function () {
    var submitButton = $('input[type="submit"]');
    submitButton.prop("disabled", true).val(kalameh_bot_i18n.saving);

    setTimeout(function () {
      submitButton.prop("disabled", false).val(kalameh_bot_i18n.save_settings);
    }, 2000);
  });

  // Show success notifications
  if (window.location.search.includes("settings-updated=true")) {
    $(
      '<div class="notice notice-success"><p>' +
        kalameh_bot_i18n.settings_saved +
        "</p></div>"
    )
      .insertAfter(".kalameh-bot-admin h1")
      .delay(3000)
      .fadeOut();
  }

  // Quick help guide
  $(".quick-help").click(function () {
    var helpContent = `
            <div class="info-box">
                <h3>${kalameh_bot_i18n.quick_guide}</h3>
                <p><strong>${kalameh_bot_i18n.create_bot}</strong> ${kalameh_bot_i18n.create_bot_desc}</p>
                <p><strong>${kalameh_bot_i18n.setup_channel}</strong> ${kalameh_bot_i18n.setup_channel_desc}</p>
                <p><strong>${kalameh_bot_i18n.google_script}</strong> ${kalameh_bot_i18n.google_script_desc}</p>
                <p><strong>${kalameh_bot_i18n.test_settings}</strong> ${kalameh_bot_i18n.test_settings_desc}</p>
            </div>
        `;

    if ("#quick-help-content") {
      $("#quick-help-content").remove();
    } else {
      $(".kalameh-bot-admin h1").after(helpContent);
    }
  });

  // Social Media Links Management
  var socialLinkIndex = 0;

  // Initialize social link index
  $(".social-link-field").each(function () {
    var currentIndex = parseInt($(this).data("index"));
    if (currentIndex >= socialLinkIndex) {
      socialLinkIndex = currentIndex + 1;
    }
  });

  // Add new social media link
  $("#add-social-link").click(function () {
    var container = $("#social-media-links-container");
    var newField = createSocialLinkField(socialLinkIndex);
    container.append(newField);
    socialLinkIndex++;
  });

  // Remove social media link
  $(document).on("click", ".remove-social-link", function () {
    var field = $(this).closest(".social-link-field");
    field.remove();

    // Reindex remaining fields
    reindexSocialLinkFields();
  });

  function createSocialLinkField(index) {
    return `
      <div class="social-link-field" data-index="${index}">
        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
          <input type="text" name="kalameh_bot_options[social_media_links][${index}][platform]" placeholder="Platform name (e.g., Telegram, Instagram)" style="width: 200px;" />
          <input type="url" name="kalameh_bot_options[social_media_links][${index}][url]" placeholder="Enter URL" style="flex: 1;" />
          <button type="button" class="button button-small remove-social-link" style="color: #dc3232;">Remove</button>
        </div>
      </div>
    `;
  }

  function reindexSocialLinkFields() {
    $(".social-link-field").each(function (newIndex) {
      var field = $(this);
      field.attr("data-index", newIndex);

      // Update select name
      var select = field.find("select");
      select.attr(
        "name",
        "kalameh_bot_options[social_media_links][" + newIndex + "][platform]"
      );

      // Update input name
      var input = field.find("input");
      input.attr(
        "name",
        "kalameh_bot_options[social_media_links][" + newIndex + "][url]"
      );
    });

    // Update socialLinkIndex
    socialLinkIndex = $(".social-link-field").length;
  }
});
