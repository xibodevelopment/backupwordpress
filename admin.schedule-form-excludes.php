<fieldset class="hmbkp-edit-schedule-excludes-form">

    <legend>Manage Excludes</legend>

    <div class="hmbkp_add_exclude_rule">

    	<span class="screen-reader-text">New Rule</span>

    	<input type="text" placeholder="New Rule" />

    	<button type="button" class="button-secondary hmbkp_preview_exclude_rule">Preview</button>

    </div>

    <table class="widefat fixed">

    	<thead>
    		<tr>
    			<th>Exclude Rules</th>
    		</tr>
    	</thead>

    	<tbody>

<?php foreach( $schedule->get_excludes() as $key => $exclude ) : ?>

			<tr>
			    <td data-hmbkp-exclude-rule="<?php echo $exclude; ?>">

			    	<?php echo str_ireplace( untrailingslashit( ABSPATH ), '', $exclude ); ?>

	<?php if ( $key ) { ?>

					<a href="#">Delete</a>

	<?php } ?>

				</td>
			</tr>

<?php endforeach; ?>

    	</tbody>

    </table>

    <div class="hmbkp-tabs">

    	<ul class="subsubsub">

    		<li><a href="#hmbkp_excluded_files">Excluded</a>(<?php echo count( $schedule->get_excluded_files() ); ?>)</li>
    		<li><a href="#hmbkp_included_files">Included</a>(<?php echo count( $schedule->get_files() ); ?>)</li>

    	</ul>

    	<div id="hmbkp_excluded_files">

    		<?php hmbkp_file_list( $schedule, null, 'get_excluded_files' ); ?>

    	</div>

    	<div id="hmbkp_included_files">

    		<?php hmbkp_file_list( $schedule, null, 'get_files' ); ?>

    	</div>

    </div>

</fieldset>