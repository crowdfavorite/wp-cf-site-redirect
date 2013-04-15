<?php
$redirects = cf_site_redirect_meta_query(array('posts_per_page' => -1, 'foundrows' => false));
?>
<h2><?php echo esc_html(__('View/Edit Existing Redirects', 'cfsr')); ?></h2>
<form id="js-cfsr-view-edit" class="js-ajax-form" action="" method="POST" data-row-iterator="<?php echo $redirects->total_rows + 1; ?>">
	<input type="hidden" name="cfrs_action" value="view-edit-submit" />
	<table>
		<thead>
			<tr>
				<th class="redirect-url"><?php echo esc_html(__('Redirect URL', 'cfsr')); ?></th>
				<th class="post-id"><?php echo esc_html(__('Post ID', 'cfsr')); ?>
				<th class="post-title hidden"><?php echo esc_html(__('Post Title', 'cfsr')); ?></th>
				<th class="post-type hidden"><?php echo esc_html(__('Post Type', 'cfsr')); ?></th>
				<th class="post-status hidden"><?php echo esc_html(__('Post Status', 'cfsr')); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($redirects->results as $i=>$redirect) { ?>
			<tr>
				<td class="redirect-url">
					<input type="hidden" class="js-old-redirect-url" name="redirects[<?php echo esc_attr($i); ?>][old_redirect_url]" value="<?php echo esc_attr($redirect['meta_value']); ?>" />
					<input type="text" class="js-new-redirect-url" name="redirects[<?php echo esc_attr($i); ?>][redirect_url]" value="<?php echo esc_attr($redirect['meta_value']); ?>" placeholder="http://example.com/" />
				</td>
				<td class="post-id">
					<input type="hidden" class="js-old-post-id" name="redirects[<?php echo esc_attr($i); ?>][old_post_id]" value="<?php echo esc_attr($redirect['post_id']); ?>">
					<input type="text" class="js-new-post-id" name="redirects[<?php echo esc_attr($i); ?>][post_id]" value="<?php echo esc_attr($redirect['post_id']); ?>" />
				<td class="post-title hidden">
					<input type="text post-typeahead" value="{post_title}" />
				</td>
				<td class="post-type hidden">{post_type}</td>
				<td class="post-status hidden">{post_status}</td>
				<td>
					<button type="submit" class="js-delete-record" name="delete_record_key" value="<?php echo $i; ?>"><?php echo esc_html(__('Delete', 'cfsr')); ?></button>
					<button type="submit" class="js-save-record" name="save_record_key" value="<?php echo $i; ?>"><?php echo esc_html(__('Save', 'cfsr')); ?></button>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<div class="frm-controls">
		<button type="submit" class="button button-primary js-save-records"><?php echo esc_html(__('Save Redirects')); ?></button>
	</div>
</form>