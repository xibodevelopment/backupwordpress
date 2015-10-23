<?php
namespace HM\BackUpWordPress;

class Task_Manager {

	/**
	 * Holds an instance of the class.
	 *
	 * @var Task_Manager
	 */
	private static $_instance;

	/**
	 * Array of Backdrop tasks.
	 *
	 * @var
	 */
	protected $tasks = array();

	/**
	 * Constructor.
	 */
	private function __construct() {

		$this->tasks = get_site_option( 'hmbkp_tasks', array() );
	}

	/**
	 * Returns the singleton instance of this class.
	 * @return Task_Manager
	 */
	public static function get_instance() {

		if ( ! ( self::$_instance instanceof Task_Manager ) ) {
			self::$_instance = new Task_Manager();
		}

		return self::$_instance;

	}

	/**
	 * Returns a Backdrop task corresponding to the schedule ID.
	 * @param $schedule_id
	 *
	 * @return mixed
	 */
	public function get_task( $schedule_id ) {

		return $this->tasks[ $schedule_id ];
	}

	/**
	 * Adds a Task to the list, indexed by schedule ID.
	 * @param                   $schedule_id
	 * @param \HM_Backdrop_Task $task
	 */
	public function add_task( $schedule_id, \HM_Backdrop_Task $task ) {
		$this->tasks[ $schedule_id ] = $task;
		update_site_option( 'hmbkp_tasks', $this->tasks );
	}

	/**
	 * Deletes a task.
	 * @param $schedule_id
	 */
	public function remove_task( $schedule_id ) {
		unset( $this->tasks[ $schedule_id ] );
		update_site_option( 'hmbkp_tasks', $this->tasks );
	}
}
