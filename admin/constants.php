<?php

namespace HM\BackUpWordPress;

?>

<div id="hmbkp-constants">

	<p><?php printf(
		wp_kses(
			/* translators: 1: wp-config.php file 2: Link to Codex page with info on how edit wp-config.php file */
			__( 'You can define any of the following constants in your %1$s file to control advanced settings. <a href="%2$s">The Codex can help</a>. Defined constants will be highlighted.', 'backupwordpress' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		'<code>wp-config.php</code>',
		'https://codex.wordpress.org/Editing_wp-config.php'
		); ?></p>

	<table class="widefat">

		<tr<?php if ( defined( 'HMBKP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_PATH' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_PATH ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Default path for backups 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The path to the folder in which you would like to store your backup files. Defaults to %1$s. e.g. %2$s', 'backupwordpress' ),
					'<code>' . esc_html( Path::get_path() ) . '</code>',
					"<code>define( 'HMBKP_PATH', '/home/willmot/backups' );</code>"
					); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_MYSQLDUMP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_MYSQLDUMP_PATH ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: mysqldump 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The path to your %1$s executable. Used for the database backup if available. e.g. %2$s', 'backupwordpress' ),
					'<code>mysqldump</code>',
					"<code>define( 'HMBKP_MYSQLDUMP_PATH', '/opt/local/bin/mysqldump' );</code>"
				); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_ZIP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_ZIP_PATH ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: zip 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The path to your %1$s executable. Used to compress your files and database if available. e.g. %2$s', 'backupwordpress' ),
					'<code>zip</code>',
					"<code>define( 'HMBKP_ZIP_PATH', '/opt/local/bin/zip' );</code>"
				); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_EXCLUDE</code></td>

			<td>

				<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_EXCLUDE ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'Comma separated list of files or directories to exclude from backup. The backups directory is automatically excluded. e.g. %s', 'backupwordpress' ),
					"<code>define( 'HMBKP_EXCLUDE', '/wp-content/uploads/, /stats/, .svn/, *.txt' );</code>"
				); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_CAPABILITY</code></td>

			<td>

				<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_CAPABILITY ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Default capability value 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The capability required to view BackUpWordPress admin menus. Defaults to %1$s. e.g. %2$s', 'backupwordpress' ),
					'<code>manage_options</code>',
					"<code>define( 'HMBKP_CAPABILITY', 'edit_posts' );</code>"
				); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_ROOT' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_ROOT</code></td>

			<td>

				<?php if ( defined( 'HMBKP_ROOT' ) ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_ROOT ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Default root directory value 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The root directory that is backed up. Defaults to %1$s. e.g. %2$s', 'backupwordpress' ),
					'<code>' . esc_html( Path::get_home_path() ) . '</code>',
					"<code>define( 'HMBKP_ROOT', ABSPATH . 'wp/' );</code>"
					); ?></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME !== '11pm' ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_SCHEDULE_TIME</code></td>

			<td>

				<?php if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME !== '11pm' ) { ?>
					<p><?php printf(
						/* translators: Constant value specified in wp-config.php */
						esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
						'<code>' . esc_html( HMBKP_SCHEDULE_TIME ) . '</code>'
						); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Default schedule time value 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The time that your schedules should run. Defaults to %1$s. e.g. %2$s', 'backupwordpress' ),
					'<code>23:00</code>',
					"<code>define( 'HMBKP_SCHEDULE_TIME', '07:30' );</code>"
				); ?></p>

			</td>

		</tr>

		<?php foreach ( Services::get_services() as $file => $service ) :
			call_user_func( array( $service, 'constant' ) );
		endforeach; ?>

	</table>

</div>
