<?php

/**
 * Implement backup command
 *
 * @package wp-cli
 * @subpackage commands/third-party
 */
class BackUpCommand extends WP_CLI_Command {

	function __construct( $args, $assoc_args ) {

		// Make sure it's possible to do a backup
		if ( hmbkp_is_safe_mode_active() ) {
			WP_CLI::error( 'Backup not possible when php is running safe_mode on' );
			return false;
		}

		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );

		add_action( 'hmbkp_mysqldump_started', function() {
			WP_CLI::line( 'Backup: Dumping database...' );
		} );

		add_action( 'hmbkp_archive_started', function() {
			WP_CLI::line( 'Backup: Zipping everything up...' );
		} );

		// Clean up any mess left by a previous backup
		hmbkp_cleanup();

		$hm_backup = HM_Backup::get_instance();

		if ( ! empty( $assoc_args['path'] ) )
			$hm_backup->path = $assoc_args['path'];

		if ( ! empty( $assoc_args['root'] ) )
			$hm_backup->root = $assoc_args['root'];

		if ( ( ! is_dir( $hm_backup->path() ) && ( ! is_writable( dirname( $hm_backup->path() ) ) || ! mkdir( $hm_backup->path() ) ) ) || ! is_writable( $hm_backup->path() ) ) {
			WP_CLI::error( 'Invalid backup path' );
			return false;
		}


		if ( ! is_dir( $hm_backup->root() ) || ! is_readable( $hm_backup->root() ) ) {
			WP_CLI::error( 'Invalid root path' );
			return false;
		}

		// Default to both
		$hm_backup->files_only = false;
		$hm_backup->database_only = false;

		if ( ! empty( $assoc_args['files_only'] ) )
			$hm_backup->files_only = true;

		if ( ! empty( $assoc_args['database_only'] ) )
			$hm_backup->database_only = true;

		if ( ! empty( $assoc_args['mysqldump_command_path'] ) )
			$hm_backup->mysqldump_command_path = empty( $assoc_args['mysqldump_command_path'] ) || $assoc_args['mysqldump_command_path'] === 'false' ? false : true;

		if ( ! empty( $assoc_args['zip_command_path'] ) )
			$hm_backup->zip_command_path = empty( $assoc_args['zip_command_path'] ) || $assoc_args['zip_command_path'] === 'false' ? false : true;

		if ( ! empty( $assoc_args['excludes'] ) )
			$hm_backup->excludes = 	$valid_rules = array_filter( array_map( 'trim', explode( ',', $assoc_args['excludes'] ) ) );

		$hm_backup->backup();

	    WP_CLI::line( 'Backup: Deleting old backups...' );

		// Delete any old backup files
	    hmbkp_delete_old_backups();

    	if ( file_exists( HM_Backup::get_instance()->archive_filepath() ) )
			WP_CLI::success( 'Backup Complete: ' . HM_Backup::get_instance()->archive_filepath() );

		else
			WP_CLI::error( 'Backup Failed' );

	}

	static function help() {

		WP_CLI::line( <<<EOB
usage: wp backup [--files_only] [--database_only] [--path<dir>] [--root<dir>] [--zip_command_path=<path>] [--mysqldump_command_path=<path>]

	 --files_only                   Backup files only, default to off
	 --database_only                Backup database only, defaults to off
	 --path                         dir that the backup should be save in, defaults to wp-content/backups/
	 --root                         dir that should be backed up, defaults to ABSPATH
	 --zip_command_path             path to your zip binary, standard locations are automatically used
	 --mysqldump_command_path       path to your mysqldump binary, standard locations are automatically used

EOB
		);

	}

}
WP_CLI::addCommand( 'backup', 'BackUpCommand' );