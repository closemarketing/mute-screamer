<?php if( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<div class="icon32" id="icon-index"><br/></div>
	<h2>Mute Screamer Intrusions <?php echo $search_title; ?></h2>

	<?php if ( $message ) : ?>

	<div id="message" class="updated"><p><?php echo $message; ?></p></div>

	<?php endif; ?>

	<div class="filter">
		<form method="get" action="" id="list-filter">
			<ul class="subsubsub">
				<li>&nbsp;</li>
			</ul>
		</form>
	</div>

	<form method="get" action="admin.php" class="search-form">
		<input type="hidden" value="<?php echo $page;?>" name="page"/>
		<p class="search-box">
			<label for="s" class="screen-reader-text"><?php _e( 'Search Intrusions', 'mute-screamer' ); ?></label>
			<input type="text" value="<?php echo esc_attr( $intrusions_search ); ?>" name="intrusions_search" id="mscr-intrusions-search-input"/>
			<input type="submit" class="button" value="<?php _e( 'Search Intrusions', 'mute-screamer' ); ?>"/>
		</p>
	</form>

	<?php if($intrusions) : ?>
	<form method="get" action="" id="posts-filter">
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option selected="selected" value=""><?php _e( 'Bulk Actions', 'mute-screamer' ); ?></option>
					<option value="bulk_delete"><?php _e( 'Delete', 'mute-screamer' ); ?></option>
					<option value="bulk_exclude"><?php _e( 'Exclude', 'mute-screamer' ); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="Apply"/>
				<?php wp_nonce_field( 'mscr_action_intrusions_bulk' ); ?>

				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
			</div>

			<?php echo $pagination; ?>

			<br class="clear"/>
		</div>

		<table cellspacing="0" class="widefat fixed">
			<thead>
				<tr class="thead">
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"/></th>
					<?php foreach($columns as $key => $val) : ?>
						<th style="" class="manage-column column-<?php echo $key;?>" id="<?php echo $key;?>" scope="col"><?php echo $val; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tfoot>
				<tr class="thead">
					<th style="" class="manage-column column-cb check-column" id="cb_2" scope="col"></th>
					<?php foreach($columns as $key => $val) : ?>
						<th style="" class="manage-column column-<?php echo $key;?>" id="<?php echo $key;?>_2" scope="col"><?php echo $val; ?></th>
					<?php endforeach; ?>
				</tr>
			</tfoot>

			<tbody class="list:intrusion intrusion-list" id="mscr_intrusions">

					<?php foreach($intrusions as $intrusion) : ?>

						<?php $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"'; ?>

						<tr<?php echo $style; ?> id="intrusion-<?php echo $intrusion->id; ?>">
							<th class="check-column" scope="row">
								<input type="checkbox" value="<?php echo $intrusion->id; ?>" class="<?php echo ''; ?>" id="intrusion_<?php echo $intrusion->id; ?>" name="intrusions[]"/>
							</th>
							<?php foreach($columns as $key => $val) : ?>
								<td class="<?php echo $key; ?> column-<?php echo $key; ?>">
									<?php switch ($key) :
										case 'name':
											$exclude_link = wp_nonce_url( admin_url( 'index.php?page=mscr_intrusions&action=exclude&intrusion=' . $intrusion->id ), 'mscr_action_exclude_intrusion' );
											$delete_link  = wp_nonce_url( admin_url( 'index.php?page=mscr_intrusions&action=delete&intrusion=' . $intrusion->id ), 'mscr_action_delete_intrusion' );
?>
											<strong><a href="<?php echo $exclude_link; ?>" title="<?php echo esc_attr( sprintf( __( 'Exclude &#8220;%s&#8221;' ), $intrusion->name ) ); ?>"><?php echo esc_html( $intrusion->name ); ?></a></strong>
											<div class="row-actions">
												<span class="exclude"><a title="<?php echo esc_attr( __( 'Add this item to the exception fields list', 'mute-screamer' ) ); ?>" href="<?php echo $exclude_link; ?>"><?php _e( 'Exclude', 'mute-screamer' ); ?></a> | </span>
												<span class="delete"><a title="<?php echo esc_attr( __( 'Delete this item', 'mute-screamer' ) ); ?>" class="delete submitdelete" href="<?php echo $delete_link; ?>"><?php _e( 'Delete', 'mute-screamer' ); ?></a></span>
											</div>
<?php
											break;

										case 'value':
											echo esc_html( $intrusion->value );
											break;

										case 'page':
											echo esc_url( $intrusion->page );
											break;

										case 'tags':
											echo esc_html( $intrusion->tags );
											break;

										case 'ip':
											echo esc_html( $intrusion->ip );
											break;

										case 'impact':
											echo esc_html( $intrusion->impact );
											break;

										case 'date':
											echo date( "{$date_format} {$time_format}", strtotime( $intrusion->created ) + $time_offset );
											break;

										default:
											echo apply_filters( 'manage_mscr_intrusions_custom_column', '', $key, $intrusion->id );
									?>
									<?php endswitch;?>
								</td>
							<?php endforeach;?>
						</tr>

					<?php endforeach; ?>

			</tbody>
		</table>

		<div class="tablenav">
			<?php echo $pagination; ?>

			<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e( 'Bulk Actions', 'mute-screamer' ); ?></option>
					<option value="bulk_delete"><?php _e( 'Delete', 'mute-screamer' ); ?></option>
					<option value="bulk_exclude"><?php _e( 'Exclude', 'mute-screamer' ); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply"/>
			</div>
			<br class="clear"/>
		</div>
	</form>

	<?php elseif( ! $search_title ) : ?>

	<p><?php _e( 'How good is that, no intrusions.', 'mute-screamer' ); ?></p>

	<?php else : ?>

	<p><?php _e( 'No intrusions found.', 'mute-screamer' ); ?></p>

	<?php endif; ?>

</div>
<script type='text/javascript'>
jQuery(function(){
	jQuery('.submitdelete').click(function() {
		return confirm(<?php _e( '"You are about to permanently delete this item.\n  \'Cancel\' to stop, \'OK\' to delete."', 'mute-screamer' ); ?>);
	});
});
</script>