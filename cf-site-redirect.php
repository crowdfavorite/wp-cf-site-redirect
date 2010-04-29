<?php
/* 
Plugin Name: CF Site Redirect
Description: Redirect old url requests to the WordPress equivalent
Version: 1.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com/
*/

/**
 * look for the url as a post meta field and redirect to that post if found
 */
define('LEGACY_URL_FIELD_NAME','_legacy_url');

function cf_site_redirect() {
	if ( is_404() ) {
		global $wpdb;
		$req = $_SERVER['REQUEST_URI'];
		
		$query = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '".LEGACY_URL_FIELD_NAME."' AND meta_value LIKE '%".$wpdb->escape($req)."%'";
		$query = apply_filters('cf_site_redirect_query', $query, $req);
		$result = $wpdb->get_row($query);

		if (!empty($result)) {
			wp_redirect(get_permalink($result->post_id), 301);
			exit();
		}
	}
}
add_action('template_redirect','cf_site_redirect');

?>