<?php
/*
Plugin Name: Amazon Lex Chatbot
Description: Integrates Amazon Lex chatbot with WordPress (supports CustomPayload options)
Version: 1.1
Author: Salman
*/

// --- Activation: Add default settings ---
register_activation_hook(__FILE__, 'lex_chatbot_activate');
function lex_chatbot_activate() {
    add_option('lex_chatbot_bot_id', '');
    add_option('lex_chatbot_alias_id', '');
    add_option('lex_chatbot_region', 'us-east-1');
    add_option('lex_chatbot_identity_pool_id', '');
}

// --- Add Settings Page ---
add_action('admin_menu', 'lex_chatbot_menu');
function lex_chatbot_menu() {
    add_options_page(
        'Amazon Lex Chatbot Settings',
        'Lex Chatbot',
        'manage_options',
        'lex-chatbot',
        'lex_chatbot_settings_page'
    );
}

function lex_chatbot_settings_page() {
    if (isset($_POST['lex_chatbot_save'])) {
        update_option('lex_chatbot_bot_id', sanitize_text_field($_POST['lex_chatbot_bot_id']));
        update_option('lex_chatbot_alias_id', sanitize_text_field($_POST['lex_chatbot_alias_id']));
        update_option('lex_chatbot_region', sanitize_text_field($_POST['lex_chatbot_region']));
        update_option('lex_chatbot_identity_pool_id', sanitize_text_field($_POST['lex_chatbot_identity_pool_id']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $bot_id = get_option('lex_chatbot_bot_id');
    $alias_id = get_option('lex_chatbot_alias_id');
    $region = get_option('lex_chatbot_region');
    $identity = get_option('lex_chatbot_identity_pool_id');

    echo '<div class="wrap"><h1>Amazon Lex Chatbot Settings</h1>';
    echo '<form method="post"><table class="form-table">';
    echo '<tr><th>Bot ID</th><td><input type="text" name="lex_chatbot_bot_id" value="' . esc_attr($bot_id) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Alias ID</th><td><input type="text" name="lex_chatbot_alias_id" value="' . esc_attr($alias_id) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Region</th><td><input type="text" name="lex_chatbot_region" value="' . esc_attr($region) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Identity Pool ID</th><td><input type="text" name="lex_chatbot_identity_pool_id" value="' . esc_attr($identity) . '" class="regular-text"></td></tr>';
    echo '</table><p class="submit"><input type="submit" name="lex_chatbot_save" class="button-primary" value="Save Changes"></p></form></div>';
}

// --- Enqueue Scripts & Styles ---
add_action('wp_enqueue_scripts', 'enqueue_lex_chatbot_scripts');
function enqueue_lex_chatbot_scripts() {
    // AWS SDK
    wp_enqueue_script('aws-sdk', 'https://sdk.amazonaws.com/js/aws-sdk-2.1048.0.min.js', [], null, true);
    // Main chatbot logic
    wp_enqueue_script('lex-chatbot', plugin_dir_url(__FILE__) . 'js/lex-chatbot.js', ['aws-sdk'], '1.1', true);
    // Localization for use in JS
    wp_localize_script('lex-chatbot', 'lexChatbotParams', [
        'botId'         => get_option('lex_chatbot_bot_id'),
        'aliasId'       => get_option('lex_chatbot_alias_id'),
        'region'        => get_option('lex_chatbot_region'),
        'identityPoolId'=> get_option('lex_chatbot_identity_pool_id'),
    ]);
    // Custom CSS
    wp_enqueue_style('lex-chatbot-style', plugin_dir_url(__FILE__) . 'css/lex-chatbot.css');
}

// --- Add Chatbot HTML to Footer ---
add_action('wp_footer', 'add_lex_chatbot_html');
function add_lex_chatbot_html() {
    echo '
    <button id="lex-chatbot-button">Chat with us</button>
    <div id="lex-chatbot-container">
      <div id="lex-chatbot-header">
        <h3>ACE Connect</h3>
        <button id="lex-chatbot-toggle">×</button>
      </div>
      <div id="lex-chatbot-body">
        <div id="lex-chatbot-messages"></div>
        <div id="lex-chatbot-input-container">
          <input type="text" id="lex-chatbot-input" placeholder="Type your message...">
          <button id="lex-chatbot-send">Send</button>
        </div>
      </div>
    </div>
    ';
}

// --- (Optional) AJAX Handler for Secure Credentials ---
add_action('wp_ajax_get_lex_credentials', 'get_lex_credentials');
add_action('wp_ajax_nopriv_get_lex_credentials', 'get_lex_credentials');
function get_lex_credentials() {
    // In production, generate temporary credentials via Cognito or STS
    wp_send_json(['status' => 'success']);
    wp_die();
}
