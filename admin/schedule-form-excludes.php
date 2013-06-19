<form method="post" class="hmbkp-form">

    <input type="hidden" name="hmbkp_schedule_id" value="<?php esc_attr_e( $schedule->get_id() ); ?>" />

    <fieldset class="hmbkp-edit-schedule-excludes-form">

        <legend><?php _e( 'Manage Excludes', 'hmbkp' ); ?></legend>

        <div class="hmbkp_add_exclude_rule">

        	<label for="hmbkp-new-exclude-rule">

                <?php _e( 'New Exclude Rule[s]', 'hmbkp' ); ?>

                <input id="hmbkp-new-exclude-rule" type="text" class="code" placeholder="" />

                <button type="button" class="button-secondary hmbkp_preview_exclude_rule"><?php _e( 'Preview', 'hmbkp' ); ?></button>

                <span class="howto"><?php printf( __( 'Enter new exclude rules as a comma separated list, e.g. %s', 'hmbkp' ), '<code>.git/, *.mp3, wp-content/uploads/</code>' ); ?></span>

            </label>

        </div>

        <table class="widefat">

        	<thead>
        		<tr>
        			<th><?php _e( 'Exclude Rules', 'hmbkp' ); ?></th>
        		</tr>
        	</thead>

        	<tbody>

    <?php foreach( $schedule->get_excludes() as $key => $exclude ) : ?>

    			<tr>
    			    <td data-hmbkp-exclude-rule="<?php esc_attr_e( $exclude ); ?>">

    			    	<span class="code"><?php esc_attr_e( str_ireplace( untrailingslashit( $schedule->get_root() ), '', $exclude ) ); ?></span>

    	<?php if ( $schedule->get_path() === untrailingslashit( $exclude ) ) : ?>

    					<span class="reason"><?php _e( 'default', 'hmbkp' ); ?></span>

    	<?php elseif ( defined( 'HMBKP_EXCLUDE' ) && strpos( HMBKP_EXCLUDE, $exclude ) !== false ) : ?>

    					<span class="reason"><?php _e( 'defined', 'hmbkp' ); ?></span>

    	<?php else : ?>

    					<a href="#" class="delete-action"><?php _e( 'Remove', 'hmbkp' ); ?></a>

    	<?php endif; ?>

    				</td>
    			</tr>

    <?php endforeach; ?>

        	</tbody>

        </table>

        <div class="hmbkp-tabs">

        	<ul class="subsubsub">

	<?php if ( $schedule->get_excluded_file_count() ) : ?>

        		<li><a href="#hmbkp_excluded_files"><?php _e( 'Excluded', 'hmbkp' ); ?></a>(<?php esc_html_e( $schedule->get_excluded_file_count() ); ?>)</li>

    <?php endif; ?>

        		<li><a href="#hmbkp_included_files"><?php _e( 'Included', 'hmbkp' ); ?></a>(<?php esc_html_e( $schedule->get_included_file_count() ); ?>)</li>

    <?php if ( $schedule->get_unreadable_file_count() ) : ?>

                <li><a href="#hmbkp_unreadable_files"><?php _e( 'Unreadable', 'hmbkp' ); ?></a>(<?php esc_html_e( $schedule->get_unreadable_file_count() ); ?>)</li>

    <?php endif; ?>

        	</ul>

	<?php if ( $schedule->get_excluded_file_count() ) : ?>

        	<div id="hmbkp_excluded_files">

        		<?php hmbkp_file_list( $schedule, null, 'get_excluded_files' ); ?>

        	</div>

    <?php endif; ?>

        	<div id="hmbkp_included_files">

        		<?php hmbkp_file_list( $schedule, null, 'get_included_files' ); ?>

        	</div>

    <?php if ( $schedule->get_unreadable_file_count() ) : ?>

            <div id="hmbkp_unreadable_files">

                <?php hmbkp_file_list( $schedule, null, 'get_unreadable_files' ); ?>

                <p class="description"><?php _e( 'Unreadable files can\'t be backed up', 'hmbkp' ); ?></p>

            </div>

    <?php endif; ?>

        <p><?php printf( __( 'Your site is %s. Backups will be compressed and so will be smaller.', 'hmbkp' ), '<code>' . esc_html( $schedule->get_formatted_file_size( false ) ) . '</code>' ); ?></p>

        </div>

        <p class="submit">

            <button type="submit" class="button-primary"><?php _e( 'Close', 'hmbkp' ); ?></button>

        </p>

    </fieldset>

</form>