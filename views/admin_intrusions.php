<?php if( ! defined('ABSPATH')) exit; ?>

<div class="wrap">
	<div class="icon32" id="icon-index"><br/></div>
	<h2>Mute Screamer Intrusions</h2>

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
			<label for="s" class="screen-reader-text">Search Profiles:</label>
			<input type="text" value="<?php echo esc_attr('');?>" name="usersearch" id="user-search-input"/>
			<input type="submit" class="button" value="Search Profiles"/>
		</p>
	</form>

	<form method="get" action="" id="posts-filter">
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option selected="selected" value="">Bulk Actions</option>
					<option value="bulk_delete">Delete</option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="Apply"/>
				<?php wp_nonce_field('mscr_action_intrusions_delete'); ?>

				<input type="hidden" name="page" value="<?php echo $page; ?>" />
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

			<tbody class="list:user user-list" id="users">

				<?php if($intrusions) : ?>
					<?php foreach($intrusions as $intrusion) : ?>

						<?php $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"'; ?>

						<tr<?php echo $style; ?> id="user-<?php echo $intrusion->id; ?>">
							<th class="check-column" scope="row">
								<input type="checkbox" value="<?php echo $intrusion->id; ?>" class="<?php echo ''; ?>" id="user_<?php echo $intrusion->id; ?>" name="intrusions[]"/>
							</th>
							<?php foreach($columns as $key => $val) : ?>
								<td class="<?php echo $key; ?> column-<?php echo $key; ?>">
									<?php switch($key) :
										case 'name':
											echo $intrusion->name;
											break;

										case 'value':
											echo esc_html($intrusion->value);
											break;

										case 'page':
											echo esc_url($intrusion->page);
											break;

										case 'tags':
											echo $intrusion->tags;
											break;

										case 'ip':
											echo $intrusion->ip;
											break;

										case 'impact':
											echo $intrusion->impact;
											break;

										case 'origin':
											echo $intrusion->origin;
											break;

										case 'date':
											echo $intrusion->created;
											break;

										default:
											echo apply_filters('manage_mscr_intrusions_custom_column', '', $key, $intrusion->id);
									?>
									<?php endswitch;?>
								</td>
							<?php endforeach;?>
						</tr>

					<?php endforeach; ?>
				<?php else : ?>

					<tr>
						<td colspan="<?php echo count($columns); ?>">How good is that, no intrusions.</td>
					</tr>

				<?php endif; ?>

			</tbody>
		</table>

		<div class="tablenav">
			<?php echo $pagination; ?>

			<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value="">Bulk Actions</option>
					<option value="bulk_delete">Delete</option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply"/>
			</div>
			<br class="clear"/>
		</div>
	</form>
</div>
<script type='text/javascript'>
jQuery(function(){
	jQuery('.submitdelete').click(function(){return confirm('Delete\nAre you sure?')});
});
</script>