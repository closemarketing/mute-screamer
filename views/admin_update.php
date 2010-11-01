<?php if( ! defined('ABSPATH')) exit; ?>

<h3><?php _e( 'Mute Screamer' ); ?></h3>
<p><?php _e( 'The following files have new versions available. Check the ones you want to update and then click &#8220;Update Mute Screamer&#8221;.' ); ?></p>
<form method="post" action="update.php?action=mscr_upgrade_diff" name="upgrade-mute-screamer" class="upgrade">
<?php wp_nonce_field('upgrade-core'); ?>
<p><input id="upgrade-mute-screamer" class="button" type="submit" value="<?php esc_attr_e('Update Mute Screamer'); ?>" name="upgrade" /></p>
<table class="widefat" cellspacing="0" id="update-mute-screamer-table">
	<thead>
	<tr>
		<th scope="col" class="manage-column check-column"><input type="checkbox" id="mute-screamer-select-all" /></th>
		<th scope="col" class="manage-column"><label for="mute-screamer-select-all"><?php _e('Select All'); ?></label></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column check-column"><input type="checkbox" id="mute-screamer-select-all-2" /></th>
		<th scope="col" class="manage-column"><label for="mute-screamer-select-all-2"><?php _e('Select All'); ?></label></th>
	</tr>
	</tfoot>
	<tbody class="plugins">
<?php
	foreach ( $files as $file => $file_data ) {
		echo "
	<tr class='active'>
		<th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='" . esc_attr($file) . "' /></th>
		<td class='plugin-title'><strong>{$file}</strong>" . sprintf(__('Update to revision %1$s. <a href="%2$s">Review changeset</a>.'), $file_data->revision, $file_data->revision_url) . "</td>
	</tr>";
	}
?>
	</tbody>
</table>
<p><input id="upgrade-mute-screamer-2" class="button" type="submit" value="<?php esc_attr_e('Update Mute Screamer'); ?>" name="upgrade" /></p>
</form>
