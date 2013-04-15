<?php
/* 
Plugin Name: CF Site Redirect
Description: Redirect old url requests to the WordPress equivalent
Version: 1.2
Author: Crowd Favorite
Author URI: http://crowdfavorite.com/
*/

/**
 * look for the url as a post meta field and redirect to that post if found
 */
define('LEGACY_URL_FIELD_NAME','_cf_legacy_url');

function cf_site_redirect() {
	if ( is_404() ) {
		global $wpdb;
		$req = $_SERVER['REQUEST_URI'];
		
		$query = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '".LEGACY_URL_FIELD_NAME."' AND meta_value LIKE '%".$wpdb->escape($req)."%' LIMIT 1";
		$query = apply_filters('cf_site_redirect_query', $query, $req);
		$result = $wpdb->get_row($query);

		if (!empty($result)) {
			wp_redirect(get_permalink($result->post_id), 301);
			exit();
		}
	}
}
add_action('template_redirect','cf_site_redirect');

function cf_site_redirect_meta_query($args = array()) {
	global $wpdb;
	$args = array_merge(array(
		'fields' => array('post_id', 'meta_value'),
		'posts_per_page' => 20,
		'foundrows' => true,
		'orderby' => 'post_id',
		'order' => 'ASC',
		'paged' => 1,
	), $args);
	
	if (!is_array($args['fields'])) {
		$args['fields'] = array($args['fields']);
	}
	
	$query = sprintf("SELECT %s %s FROM {$wpdb->postmeta} WHERE 1=1 ", ($args['foundrows'] ? 'SQL_CALC_FOUND_ROWS' : ''), implode(', ', $args['fields']));
	
	$query .= $wpdb->prepare("AND meta_key = %s ", LEGACY_URL_FIELD_NAME);
	if (!empty($args['meta_value'])) {
		$args['meta_value'] = '%'.$args['meta_value'].'%';
		$query .= $wpdb->prepare("AND meta_value LIKE %s ", $args['meta_value']);
	}
	
	$query .= "ORDER BY {$args['orderby']} {$args['order']} ";
	
	if ($args['posts_per_page'] > -1) {
		$offset = $args['posts_per_page'] * ($args['paged'] - 1);
		$query .= "LIMIT {$offset}, {$args['posts_per_page']}";
	}
	
	$new_wp_query = new WP_Query();
	
	$res_obj = new stdClass();
	
	$res_obj->query_args = $args;
	
	$res_obj->query = $query;
	
	if (count($args['fields']) > 1) {
		$res_obj->results = $wpdb->get_results($query, ARRAY_A);
	}
	else if (count($args['fields']) == 1) {
		$res_obj->results = $wpdb->get_col($query, 0);
	}
	
	$res_obj->row_count = count($res_obj->results);
	
	if ($res_obj->row_count == $args['posts_per_page'] && $args['foundrows']) {
		// Set the total rows correctly.
		$rows = $wpdb->get_row("SELECT FOUND_ROWS() as total_rows");
		$res_obj->total_rows = $rows->total_rows;
	}
	else {
		$res_obj->total_rows = $res_obj->row_count;
	}
	
	return $res_obj;
}

function _cf_site_redirect_admin_init() {
}
add_action('admin_init', '_cf_site_redirect_admin_init');

function _cf_site_redirect_admin_menu() {
	add_management_page(
		__('Site Redirects', 'cfsr'),
		__('Site Redirects', 'cfsr'),
		'manage_options',
		'cf-site-redirect',
		'_cf_site_redirect_admin_screen'
	);
}
add_action('admin_menu', '_cf_site_redirect_admin_menu');

function _cf_site_redirect_admin_styles() {
	?>
	<style type="text/css">
		#cfsr-wrapper table {
			border-collapse: collapse;
		}
	</style>
	<?php
}
add_action('admin_print_scripts', '_cf_site_redirect_admin_styles', 100);

function _cf_site_redirect_admin_scripts() {
	global $pagenow, $plugin_page;
	if ($pagenow == 'tools.php' && $plugin_page == 'cf-site-redirect') {
	?>
	<script type="text/javascript">
		if (window.jQuery) {
			jQuery(document).ready(function($) {
				var $currentView = $("#js-current-view"),
					$wrapper = $("#cfsr-wrapper"),
					cfSiteRedirect;
					
				cfSiteRedirect = ({
					"currentView": $("#js-current-view"),
					"wrapper": $("#cfsr-wrapper"),
					"init": function() {
						if (this.wrapper.length > 0) {
							$wrapper.on("click", "a.js-ajax-link", function(e) {
								var $ajaxLink = $(this);
								e.preventDefault();
								e.stopPropagation();
								if ($ajaxLink.attr("id") == "link-add-new") {
									cfSiteRedirect.loadView("add-new");
								}
								else if ($ajaxLink.attr("id") == "link-view-edit") {
									cfSiteRedirect.loadView("view-edit");
								}
							}).on("click", ".js-clone-repeater", function(e) {
								var $form = $(this).closest("form"),
									$repeater = $form.find(".repeater:first"),
									$newRow = $repeater.clone();
								e.preventDefault();
								if (!$form.data("row-iterator")) {
									$form.data("row-iterator", 1);
								}
								$newRow.removeClass("no-js repeater").appendTo($repeater.parent()).show();
								$newRow.html($newRow.html().replace("{row_iterator}", $form.data("row-iterator")));
								$form.data("row-iterator", $form.data("row-iterator") + 1);
							}).on("click", ".js-delete-record", function(e) {
								var $button = $(this),
									$row = $(this).closest("tr"),
									postId = $row.find(".js-old-post-id").val(),
									redirectUrl = $row.find(".js-old-redirect-url").val();
								e.preventDefault();
								e.stopPropagation();
								cfSiteRedirect.deleteRecord(postId, redirectUrl, $row);
							}).on("click", ".js-save-record", function(e) {
									var $button = $(this),
										$row = $(this).closest("tr"),
										data = {
											"post_id": $row.find(".js-new-post-id").val(),
											"old_post_id": $row.find(".js-old-post-id").val(),
											"redirect_url": $row.find(".js-new-redirect-url").val(),
											"old_redirect_url": $row.find(".js-old-redirect-url").val()
										}
									e.preventDefault();
									e.stopPropagation();
									cfSiteRedirect.saveRecord(data, $row);
							}).on("click", ".js-save-records", function(e) {
								e.preventDefault();
								e.stopPropagation();
								cfSiteRedirect.saveRecords($(this).closest("form").find("table:first"));
							});
							
							if (this.currentView.length > 0) {
								this.currentView.on("submit", "form.js-ajax-form", function(e) {
									e.preventDefault();
									e.stopPropagation();
									cfSiteRedirect.submitForm($(this)); // Submit the form via AJAX, do not redirect.
								});
							}
							
							this.updateAjaxControls(this.wrapper);
						}
						return this;
					},
					"updateAjaxControls": function($context) {
						$context.find(".ajax-controls").show();
						$context.find(".repeater").hide();
						$context.find(".js-clone-repeater:first").click();
					},
					"saveRecord": function(data, $scope) {
						data.action = "cfsr_save_redirect";
						console.log(data);
						$scope.css({"backgroundColor": "#FFFFFF"});
						$.ajax({
							"type": "POST",
							"url": ajaxurl,
							"data": data,
							"success": function(data) {
								$scope.css({"backgroundColor": "#99FF99"});
								$scope.find(".js-old-redirect-url").val(data.redirect_url);
								$scope.find(".js-old-post-id").val(data.post_id);
							},
							"error": function(xhr) {
								$scope.css({"backgroundColor": "#FF9999"});
								
								if (window.console) {
									window.console.log(xhr.responseText);
								}
							}
						})
					},
					"saveRecords": function($table) {
						$table.find("tbody tr").not(".repeater").each(function() {
							$row = $(this),
							data = {
								"post_id": $row.find(".js-new-post-id").val(),
								"old_post_id": $row.find(".js-old-post-id").val(),
								"redirect_url": $row.find(".js-new-redirect-url").val(),
								"old_redirect_url": $row.find(".js-old-redirect-url").val()
							}
							cfSiteRedirect.saveRecord(data, $row);
						});
					},
					"deleteRecord": function(postId, redirectUrl, $scope) {
						var $inputs = $scope.find("input, textarea, button");
						$inputs.attr("disabled", "disabled");
						$.ajax({
							"type": "POST",
							"url": ajaxurl,
							"data": {
								"action": "cfsr_delete_redirect",
								"post_id": postId,
								"redirect_url": redirectUrl,
							},
							"success": function(data) {
								$scope.fadeOut(500, function() { $row.remove(); });
							},
							"error": function(data) {
								$scope.css({"backgroundColor": "#FF9999"});
								$inputs.removeAttr("disabled");
							}
						})
					},
					"loadView": function(viewName) {
						cfSiteRedirect.currentView.html('<div class="loading">Loading view</div>');
						$.ajax({
							"type": "GET",
							"url": window.ajaxurl,
							"data": {
								"action": "cfsr_get_admin_view",
								"view": viewName
							},
							"success": function(data) {
								cfSiteRedirect.currentView.html(data); // Reset the current view.
								cfSiteRedirect.updateAjaxControls(cfSiteRedirect.currentView);
							},
							"error": function(data) {
								cfSiteRedirect.currentView.html(data); // Update the current view with returned error state
							}
						});
					} 
				}).init();
			});
		}
	</script>
	<?php
	}
}
add_action('admin_print_scripts', '_cf_site_redirect_admin_scripts', 100);

function _cf_site_redirect_ajax_load_view() {
	if (!current_user_can('manage_options')) {
		wp_die('I\'m sorry, Dave. I can\'t do that.');
	}
	if (!isset($_REQUEST['view'])) {
		header('HTTP/1.0 401 Invalid Request');
		echo(__('"view" parameter is required', 'cfsr'));
		exit;
	}
	$view_file = trailingslashit(dirname(__FILE__)).'/views/'.$_REQUEST['view'].'-redirects.php';
	if (!file_exists($view_file)) {
		header('HTTP/1.0 404 Not Found');
		echo(__('View not found', 'cfsr'));
		exit;
	}
	include_once($view_file);
	exit;
}
add_action('wp_ajax_cfsr_get_admin_view', '_cf_site_redirect_ajax_load_view');

function _cf_site_redirect_ajax_save_redirect() {
	if (!current_user_can('manage_options')) {
		wp_die("I'm sorry, Dave. I can't do that.");
	}
	if (!(isset($_REQUEST['post_id']) && isset($_REQUEST['redirect_url']))) {
		header('HTTP/1.0 401 Invalid Request');
		echo 'The "post_id" and "redirect_url" parameters are required.';
		exit;
	}
	$post_id = intval($_REQUEST['post_id']);
	$redirect_url = $_REQUEST['redirect_url'];
	$old_post_id = isset($_REQUEST['old_post_id']) ? intval($_REQUEST['old_post_id']) : 0;
	$old_redirect_url = isset($_REQUEST['old_redirect_url']) ? $_REQUEST['old_redirect_url'] : null;
	$result = _cf_site_redirect_save_redirect($post_id, $redirect_url, $old_post_id, $old_redirect_url);
	if (!$result || is_wp_error($result)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "Could not save redirect URL for $post_id";
		exit;
	}
	echo "Redirect: {$redirect_url} to {$post_id} saved.\n";
	exit;
}
add_action('wp_ajax_cfsr_save_redirect', '_cf_site_redirect_ajax_save_redirect');

function _cf_site_redirect_ajax_delete_redirect() {
	if (!current_user_can('manage_options')) {
		wp_die("I'm sorry, Dave. I can't do that.");
	}
	if (!(isset($_REQUEST['post_id']) && isset($_REQUEST['redirect_url']))) {
		header('HTTP/1.0 401 Invalid Request');
		echo 'The "post_id" and "redirect_url" parameters are required.';
		exit;
	}
	$post_id = intval($_REQUEST['post_id']);
	$redirect_url = $_REQUEST['redirect_url'];
	$result = _cf_site_redirect_delete_redirect($post_id, $redirect_url);
	if (!$result || is_wp_error($result)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "Could not delete redirect URL for {$post_id}";
		exit;
	}
	echo "Redirect: {$redirect_url} to {$post_id} deleted.\n";
	exit;
}
add_action('wp_ajax_cfsr_delete_redirect', '_cf_site_redirect_ajax_delete_redirect');

function _cf_site_redirect_save_redirect($post_id, $redirect_url, $old_post_id, $old_redirect_url) {
	if ($old_post_id != $post_id && $old_post_id > 0) {
		delete_post_meta($old_post_id, LEGACY_URL_FIELD_NAME, $old_redirect_url);
	}
	return update_post_meta($post_id, LEGACY_URL_FIELD_NAME, $redirect_url, $old_redirect_url);
}

function _cf_site_redirect_delete_redirect($post_id, $redirect_url) {
	return delete_post_meta($post_id, LEGACY_URL_FIELD_NAME, $redirect_url);
}

function _cf_site_redirect_admin_screen() {
	global $title;
	screen_icon();
	$base_url = remove_query_arg('cfsr-subpage', $_SERVER['REQUEST_URI']);
	?>
	<div id="cfsr-wrapper" class="cf-site-redirect-wrapper">
	<h1><?php echo esc_html($title); ?></h1>
	<p><?php echo esc_html(__('You may use this page to create, view, and edit redirect URLs for posts.', 'cfsr')); ?></p>
	<ul id="cfsr-controls" class="controls control-list">
		<li>
			<a class="button js-ajax-link" id="link-add-new" href="<?php echo esc_url(add_query_arg('cfsr-subpage', 'add-new', $base_url)); ?>"><?php echo esc_html(__('Add New Redirects', 'cfsr')); ?></a>
		</li>
		<li>
			<a class="button js-ajax-link" id="link-view-edit" href="<?php echo esc_url($base_url); ?>"><?php echo esc_html(__('View Redirects', 'cfsr')); ?></a>
		</li>
	</ul>
	<div id="js-current-view">
	<?php
		if (isset($_GET['cfsr-subpage']) && $_GET['cfsr-subpage'] == 'add-new') {
			include_once(trailingslashit(dirname(__FILE__)).'views/add-new-redirects.php');
		}
		else {
			include_once(trailingslashit(dirname(__FILE__)).'views/view-edit-redirects.php');
		}
	?>
	</div>
	</div>
	<?php
}

?>