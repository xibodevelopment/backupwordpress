<?php

// Import the HM Backups unit tests
foreach ( glob( dirname( dirname( __FILE__ ) ) . '/hm-backup/tests/*.php' ) as $filename )
	include ( $filename );