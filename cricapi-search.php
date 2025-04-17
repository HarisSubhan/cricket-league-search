<?php
/**
 * Plugin Name: CricAPI Search
 * Description: Search cricket series using CricAPI with AJAX.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Enqueue JS
function cricapi_enqueue_assets() {
    wp_enqueue_style('cricapi-search-css', plugins_url('/css/style.css', __FILE__));
    wp_enqueue_script('cricapi-search-js', plugins_url('/js/cricapi-search.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('cricapi-search-js', 'cricapi_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cricapi_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'cricapi_enqueue_assets');

// Shortcode to display search form
function cricapi_search_shortcode() {
    ob_start(); ?>
    <div class="cricapi-search-container">
        <input type="text" id="cricapi-search-input" placeholder="Search a league...">
        <button id="cricapi-search-btn">Search</button>
        <div id="cricapi-search-results"></div>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('cricapi_search', 'cricapi_search_shortcode');

// AJAX handler
add_action('wp_ajax_cricapi_search', 'cricapi_handle_search');
add_action('wp_ajax_nopriv_cricapi_search', 'cricapi_handle_search');

function cricapi_handle_search() {
    check_ajax_referer('cricapi_nonce', 'nonce');

    $search = sanitize_text_field($_POST['query']);
    $apiKey = '';
    $apiUrl = "https://api.cricapi.com/v1/series?apikey={$apiKey}&offset=0&search=" . urlencode($search);

    $response = wp_remote_get($apiUrl);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API call failed']);
    }

    $body = wp_remote_retrieve_body($response);
    wp_send_json_success(json_decode($body));
}


function cricket_live_matches_shortcode() {
    ob_start();
    ?>
    <div id="cricket-matches"><p>Loading matches...</p></div>

    <style>
        #cricket-matches {
            padding: 15px;
            background: #e6f7ff;
            border-radius: 10px;
        }
        .match-card {
            border-radius: 12px;
            background: white;
            padding: 10px;
            min-width: 240px;
            margin: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        .match-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      jQuery(document).ready(function ($) {
        const apiUrl = "";

        $.getJSON(apiUrl, function (data) {
          if (!data || !data.data || data.data.length === 0) {
            $("#cricket-matches").html("<p>No matches found.</p>");
            return;
          }

          let html = '<div class="match-container">';

          data.data.forEach(match => {
            if (!match.teamInfo || match.teamInfo.length < 2) return;

            const {
              matchType = '',
              dateTimeGMT = '',
              venue = '',
              status = '',
              teamInfo = []
            } = match;

            const team1 = teamInfo[0]?.name || "Team A";
            const team2 = teamInfo[1]?.name || "Team B";
            const time = new Date(dateTimeGMT).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const isLive = status?.toLowerCase().includes("live") || status?.toLowerCase().includes("stump");

            html += `
              <div class="match-card">
                <div style="font-size: 12px; color: gray;">${matchType.toUpperCase()} • ${venue}</div>
                <div style="font-weight: bold; margin: 5px 0;">${team1}</div>
                <div>${team2}</div>
                <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">
                  ${isLive ? '<span style="color: orange;">' + status + '</span>' : time}
                </div>
                <div style="font-size: 12px; color: gray;">${status}</div>
                <div style="font-size: 12px; margin-top: 8px;">
                  <a href="#">Schedule</a> • <a href="#">Table</a> • <a href="#">Series</a>
                </div>
              </div>
            `;
          });

          html += '</div>';
          $("#cricket-matches").html(html);
        }).fail(function () {
          $("#cricket-matches").html("<p>Failed to load match data.</p>");
        });
      });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('live_cricket_matches', 'cricket_live_matches_shortcode');

