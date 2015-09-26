<?php

namespace HM\BackUpWordPress;

/**
 * An abstract service class, individual services should
 * extend this class
 */
abstract class Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name;

	/**
	 * The instance Backup_Schedule that this service is
	 * is currently working with
	 *
	 * @var Scheduled_Backup
	 */
	protected $schedule;

	public function __construct( Scheduled_Backup $schedule ) {
		$this->set_schedule( $schedule );
	}

	/**
	 * Used to determine if the service is in use or not
	 *
	 * @return boolean
	 */
	abstract public function is_service_active();

	/**
	 * The form to output as part of the schedule settings
	 *
	 * If you don't want a whole form return ''; here and use @field instead
	 *
	 * @return string    The raw HTML for the form you want to output
	 */
	abstract public function form();

	/**
	 * The field to output as part of the schedule settings
	 *
	 * If you don't want a field return ''; here and use @form instead
	 *
	 * @return string    The raw HTML for the field you want to output
	 */
	abstract public function field();

	/**
	 * Help text that should be output in the Constants help tab
	 *
	 * @return string    The raw HTML for the Constant help text you want to output
	 */
	public static function constant() {}

	/**
	 * Validate and sanitize data before it's saved.
	 *
	 * @param  array &$new_data An array or data from $_GET, passed by reference so it can be modified,
	 * @param  array $old_data  The old data thats going to be overwritten
	 * @return array $error     Array of validation errors e.g. return array( 'email' => 'not valid' );
	 */
	abstract public function update( &$new_data, $old_data );

	/**
	 * The string to be output as part of the schedule sentence
	 *
	 * @return string
	 */
	abstract public function display();

	/**
	 * Receives actions from the backup
	 *
	 * This is where the service should do it's thing
	 *
	 * @see  Backup::do_action for a list of the actions
	 *
	 * @param $action
	 * @param Backup $backup
	 *
	 * @return mixed
	 */
	abstract public function action( $action, Backup $backup );

	public function get_slug() {
		return sanitize_key( $this->name );
	}

	/**
	 * Utility for getting a formated html input name attribute
	 *
	 * @param  string $name The name of the field
	 * @return string       The formated name
	 */
	protected function get_field_name( $name ) {
		return esc_attr( $this->get_slug() . '[' . $name . ']' );
	}

	/**
	 * Get the value of a field
	 *
	 * @param string $name The name of the field
	 * @param string $esc  The escaping function that should be used
	 * @return string
	 */
	protected function get_field_value( $name, $esc = 'esc_attr' ) {

		if ( $name && $this->schedule->get_service_options( $this->get_slug(), $name ) ) {
			return $esc( $this->schedule->get_service_options( $this->get_slug(), $name ) );
		}

		return '';

	}

	/**
	 * Save the settings for this service
	 *
	 * @return null|array returns null on success, array of errors on failure
	 */
	public function save() {

		$classname = $this->get_slug();

		$old_data = $this->schedule->get_service_options( $classname );

		$new_data = isset( $_POST[ $classname ] ) ? $_POST[ $classname ] : array();

		// $new_data is passed by ref, so it is clean after this method call.
		$errors = $this->update( $new_data, $old_data );

		if ( $errors && $errors = array_flip( $errors ) ) {

			foreach ( $errors as $error => &$field ) {
				$field = $this->get_slug() . '[' . $field . ']';
			}

			return array_flip( $errors );

		}

		// Only overwrite settings if they changed
		if ( ! empty( $new_data ) ) {
			$this->schedule->set_service_options( $classname, $new_data );
		}

		return array();

	}

	/**
	 * Set the current schedule object
	 *
	 * @param Scheduled_Backup $schedule An instantiated schedule object
	 */
	public function set_schedule( Scheduled_Backup $schedule ) {
		$this->schedule = $schedule;
	}

	/**
	 * Gets the settings for a similar destination from the existing schedules
	 * so that we can copy them into the form to avoid having to type them again
	 *
	 * @return array
	 */
	protected function fetch_destination_settings() {

		$service = $this->get_slug();

		$schedules_obj = Schedules::get_instance();

		$schedules = $schedules_obj->get_schedules();

		foreach ( $schedules as $schedule ) {

			if ( $schedule->get_id() != $this->schedule->get_id() ) {

				$options = $schedule->get_service_options( $service );

				if ( ! empty( $options ) ) {
					return $options;
				}

			}

		}

		return array();

	}

	/**
	 * @return boolean
	 */
	public function has_form() {

		ob_start();

		$this->form();

		return (bool) ob_get_clean();

	}

	/**
	 * Handles passing service specific data to Intercom
	 */
	public static function intercom_data() {}

	public static function intercom_data_html() {}

}
