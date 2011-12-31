<div id="hmbkp-settings" <?php if ( ! hmbkp_get_backups() || ! empty( $_POST['hmbkp_settings_submit'] ) ) echo ' class="show_form"' ?>>

    <h3><?php _e( 'Settings', 'hmbkp' ); ?></h3>

	<p><?php printf( __( 'You can define %s in your %s to control some settings. A full list of %s can be found in the <a href="#contextual-help-wrap" class="hmbkp-show-help-tab">help</a> panel. Defined settings will not be editable below.', 'hmbkp' ), '<code>Constants</code>', '<code>wp-config.php</code>', '<code>Constants</code>' ); ?></p>

	<form method="post">
		
		<?php wp_nonce_field( 'hmbkp_settings', 'hmbkp_settings_nonce' ); ?>
		
		<table class="form-table">
			<tbody>

				<tr align="top">
			
					<th scope="row"><?php _e( 'Automatic Backups', 'hmbkp' ); ?></th>
			
					<td>
						
						<label for="hmbkp_automatic_on"> 
						    <input name="hmbkp_automatic" type="radio" id="hmbkp_automatic_on" value="1" <?php checked( ! hmbkp_get_disable_automatic_backup() ); ?> <?php disabled( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ); ?>>
						    <?php _e( 'Backup my site automatically.', 'hmbkp' ); ?>
						</label><br/>
						
						<label for="hmbkp_automatic_off">
						    <input name="hmbkp_automatic" type="radio" id="hmbkp_automatic_off" value="0" <?php checked( hmbkp_get_disable_automatic_backup() ); ?> <?php disabled( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ); ?>>
						    <?php _e( 'No automatic backups.', 'hmbkp' ); ?>
						</label>
					
					</td>
			
				</tr>
	
				<tr align="top">
					
					<th scope="row"><label for="hmbkp_frequency"><?php _e( 'Frequency of backups', 'hmbkp' ); ?></label></th>
					
					<td>
					
						<?php _e( 'Automatic backups will occur', 'hmbkp' ); ?>
						
						<select name="hmbkp_frequency" id="hmbkp_frequency">
						    <option value="daily" <?php selected( ! get_option( 'hmbkp_schedule_frequency' ) ); ?>><?php _e( 'Daily', 'hmbkp' ); ?></option>
						    <option value="hmbkp_weekly" <?php selected( get_option( 'hmbkp_schedule_frequency' ), 'hmbkp_weekly' ); ?>><?php _e( 'Weekly', 'hmbkp' ); ?></option>
						    <option value="hmbkp_fortnightly" <?php selected( get_option( 'hmbkp_schedule_frequency' ), 'hmbkp_fortnightly' ); ?>><?php _e( 'Fortnightly', 'hmbkp' ); ?></option>
						    <option value="hmbkp_monthly" <?php selected( get_option( 'hmbkp_schedule_frequency' ), 'hmbkp_monthly' ); ?>><?php _e( 'Monthly', 'hmbkp' ); ?></option>
						</select>
					
					</td>

				</tr>

				<tr align="top">
				
					<th scope="row"><label for="hmbkp_what_to_backup"><?php _e( 'What to Backup', 'hmbkp' ); ?></label></th>
				
					<td>
					
						<?php _e( 'Backup my', 'hmbkp' ); ?>
				
						<select name="hmbkp_what_to_backup" id="hmbkp_what_to_backup" <?php disabled( defined( 'HMBKP_FILES_ONLY' ) || defined( 'HMBKP_DATABASE_ONLY' )  ); ?>>
							<option value="default" <?php selected( ! get_option( 'hmbkp_files_only' ) && !get_option( 'hmbkp_database_only' ) ); ?>><?php _e( 'database &amp; files', 'hmbkp' ); ?></option>
							<option <?php selected( hmbkp_get_database_only() ); ?>><?php _e( 'database only', 'hmbkp' ); ?></option>
							<option <?php selected( hmbkp_get_files_only() ); ?>><?php _e( 'files only', 'hmbkp' ); ?></option>
						</select>
				
					</td>
				
				</tr>
				
				<tr align="top">
					<th scope="row"><label for="hmbkp_backup_number"><?php _e( 'Number of backups', 'hmbkp' ); ?></label></th>
					<td><label for="hmbkp_backup_number"><?php printf( __( 'The last %s backups will be stored on the server.', 'hmbkp' ), '<input type="text" class="small-text ' . ( defined( 'HMBKP_MAX_BACKUPS' ) ? 'disabled' : '' ) . '" value="' . hmbkp_max_backups() . '" id="hmbkp_backup_number" name="hmbkp_backup_number"' . disabled( defined( 'HMBKP_MAX_BACKUPS' ), true, false ) . '>' ); ?></label></td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="hmbkp_email_address"><?php _e( 'Email backups', 'hmbkp' ); ?></label></th>
					<td><input name="hmbkp_email_address" type="text" id="hmbkp_email_address" value="<?php echo hmbkp_get_email_address(); ?>" class="regular-text <?php if ( defined( 'HMBKP_EMAIL' ) ) echo 'disabled'; ?>" <?php disabled( defined( 'HMBKP_EMAIL' ) ); ?>> <span class="description"><?php _e( 'A copy of the backup file will be emailed to this address. Disabled if left blank.', 'hmbkp' ); ?></span></td>
				</tr>
				
				<tr align="top">
					<th scope="row"><label for="hmbkp_excludes"><?php _e( 'Excludes', 'hmbkp' ); ?></th>
					<td>
						<textarea class="code large-text<?php if ( defined( 'HMBKP_EXCLUDE' ) || hmbkp_get_database_only() ) echo ' disabled' ?>" name="hmbkp_excludes" id="hmbkp_excludes" <?php disabled( defined( 'HMBKP_EXCLUDE' ) || hmbkp_get_database_only() ); ?>><?php echo hmbkp_get_excludes(); ?></textarea> 
						<span class="description"><?php _e( 'A comma separated list of file and directory paths that you do <strong>not</strong> want to backup.', 'hmbkp' ); ?></span><br/>
						<?php _e( 'e.g.', 'hmbkp' ); ?> <code>file.php, /directory/, /directory/file.jpg</code>
					</td>
				</tr>

			</tbody>
	
		</table>

		<p class="submit"><input type="submit" name="hmbkp_settings_submit" id="submit" class="button-primary" value="Save Changes"></p>

	</form>
	    
</div>