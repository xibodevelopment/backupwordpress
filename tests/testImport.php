<?php

// Import the HM Backup unit tests
foreach ( glob( dirname( dirname( __FILE__ ) ) . '/hm-backup/tests/*.php' ) as $filename )
	include ( $filename );