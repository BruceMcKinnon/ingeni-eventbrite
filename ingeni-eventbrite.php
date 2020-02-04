<?php
/*
Plugin Name: Ingeni Eventbrite
Version: 2020.01
Plugin URI: http://ingeni.net
Author: Bruce McKinnon - ingeni.net
Author URI: http://ingeni.net
Description: Get Eventbrite event info
License: GPL v3

Ingeni Eventbrite
Copyright (C) 2020, Bruce McKinnon

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


//
// v2020.01 - Initial release
//



define("SAVE_EB_SETTINGS", "Save Settings...");
define("TEST_EB_SETTINGS", "Test Connection...");
define("EB_PRIVATE_TOKEN", "ingeni_eb_private_token");
define("EB_ORGANISER_ID", "ingeni_eb_organiser_id");

define("EB_CACHE_INDEX", "ingeni_eb_cached_index");
define("EB_CACHED_EVENT", "ingeni_eb_event_");

require_once('ingeni-eventbrite-api-class.php');
$ingeniEventbriteApi;


//
// Main function for calling EB, saving event data and managing cached data
//
function ingeni_eb_get_all_event_ids() {
	global $ingeniEventbriteApi;

	$current_cache = get_option(EB_CACHE_INDEX);
	$eb_events_index = "";
	if ( !empty($current_cache) ) {
		$eb_events_index = unserialize($current_cache);

		$cache_expiry = new DateTime($eb_events_index[0]);

		if ( $cache_expiry < new DateTime("now") ) {
			// Time to clear the cache
			ingeni_eb_clear_cache( $eb_events_index );
			$eb_events_index = "";
			$current_cache = "";		
		}
	}

	// If the cache is empty, go an get a live refresh
	if ( empty($current_cache) ) {
		if ( !$ingeniEventbriteApi ) {
			$token = get_option(EB_PRIVATE_TOKEN);
			$ingeniEventbriteApi = new IngeniEventbriteApi( $token );
		}

		$json = $ingeniEventbriteApi->get_eb_events(false,$errMsg);
		if ( ( !empty($json) ) && ( strlen($errMsg) == 0 ) ) {
			// It worked! Now save the list of event ID into the DB, then each individual event
			$event_count = $json['pagination']['object_count'];
			$event_ids = "";
			$idx = 0;

			if ($event_count > 0) {
				// Save each event individually to the DB
				for ($idx = 0; $idx < $event_count; $idx++) {
					$event_id = $json['events'][$idx]['id'];
					$event_ids .= $event_id . ',';
					update_option(EB_CACHED_EVENT.$event_id, serialize($json['events'][$idx]) );
				}

				// Now save the list of event IDs and the cache timeout;
				$cache_timeout = date("Y-m-d H:i:s", strtotime("+2 hours"));
				if ( substr($event_ids,strlen($event_ids)-1,1) == ',' ) {
					$event_ids = substr($event_ids,0,strlen($event_ids)-1);
				}
				$eb_events_index = array( $cache_timeout, $event_ids );
				$current_cache = serialize($eb_events_index);
				update_option(EB_CACHE_INDEX, $current_cache);

				$current_cache = get_option(EB_CACHE_INDEX);
			}
		}
	}

	$eb_events_index = "";
	$eb_event_ids = "";
	if ( !empty($current_cache) ) {
		$eb_events_index = unserialize($current_cache);

		$eb_event_ids = explode(',',$eb_events_index[1]);
	}
	return $eb_event_ids;
}



function get_single_event( $event_id ) {
	$retEvent = '';

	$json = get_option( EB_CACHED_EVENT.$event_id );
	if ( !empty($json) ) {
		$retEvent = unserialize( $json );
	}

	return $retEvent;
}






//
// Remove all cached info from the WP DB
//
function ingeni_eb_clear_cache( $cache ) {
	$events_ary = explode( $cache[1],",");

	if ( !empty($events_ary) ) {
		for ($idx = 0; $idx < count($events_ary); $idx++) {
			delete_option(EB_CACHED_EVENT.$events_ary[$idx]);
		}
	}
	delete_option(EB_CACHE_INDEX);
}


function ingeni_load_eb() {
	
	// Init auto-update from GitHub repo
	require 'plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/BruceMcKinnon/ingeni-eventbrite',
		__FILE__,
		'ingeni-eventbrite'
	);
}
add_action( 'wp_enqueue_scripts', 'ingeni_load_eb' );






//
// Admin functions
//

add_action('admin_menu', 'ingeni_eb_submenu_page');
function ingeni_eb_submenu_page() {
	add_submenu_page( 'tools.php', 'Ingeni Eventbrite', 'Ingeni Eventbrite', 'manage_options', 'ingeni_eb_options', 'ingeni_eb_options_page' );
}

function ingeni_eb_options_page() {
	global $ingeniEventbriteApi;

	if ( !$ingeniEventbriteApi ) {
		$token = get_option(EB_PRIVATE_TOKEN);
		$ingeniEventbriteApi = new IngeniEventbriteApi( $token );
	}

	if ( (isset($_POST['ingeni_eb_edit_hidden'])) && ($_POST['ingeni_eb_edit_hidden'] == 'Y') ) {
		$errMsg = '';
		
		switch ($_REQUEST['btn_ingeni_eb_submit']) {
			case TEST_EB_SETTINGS :

				$errMsg = "";
				$return_json = $ingeniEventbriteApi->get_eb_events( true, $errMsg );
				if ( ( !empty($return_json) ) && ( strlen($errMsg) == 0 ) ) {
					echo('<div class="updated"><p><strong>OK</p></div>');
				} else {
					echo('<div class="updated"><p><strong>Error: '.$errMsg.'</strong></p></div>');					
				}

			break;
				
			case SAVE_EB_SETTINGS :
				try {
					update_option(EB_PRIVATE_TOKEN, $_POST[EB_PRIVATE_TOKEN] );
					update_option(EB_ORGANISER_ID, $_POST[EB_ORGANISER_ID] );

					echo('<div class="updated"><p><strong>Settings saved...</strong></p></div>');

				} catch (Exception $e) {
					echo('<div class="updated"><p><strong>Error: '.$e->getMessage().'</strong></p></div>');		
				}

			break;
		}
	}

	echo('<div class="wrap">');
		echo('<h2>Ingeni Eventbrite</h2>');

		echo('<form action="'. str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="post" name="ingeni_ses_options_page">'); 
			echo('<input type="hidden" name="ingeni_eb_edit_hidden" value="Y">');
			
			echo('<table class="form-table" style="width:90%;max-width:400px;">');
			
			echo('<tr valign="top">');
				echo('<td>Eventbrite API Private Token</td><td><input type="text" name="'.EB_PRIVATE_TOKEN.'" value="'.get_option(EB_PRIVATE_TOKEN).'"></td>'); 
			echo('</tr>');
			echo('<tr valign="top">');
				echo('<td>Eventbrite Organizer ID</td><td><input type="password" name="'.EB_ORGANISER_ID.'" value="'.get_option(EB_ORGANISER_ID).'"></td>'); 
			echo('</tr>');

			
			echo('</tbody></table><br/>');			
			
			echo('<p class="submit"><input type="submit" name="btn_ingeni_eb_submit" id="btn_ingeni_eb_submit" class="button button-primary" value="'.SAVE_EB_SETTINGS.'">   ');
			echo('<input type="submit" name="btn_ingeni_eb_submit" id="btn_ingeni_eb_submit" class="button button-primary" value="'.TEST_EB_SETTINGS.'"></p>');
		echo('</form>');	
	echo('</div>');
}



//
// Plugin activation/deactivation hooks
//
function ingeni_settings_link($links) { 
  $settings_link = '<a href="tools.php?page=ingeni_eb_options">Settings</a>'; 
  array_push($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'ingeni_settings_link' );


//
// Plugin registration functions
//
register_activation_hook(__FILE__, 'ingeni_eb_activation');
function ingeni_eb_activation() {
	try {
		global $ingeniEventbriteApi;

		if ( !$ingeniEventbriteApi ) {

			$token = get_option(EB_PRIVATE_TOKEN);
			if (strlen($token) > 0) {
				$ingeniEventbriteApi = new IngeniEventbriteApi( $token );
			}
		}
	} catch (Exception $e) {
		fb_log("ingeni_eb_activation(): ".$e->getMessage());
	}
	flush_rewrite_rules( false );
}

register_deactivation_hook( __FILE__, 'ingeni_eb_deactivation' );
function ingeni_eb_deactivation() {
	flush_rewrite_rules( false );
}

?>