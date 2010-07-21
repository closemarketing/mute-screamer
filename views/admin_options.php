<?php if( ! defined('ABSPATH')) exit; ?>

<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2>Mute Screamer Settings</h2>

	<form action="options.php" method="post">
		<?php settings_fields('mscr_options'); ?>

		<table class="form-table">
			<tbody>
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
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" value="Save Changes" class="button-primary" name="Submit">
		</p>
	</form>
</div>