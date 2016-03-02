<?php

namespace HM\BackUpWordPress;

/**
 * Implement backup command
 *
 * @todo fix
 * @package wp-cli
 * @subpackage commands/third-party
 */
class CLI extends \WP_CLI_Command {

	/**
	 * Perform a Backup.
	 *
	 * ## OPTIONS
	 *
	 * [--files_only]
	 * : Backup files only, default to off
	 *
	 * [--database_only]
	 * : Backup database only, defaults to off
	 *
	 * [--destination]
	 * : dir that the backup should be save in, defaults to your existing backups directory
	 *
	 * [--root]
	 * : dir that should be backed up, defaults to site root.
	 *
	 * [--archive_filename]
	 * : filename for the resulting zip file
	 *
	 * [--excludes]
	 * : list of paths you'd like to exclude
	 *
	 * ## Usage
	 *
	 *     wp backupwordpress backup [--files_only] [--database_only] [--path<dir>] [--root<dir>] [--zip_command_path=<path>] [--mysqldump_command_path=<path>]
	 *
	 * @todo errors should be bubbled from Backup, Scheduled_Backup and the like instead of being repeated.
	 */
	public function backup( $args, $assoc_args ) {

		add_action( 'hmbkp_mysqldump_started', function () {
			\WP_CLI::line( __( 'Backup: Dumping database...', 'backupwordpress' ) );
		} );

		add_action( 'hmbkp_archive_started', function () {
			\WP_CLI::line( __( 'Backup: Zipping everything up...', 'backupwordpress' ) );
		} );

		if ( ! empty( $assoc_args['destination'] ) ) {
			Path::get_instance()->set_path( $assoc_args['destination'] );
		}

		Path::get_instance()->cleanup();

		if ( ! empty( $assoc_args['root'] ) ) {
			Path::get_instance()->set_root( $assoc_args['root'] );
		}

		if ( ( ! is_dir( Path::get_path() ) ) ) {
			\WP_CLI::error( __( 'Invalid backup path', 'backupwordpress' ) );
			return false;
		}

		if ( ! is_dir( Path::get_root() ) || ! is_readable( Path::get_root() ) ) {
			\WP_CLI::error( __( 'Invalid root path', 'backupwordpress' ) );
			return false;
		}

		$filename = 'backup.zip';

		if ( isset( $assoc_args['archive_filename'] ) ) {
			$filename = $assoc_args['archive_filename'];
		}

		$hm_backup = new Backup( $filename );

		if ( ! empty( $assoc_args['files_only'] ) ) {
			$hm_backup->set_type( 'file' );
		}

		if ( ! empty( $assoc_args['database_only'] ) ) {
			$hm_backup->set_type( 'database' );
		}

		if ( ! empty( $assoc_args['excludes'] ) ) {
			$hm_backup->set_excludes( $assoc_args['excludes'] );
		}

		$hm_backup->run();

		if ( file_exists( $hm_backup->get_backup_filepath() ) ) {
			\WP_CLI::success( __( 'Backup Complete: ', 'backupwordpress' ) . $hm_backup->get_backup_filepath() );
		} else {
			\WP_CLI::error( __( 'Backup Failed', 'backupwordpress' ) );
		}

	}

}

\WP_CLI::add_command( 'backupwordpress', 'HM\BackUpWordPress\CLI' );
