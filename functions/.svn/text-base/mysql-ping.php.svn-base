<?php

/**
* "MySQL Ping" Keeps mysql connections alive
*
* Extend the default {@link wpdb} class by
* adding {@link mysql_ping()} capabilities
*
* @author Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
Class wpdb2 Extends wpdb {

	/**
	* Attemp to ping the MySQL server
	*/
	function _ping() {

		$retry = 5;
		$failed = 1;

		// probe w\ a ping
		$ping = mysql_ping( $this->dbh ) ;

		while( !$ping && $failed < $retry ) :

			// Reconnect
			$this->dbh = mysql_connect( DB_HOST, DB_USER, DB_PASSWORD, 1 );
			$this->select( DB_NAME );

			if ( !DB_CHARSET && version_compare( mysql_get_server_info( $this->dbh ), '4.1.0', '>=' ) )
				$this->query( "SET NAMES '" . DB_CHARSET . "'" );

			// Ping again to check the result
			$ping = mysql_ping( $this->dbh ) ;

			if ( !$ping ) {
				sleep(2);
				$failed+=1;
			}

		endwhile;

		// Ping failed
		if ( !$ping )
			$this->print_error( 'Attempted to connect for ' . $retry . ' but failed...' );

	}

	/**
	* Override the original {@link wpdb::query()} method in
	* order to ping the server before executing every query
	*
	* @param string $query
	* @return mixed
	*/
	function query( $query ) {

		$this->_ping();

		return parent::query( $query );

	}

}

/**
 * If mysql.max_links is 2 or less and we're using the mysqldump fallback
 * then we need the second link for the backup so we can't include
 * mysql_ping.
 */
if ( ini_get( 'mysql.max_links' > 2 ) && !hmbkp_mysqldump_path() ) :

	// Setup the wpdb2 class
	$wpdb2 = new wpdb2( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	
	// Copy over the $wpdb vars
	global $wpdb;
	foreach( get_object_vars( $wpdb ) as $k => $v )
	    if ( is_scalar( $v ) )
	    	$wpdb2->$k = $v;
	
	$wpdb =& $wpdb2;

endif;