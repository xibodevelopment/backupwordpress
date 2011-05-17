<div id="hmbkp_advanced-options">

	<?php 
		
		if( isset( $_POST['submit'] ) ) {
			$errors = hmbkp_option_save();
		}
		
		if( $errors ) {
			foreach( $errors as $error ) {
				echo '<div class="error fade">' . $error . '</div>';
			}
		}
	?>

    <h4><?php _e( 'Advanced Options', 'hmbkp' ); ?></h4>

	<form action="" method="post">
		<table class="form-table">
			<tbody>
				<tr align="top">
					<th scope="row">Automatic Backups</th>
					<td>
						<fieldset>
							<label for="hmbkp_automatic_on"> 
								<input name="hmbkp_automatic" type="radio" id="hmbkp_automatic_on" value="1" <?php if( !hmbkp_get_disable_automatic_backup() ) echo 'checked="checked"'; ?> <?php if( defined('HMBKP_DISABLE_AUTOMATIC_BACKUP') ) echo 'disabled="disabled"'; ?>>
								Backup my site automatically.
							</label><br/>
							<label for="hmbkp_automatic_off">
								<input name="hmbkp_automatic" type="radio" id="hmbkp_automatic_off" value="0" <?php if( !hmbkp_get_disable_automatic_backup() ) echo 'checked="checked"'; ?> <?php if( defined('HMBKP_DISABLE_AUTOMATIC_BACKUP') ) echo 'disabled="disabled"'; ?>>
								No automatic backups.
							</label><br/>
						</fieldset>
					</td>
				</tr>
				<tr align="top">
					<th scope="row"><label for="hmbkp_what_to_backup">What to Backup</label></th>
					<td>Backup my 
						<select name="hmbkp_what_to_backup" id="hmbkp_what_to_backup" <?php if( defined('HMBKP_FILES_ONLY') || defined('HMBKP_DATABASE_ONLY')  ) echo 'disabled="disabled"'; ?>>
							<option value="default" <?php if( !get_option( 'hmbkp_files_only' ) && !get_option( 'hmbkp_database_only' ) ) echo 'selected="selected"'; ?>>database &amp; files</option>
							<option <?php if( hmbkp_get_database_only() ) echo 'selected="selected"'; ?>>database only</option>
							<option <?php if( hmbkp_get_files_only() ) echo 'selected="selected"'; ?>>files only</option>
						</select>
					</td>
				</tr>
				<tr align="top">
					<th scope="row"><label for="hmbkp_backup_number">Number of backups</label></th>
					<td><label for="hmbkp_backup_number">The last <input type="text" class="small-text <?php if( defined('HMBKP_MAX_BACKUPS') ) echo 'disabled'; ?>" value="<?php echo hmbkp_max_backups() ?>" id="hmbkp_backup_number" name="hmbkp_backup_number" <?php if( defined('HMBKP_MAX_BACKUPS') ) echo 'disabled="disabled"'; ?>> backups will be stored on the server.</label></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="hmbkp_email_address">Email Backups</label></th>
					<td><input name="hmbkp_email_address" type="text" id="hmbkp_email_address" value="<?php echo hmbkp_get_email_address(); ?>" class="regular-text <?php if( defined('HMBKP_EMAIL') ) echo 'disabled'; ?>" <?php if( defined('HMBKP_EMAIL') ) echo 'disabled="disabled"'; ?>> <span class="description">A copy of the backup file will be emailed to this address. Disabled if left blank.</span></td>
				</tr>
				<tr align="top">
					<th scope="row"><label for="hmbkp_excludes">Excludes</th>
					<td>
						<textarea class="large-text<?php if ( defined( 'HMBKP_EXCLUDES' ) ) echo ' disabled' ?>" name="hmbkp_excludes" id="hmbkp_excludes" <?php if( defined( 'HMBKP_EXCLUDES' ) ) echo 'disabled="disabled"'; ?>><?php hmbkp_option_value('hmbkp_excludes'); ?></textarea> 
						<span class="description">A comma separated list of file and directory paths that you do <strong>not</strong> want to backup.</span><br/>
						e.g. <code>file.php, directory/, directory/file.jpg</code>
					</td>
				</tr>
			</tbody>
		</table>
	<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
	</form>
	<p><?php printf( __( 'You can still %s settings in your %s to control advanced options. A full list of %s can be found in the readme. Defined settings will not be editable via the WordPress admin.', 'hmbkp' ), '<code>define</code>', '<code>wp-config.php</code>', '<code>Constants</code>', '<a href="http://codex.wordpress.org/Editing_wp-config.php">' . __( 'The Codex can help', 'hmbkp' ) . '</a>', '<code>Constants</code>' ); ?></p>
	    
</div>

<?php
/**
 *	hmbkp_option_save function 
 *
 *	Verify & save all the options set on 
 *	the backupwordpress advanced options page.
 * 	
 *	Returns array of errors encountered when updating options.
 * 	If no errors - returns false. 
 *
 *	Uses $_POST data
 */
function hmbkp_option_save() {
	
	if( ! (bool) $_POST['hmbkp_automatic'] )
		update_option('hmbkp_disable_automatic_backup', 'true' );
	else 
		delete_option('hmbkp_disable_automatic_backup');
	
	if( $_POST['hmbkp_what_to_backup'] == 'files only' ) {
		update_option('hmbkp_files_only', 'true' );
		delete_option('hmbkp_database_only');
	} elseif( $_POST['hmbkp_what_to_backup'] == 'database only' ) {
		update_option('hmbkp_database_only', 'true' );
		delete_option('hmbkp_files_only');
	} else {
		delete_option('hmbkp_database_only');
		delete_option('hmbkp_files_only');
	}
	
	if( $max_backups = intval( $_POST['hmbkp_backup_number'] ) ) {
		update_option('hmbkp_max_backups', intval( $_POST['hmbkp_backup_number'] ) );
	} else {
		delete_option('hmbkp_max_backups', intval( $_POST['hmbkp_backup_number'] ) );
		$errors[] = 'Invalid number of backups entered. Reset to default (10 backups)';
	}
	
	if( !is_email( $_POST['hmbkp_email_address'] ) && !empty( $_POST['hmbkp_email_address'] ) ) {
		$errors[] = 'Email address was invalid.';
	} elseif( !empty( $_POST['hmbkp_email_address'] ) ) {
		update_option( 'hmbkp_email_address', $_POST['hmbkp_email_address'] );
	} else {
		delete_option( 'hmbkp_email_address' );
	}
	
	if( !empty( $_POST['hmbkp_excludes'] ) )
		update_option('hmbkp_excludes', $_POST['hmbkp_excludes'] );
	else 
		delete_option('hmbkp_excludes');
	
	if( $errors ) {
		return $errors; 
	}
				
}

/**
 *	hmbkp_option_value function
 *
 *	Echoes the value of the given option.
 *	If the values have been defined using constants, return that value instead. 
 *
 */
function hmbkp_option_value( $option, $default = false, $echo = true ) {
	
	switch( $option ) {
						
		case 'hmbkp_excludes' :
			if( defined( 'HMBKP_EXCLUDES' ) )
				$r = HMBKP_EXCLUDES;
			else
				$r = get_option( 'hmbkp_excludes', false );
			break;
		
		default:
			$r = get_option( $option, $default );
	}
		
	if( $echo )
		echo $r;
	else
		return $r;
}