<?php
/**
 * Settings Page
 *
 * @since 1.0.0
 *
 * @author Code Parrots <support@codeparrots.com>
 */
class PHP_Notifier_Email_Cron {

	/**
	 * Options
	 *
	 * @var array
	 */
	private $options;

	/**
	 * PHP Version
	 *
	 * @var string
	 */
	private $php_version;

	/**
	 * PHP Version Error
	 *
	 * @since string
	 */
	private $php_version_error;

	public function __construct( $options, $php_version, $php_version_error ) {

		$this->options = $options;

		$this->php_version = $php_version;

		$this->php_version_error = $php_version_error;

		add_filter( 'cron_schedules', array( $this, 'custom_cron_schedules' ) );

		add_action( 'php_notifier_email_cron', array( $this, 'email_cron' ) );

	}

	/**
	 * Setup custom
	 *
	 * @param  array $schedules Defined cron schedules
	 *
	 * @return array
	 */
	public function custom_cron_schedules( $schedules ) {

		if ( ! isset( $schedules['weekly'] ) ) {

			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Per Week' ),
			);

		}

		if ( ! isset( $schedules['monthly'] ) ) {

			$schedules['monthly'] = array(
				'interval' => 2628000,
				'display'  => __( 'Once Per Month' ),
			);

		}

		return $schedules;

	}

	/**
	 * Send the PHP info email.
	 *
	 * @return bool
	 */
	public function email_cron() {

		if ( ! $this->options['send_email'] ) {

			return;

		}

		$error_message = ! $this->php_version_error ? sprintf( __( '%s You are running a supported version of PHP.', 'php-notifier' ), '☑' ) : '☒ ' . wp_strip_all_tags( $this->php_version_error );

		$message = sprintf(
			'The version of PHP running on the server hosting %1$s is PHP  %2$s.' . "\r\n\r\n" . '%3$s',
			esc_html( get_site_url() ),
			esc_html( $this->php_version ),
			esc_html( $error_message )
		);

		wp_mail( get_option( 'admin_email' ), __( 'PHP Notifier Update', 'php-notifier' ), $message );

	}

}

$php_notifier_email_cron = new PHP_Notifier_Email_Cron( $this->options, $this->php_version, $this->php_version_error( false ) );
