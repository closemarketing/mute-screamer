<?php if( ! defined('ABSPATH')) exit; ?>

<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2><?php _e( 'Mute Screamer Settings', 'mute-screamer' ); ?></h2>

	<form action="options.php" method="post">
		<?php settings_fields('mscr_options'); ?>

		<h3><?php _e( 'General Settings', 'mute-screamer' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'WordPress Admin', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'WordPress Admin', 'mute-screamer' ); ?></span></legend>
							<label for="mscr_enable_admin">
								<input type="checkbox" value="1" id="mscr_enable_admin" name="mscr_options[enable_admin]" <?php checked('1', $enable_admin); ?> />
								<?php _e( 'Enable Mute Screamer for the WordPress admin', 'mute-screamer' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php _e( 'Email', 'mute-screamer' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="mscr_email"><?php _e( 'E-mail address', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" value="<?php echo esc_attr( $email ); ?>" id="mscr_email" name="mscr_options[email]" />
						<span class="description"><?php _e( 'This address is used to send intrusion alerts.', 'mute-screamer' ); ?></span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'E-mail Notifications', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'E-mail Notifications', 'mute-screamer' ); ?></span></legend>
							<label for="mscr_email_notifications">
								<input type="checkbox" value="1" id="mscr_email_notifications" name="mscr_options[email_notifications]" <?php checked('1', $email_notifications); ?> />
								<?php _e( 'Send alert emails', 'mute-screamer' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_email_threshold"><?php _e( 'E-mail threshold', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo esc_attr( $email_threshold ); ?>" id="mscr_email_threshold" name="mscr_options[email_threshold]" />
						<span class="description"><?php _e( 'Minimum impact to send an alert email.', 'mute-screamer' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php _e( 'Warning Page', 'mute-screamer' ); ?></h3>
		<p><?php _e( 'To setup a warning page you will need to create a template named 500.php for your theme.', 'mute-screamer' ); ?></p>
		<p><?php printf( __( 'You can find an example 500.php template based on <a href="http://wordpress.org/extend/themes/twentyten">TwentyTen</a> in %s/mute-screamer/templates/500.php', 'mute-screamer' ), str_replace( ABSPATH, '', WP_PLUGIN_DIR ) ); ?></p>
		<p><?php _e( "If a 500.php template can't be found then 404.php is used, and if that fails it will redirect to the homepage.", 'mute-screamer' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'WordPress admin warning', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'WordPress admin warning', 'mute-screamer' ); ?></span></legend>
							<label for="mscr_warning_wp_admin">
								<input type="checkbox" value="1" id="mscr_warning_wp_admin" name="mscr_options[warning_wp_admin]" <?php checked('1', $warning_wp_admin); ?> />
								<?php _e( 'Log user out of the WordPress admin', 'mute-screamer' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_warning_threshold"><?php _e( 'Warning threshold', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo esc_attr( $warning_threshold ); ?>" id="mscr_warning_threshold" name="mscr_options[warning_threshold]" />
						<span class="description"><?php _e( 'Minimum impact to show warning page.', 'mute-screamer' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php _e( 'User Banning', 'mute-screamer' ); ?></h3>
		<p><?php _e( 'Users can be banned for attacks over a certain threshold or for a number of repeated attacks.', 'mute-screamer' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'Enable banning', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'Enable banning', 'mute-screamer' ); ?></span></legend>
							<label for="mscr_ban_enabled">
								<input type="checkbox" value="1" id="mscr_ban_enabled" name="mscr_options[ban_enabled]" <?php checked('1', $ban_enabled); ?> />
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_ban_time"><?php _e( 'Ban time', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo esc_attr( $ban_time ); ?>" id="mscr_ban_time" name="mscr_options[ban_time]" />
						<span class="description"><?php _e( 'Number of seconds a user will be banned.', 'mute-screamer' ); ?></span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_ban_threshold"><?php _e( 'Ban threshold', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo esc_attr( $ban_threshold ); ?>" id="mscr_ban_threshold" name="mscr_options[ban_threshold]" />
						<span class="description"><?php _e( 'Minimum impact to ban a user.', 'mute-screamer' ); ?></span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_attack_repeat_limit"><?php _e( 'Attack repeat limit', 'mute-screamer' ); ?></label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo esc_attr( $attack_repeat_limit ); ?>" id="mscr_attack_repeat_limit" name="mscr_options[attack_repeat_limit]" />
						<span class="description"><?php _e( 'Number of repeated attacks before a user is banned (repeat attacks can be under the ban threshold).', 'mute-screamer' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php _e( 'Exceptions', 'mute-screamer' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'Exception fields', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'Exception fields', 'mute-screamer' ); ?></span></legend>
							<p><label for="mscr_exception_fields">
								<?php _e( "Define fields that will be excluded from PHPIDS. One field per line. We've already added some defaults.", 'mute-screamer' ); ?><br />
								<?php _e( 'Example - exlude the POST field my_field: POST.my_field', 'mute-screamer' ); ?><br />
								<?php _e( 'Example - regular expression exclude: /.*foo/i', 'mute-screamer' ); ?>
							</label></p>
							<p><textarea class="large-text code" id="mscr_exception_fields" cols="50" rows="5" name="mscr_options[exception_fields]"><?php echo esc_textarea( $exception_fields ); ?></textarea></p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'HTML fields', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'HTML fields', 'mute-screamer' ); ?></span></legend>
							<p><label for="mscr_html_fields">
								<?php _e( 'Define fields that contain HTML and need preparation before hitting the PHPIDS rules.', 'mute-screamer' ); ?><br />
								<?php _e( 'Note: Fields must contain valid HTML', 'mute-screamer' ); ?>
							</label></p>
							<p><textarea class="large-text code" id="mscr_html_fields" cols="50" rows="5" name="mscr_options[html_fields]"><?php echo esc_textarea( $html_fields ); ?></textarea></p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'JSON fields', 'mute-screamer' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'JSON fields', 'mute-screamer' ); ?></span></legend>
							<p><label for="mscr_json_fields">
								<?php _e( 'Define fields that contain JSON data and should be treated as such.', 'mute-screamer' ); ?>
							</label></p>
							<p><textarea class="large-text code" id="mscr_json_fields" cols="50" rows="5" name="mscr_options[json_fields]"><?php echo esc_textarea( $json_fields ); ?></textarea></p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" value="<?php esc_attr_e( 'Save Changes', 'mute-screamer' ); ?>" class="button-primary" name="Submit">
		</p>
	</form>
</div>