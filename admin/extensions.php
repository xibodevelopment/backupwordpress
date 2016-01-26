<?php

namespace HM\BackUpWordPress;

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo get_settings_url() ?>">&larr; Backups</a>

		BackUpWordPress Extensions

	</h1>

	<div class="wp-filter">
		<p>Extend BackUpWordPress by installing extensions. Extensions allows you to pick and choose the exact features you need whilst also supporting us, the developers, so we can continue working on BackUpWordPress.</p>
	</div>

	<?php

	// TODO should be pulled from bwp.hmn.md
	$extentions = array(
		'dropbox' => (object) array(
			'name'			=> 'Dropbox',
			'slug'			=> 'dropbox',
			'url'			=> 'https://bwp.hmn.md/downloads/backupwordpress-to-dropbox/',
			'description' 	=> 'BackUpWordPress to Dropbox allows you to automatically send a copy of your backup to your Dropbox account. All you need to do is authorize BackUpWordPress once in your dashboard, and set a schedule.' ,
			'requirements'	=> 'PHP 5.3, Latest BackUpWordPress, WordPress 3.9, Mcrypt PHP extension',
			'author'		=> 'Human Made Limited',
			'author_url'	=> 'https://hmn.md/',
			'icon'			=> 'https://bwp.hmn.md/content/themes/backupwp/img/d-dropbox-alt.png',
			'installed'		=> false,
			'price'			=> 24
		),
		'drive' => (object) array(
			'name'			=> 'Google Drive',
			'slug'			=> 'dropbox',
			'url'			=> 'https://bwp.hmn.md/downloads/backupwordpress-to-google-drive/',
			'description' 	=> 'Many people already use Google Drive for creating and storing documents in the cloud, but did you know you can also use it for storing other types of files?

Well now you set BackUpWordPress to automatically send a copy of your backups to your Google Drive account, simply by authenticating with it through the extension settings.',
			'requirements'	=> 'PHP 5.3, Latest BackUpWordPress, WordPress 3.9, Mcrypt PHP extension',
			'author'		=> 'Human Made Limited',
			'author_url'	=> 'https://hmn.md/',
			'icon'			=> 'https://bwp.hmn.md/content/themes/backupwp/img/d-drive-alt.png',
			'installed'		=> false,
			'price'			=> 24
		),
		'ftp' => (object) array(
			'name'			=> 'FTP',
			'slug'			=> 'ftp',
			'url'			=> 'https://bwp.hmn.md/downloads/backupwordpress-to-ftp/',
			'description' 	=> 'Do you wish you could store a copy of your backup files on another server than your website hosting? Well now you can!

After activating the BackUpWordPress to FTP extension, you’ll find a new destination in your backup schedule settings. Just fill out your FTP/ SFTP credentials and you’ll be all set to get automatic backups sent to a remote server.',
			'requirements'	=> 'PHP 5.3, Latest BackUpWordPress, WordPress 3.9, Mcrypt PHP extension',
			'author'		=> 'Human Made Limited',
			'author_url'	=> 'https://hmn.md/',
			'icon'			=> 'https://bwp.hmn.md/content/themes/backupwp/img/d-ftp-alt.png',
			'installed'		=> true,
			'price'			=> 0
		)

	); ?>

	<h3>Remote Storage</h3>

	<p>It's important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providor.</p>

	<div class="wp-list-table widefat plugin-install">
		<div id="the-list">

			<?php foreach ( $extentions as $extension ) : ?>

				<div class="plugin-card plugin-card-<?php echo esc_attr( $extension->slug ); ?>">

					<div class="plugin-card-top">

						<div class="name column-name">
							<h4>
								<a href="" class="thickbox">
									<?php echo esc_html( $extension->name ); ?>
									<img src="<?php echo esc_url( $extension->icon ); ?>" class="plugin-icon" alt="">
								</a>
							</h4>
						</div>

						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<?php if ( $extension->installed ) : ?>
										<span class="button button-disabled" title="This extension is already installed and is up to date ">Installed</span>
									<?php else : ?>
										<a class="install-now button-primary" data-slug="jetpack" href="" aria-label="Install Jetpack by WordPress.com 3.7.2 now" data-name="Jetpack by WordPress.com 3.7.2">Buy Now &dollar;24</a>
									<?php endif; ?>
								</li>
								<li>
									<a href="h" class="thickbox" aria-label="More information about Jetpack by WordPress.com 3.7.2" data-title="Jetpack by WordPress.com 3.7.2">More Details</a>
								</li>
							</ul>
						</div>

						<div class="desc column-description">
							<p><?php echo esc_html( $extension->description ); ?></p>
							<p class="authors"> <cite>By <a href="<?php echo esc_url( $extension->author_url ); ?>"><?php echo esc_html( $extension->author ); ?></a></cite></p>
						</div>
					</div>

					<div class="plugin-card-bottom">
						<div class="column-updated">
							<strong>Last Updated:</strong> <span title="Sep 29, 2015 @ 23:17">1 week ago</span>
						</div>
					</div>
				</div>

			<?php endforeach; ?>

		</div>
	</div>

</div>
