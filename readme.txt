=== Ingeni Eventbrite ===

Contributors: Bruce McKinnon
Tags: eventbrite
Requires at least: 5
Tested up to: 5.3
Stable tag: 2020.01

Load details of Eventbrite events for a specific organiser



== Description ==

* - Load details of Eventbrite events for a specific organiser

* - Caches details to reduce load time




== Installation ==

1. Upload the 'ingeni-eventbrite' folder to the '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' menu in WordPress.

3. Obtain a Private Token from the Developers page of your Eventbrite account. This is required to authenticate to Eventbrite.

4. Optionally, you may also save your Eventbrite Organiser ID. (This value is not used in v2020.01).

5. Call functions within the plugin from your theme functions.php



== Frequently Asked Questions ==



= How do a display a list of events? =

From your functions.php file call ingeni_eb_get_all_event_ids(); This returns an array.

The first element is the cache timeout value. (The plugin automatically handles cache refreshing).

The second element is a comma delimited list of Eventbrite IDs. Using one of those IDs, call get_single_event( event_id ).

get_single_event() returns the raw JSON Eventbrite data. Your theme then needs to extract the relevant data.





== Changelog ==

v2020.01 - Initial version
