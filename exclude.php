<?php 

// Exclude the back up path
echo hmbkp_path() . "*\n";

// Exclude the default back up path
echo hmbkp_path_default() . "*\n"

// Exclude the custom path if one is defined
if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
	echo hmbkp_conform_dir( HMBKP_PATH ) . "*\n";