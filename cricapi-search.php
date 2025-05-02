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
    $apiKey = '23853bc0-52f1-498c-985e-2faa764e23c4';
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
        const apiUrl = "https://api.cricapi.com/v1/currentMatches?apikey=23853bc0-52f1-498c-985e-2faa764e23c4&offset=0";

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



function display_cricket_matches() {
  $api_url = 'https://api.cricapi.com/v1/currentMatches?apikey=23853bc0-52f1-498c-985e-2faa764e23c4&offset=0';
  $response = wp_remote_get($api_url);
  
  if (is_wp_error($response)) {
      return '<div class="cricket-error">Error fetching live matches data. Please try again later.</div>';
  }
  
  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);
  
  if ($data['status'] !== 'success' || empty($data['data'])) {
      return '<div class="cricket-no-matches">No live matches available at the moment.</div>';
  }
  
  ob_start(); // Start output buffering
  ?>
  <div class="cricket-matches-container">
      <h2 class="section-title">Live Cricket Matches</h2>
      <div class="matches-grid">
          <?php foreach ($data['data'] as $match): ?>
              <div class="match-card">
                  <div class="match-header">
                      <h3 class="match-title"><?php echo esc_html($match['name']); ?></h3>
                      <span class="match-status <?php echo sanitize_html_class(strtolower(str_replace(' ', '-', $match['status']))); ?>">
                          <?php echo esc_html($match['status']); ?>
                      </span>
                  </div>
                  
                  <div class="match-teams">
                      <?php if (isset($match['teams'])): ?>
                          <div class="team-vs-team">
                              <span class="team"><?php echo esc_html($match['teams'][0] ?? 'TBD'); ?></span>
                              <span class="vs">vs</span>
                              <span class="team"><?php echo esc_html($match['teams'][1] ?? 'TBD'); ?></span>
                          </div>
                      <?php endif; ?>
                  </div>
                  
                  <?php if (isset($match['score'])): ?>
                      <div class="match-scores">
                          <?php foreach ($match['score'] as $score): ?>
                              <div class="innings-score">
                                  <span class="team-name"><?php echo esc_html($score['inning'] ?? 'Inning'); ?></span>
                                  <span class="score"><?php echo esc_html($score['r'] . '/' . $score['w']); ?></span>
                                  <span class="overs">(<?php echo esc_html($score['o']); ?> ov)</span>
                              </div>
                          <?php endforeach; ?>
                      </div>
                  <?php endif; ?>
                  
                  <div class="match-footer">
                      <span class="match-venue"><?php echo esc_html($match['venue'] ?? ''); ?></span>
                      <span class="match-date"><?php echo esc_html($match['date']); ?></span>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  </div>
  
  <style>
  .cricket-matches-container {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
  }
  
  .section-title {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
      font-size: 28px;
      position: relative;
  }
  
  .section-title:after {
      content: '';
      display: block;
      width: 80px;
      height: 3px;
      background: #e10600;
      margin: 10px auto 0;
  }
  
  .matches-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
  }
  
  .match-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
  }
  
  .match-card:hover {
      transform: translateY(-5px);
  }
  
  .match-header {
      padding: 15px;
      background: #f5f5f5;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
  }
  
  .match-title {
      margin: 0;
      font-size: 16px;
      color: #222;
  }
  
  .match-status {
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 12px;
      font-weight: bold;
      text-transform: uppercase;
  }
  
  .match-status.live {
      background: #ffeb3b;
      color: #c62828;
  }
  
  .match-status.completed {
      background: #4caf50;
      color: white;
  }
  
  .match-status.scheduled {
      background: #2196f3;
      color: white;
  }
  
  .match-teams {
      padding: 15px;
      text-align: center;
  }
  
  .team-vs-team {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-size: 18px;
  }
  
  .team {
      font-weight: 600;
  }
  
  .vs {
      color: #888;
      font-size: 14px;
  }
  
  .match-scores {
      padding: 0 15px 15px;
  }
  
  .innings-score {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px dashed #eee;
  }
  
  .team-name {
      font-weight: 500;
      color: #555;
  }
  
  .score {
      font-weight: bold;
      color: #333;
  }
  
  .overs {
      color: #888;
      font-size: 12px;
  }
  
  .match-footer {
      padding: 10px 15px;
      background: #f9f9f9;
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #666;
  }
  
  .cricket-error, .cricket-no-matches {
      text-align: center;
      padding: 20px;
      background: #ffebee;
      color: #c62828;
      border-radius: 4px;
      margin: 20px 0;
  }
  
  @media (max-width: 768px) {
      .matches-grid {
          grid-template-columns: 1fr;
      }
  }
  </style>
  <?php
  return ob_get_clean(); // Return the buffered output
}

add_shortcode('cricket_matches', 'display_cricket_matches');
