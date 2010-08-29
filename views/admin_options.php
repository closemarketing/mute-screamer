<?php if( ! defined('ABSPATH')) exit; ?>

<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2>Mute Screamer Settings</h2>

	<form action="options.php" method="post">
		<?php settings_fields('mscr_options'); ?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">WordPress Admin</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>WordPress Admin</span></legend>
							<label for="mscr_enable_admin">
								<input type="checkbox" value="1" id="mscr_enable_admin" name="mscr_options[enable_admin]" <?php checked('1', $enable_admin); ?> />
								Enable Mute Screamer for the WordPress admin
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_email">E-mail address </label></th>
					<td>
						<input type="text" class="regular-text" value="<?php echo $email; ?>" id="mscr_email" name="mscr_options[email]">
						<span class="description">This address is used to send intrusion alerts.</span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">E-mail Notifications</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>Email notifications</span></legend>
							<label for="mscr_email_notifications">
								<input type="checkbox" value="1" id="mscr_email_notifications" name="mscr_options[email_notifications]" <?php checked('1', $email_notifications); ?> />
								Send alert emails
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><label for="mscr_email_threshold">E-mail threshold </label></th>
					<td>
						<input type="text" class="small-text" value="<?php echo $email_threshold; ?>" id="mscr_email_threshold" name="mscr_options[email_threshold]" />
						<span class="description">Minimum impact to send an alert email.</span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">Exception fields</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>Exception fields</span></legend>
							<p><label for="mscr_exception_fields">
								Define fields that will be excluded from PHPIDS. One field per line. We've already added some defaults.<br />
								Example - exlude the POST field my_field: POST.my_field <br />
								Example - regular expression exclude: /.*foo/i
							</label></p>
							<p><textarea class="large-text code" id="mscr_exception_fields" cols="50" rows="10" name="mscr_options[exception_fields]"><?php echo $exception_fields; ?></textarea></p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">HTML fields</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>HTML fields</span></legend>
							<p><label for="mscr_html_fields">
								Define fields that contain HTML and need preparation before hitting the PHPIDS rules.<br />
								Note: Fields must contain valid HTML
							</label></p>
							<p><textarea class="large-text code" id="mscr_html_fields" cols="50" rows="10" name="mscr_options[html_fields]"><?php echo $html_fields; ?></textarea></p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">JSON fields</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>JSON fields</span></legend>
							<p><label for="mscr_json_fields">
								Define fields that contain JSON data and should be treated as such.
							</label></p>
							<p><textarea class="large-text code" id="mscr_json_fields" cols="50" rows="10" name="mscr_options[json_fields]"><?php echo $json_fields; ?></textarea></p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" value="Save Changes" class="button-primary" name="Submit">
		</p>
	</form>
</div>