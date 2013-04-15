<h2><?php echo esc_html(__('Add New Redirects', 'cfsr')); ?></h2>
<form id="js-cfsr-add-new" class="js-ajax-form" action="" method="POST">
	<input type="hidden" name="cfrs_action" value="add-new-submit" />
	<table>
		<thead>
			<tr>
				<th class="redirect-url"><?php echo esc_html(__('Redirect URL', 'cfsr')); ?></th>
				<th class="post-id"><?php echo esc_html(__('Post ID', 'cfsr')); ?>
				<th class="post-title hidden"><?php echo esc_html(__('Post Title', 'cfsr')); ?></th>
				<th class="post-type hidden"><?php echo esc_html(__('Post Type', 'cfsr')); ?></th>
				<th class="post-status hidden"><?php echo esc_html(__('Post Status', 'cfsr')); ?></th>
				<th class="ajax-controls hidden"></th>
			</tr>
		</thead>
		<tbody>
			<tr class="no-js repeater">
				<td class="redirect-url">
					<input type="text" class="js-new-redirect-url" name="redirects[{row_iterator}][redirect_url]" value="" placeholder="http://example.com/" />
				</td>
				<td class="post-id">
					<input type="text" class="js-new-post-id" name="redirects[{row_iterator}][post_id]" value="" />
				<td class="post-title hidden">
					<input type="text post-typeahead" value="" />
				</td>
				<td class="post-type hidden">
				</td>
				<td class="post-status hidden">
				</td>
				<td class="ajax-controls hidden">
					<button class="js-remove-row" onclick="jQuery(this).closest('tr').remove(); return false;"><?php echo esc_html(__('Remove', 'cfsr')); ?></button>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="frm-controls">
		<button class="button ajax-controls hidden js-clone-repeater "><?php echo esc_html(__('Add Another Redirect', 'cfsr')); ?></button>
		<button type="submit" class="button button-primary js-save-records"><?php echo esc_html(__('Save New Redirects')); ?></button>
	</div>
</form>