<?php

class HM_Backdrop_Task {
	protected $key;
	protected $callback;
	protected $params = array();

	public function __construct( $callback /* , $... */ ) {
		$this->callback = $callback;

		if ( func_num_args() > 1 ) {
			$args = func_get_args();
			$this->params = array_slice( $args, 1 );
		}

		$this->key = $this->get_unique_id();
	}

	public function schedule() {

		if ( $this->is_scheduled() ) {
			return new WP_Error( 'hm_backdrop_scheduled', __( 'Task is already scheduled to run', 'hm_backdrop' ) );
		}

		$data = array(
			'callback' => $this->callback,
			'params' => $this->params
		);
		set_transient( 'hm_backdrop-' . $this->key, $data, 300 );
		add_action( 'shutdown', array( $this, 'spawn_server' ) );

		return true;
	}

	public function is_scheduled() {
		return (bool) $this->get_data();
	}

	public function cancel() {
		if ( ! $this->is_scheduled() ) {
			return new WP_Error( 'hm_backdrop_not_scheduled', __( 'Task is not scheduled to run', 'hm_backdrop' ) );
		}

		delete_transient( 'hm_backdrop-' . $this->key );
		return true;
	}

	public function spawn_server() {
		$server_url = admin_url( 'admin-ajax.php' );
		$data = array(
			'action' => 'hm_backdrop_run',
			'key'    => $this->key,
		);
		$args = array(
			'body' => $data,
			'timeout' => 0.01,
			'blocking' => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);
		wp_remote_post( $server_url, $args );
		return true;
	}

	protected function get_data() {
		return get_transient( 'hm_backdrop-' . $this->key );
	}

	protected function get_unique_id() {
		return substr( sha1( serialize( $this->callback ) . serialize( $this->params ) ), -28 );
	}
}
