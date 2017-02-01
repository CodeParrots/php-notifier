<?php
/**
 * Email cron functionality
 *
 * @since 1.0.0
 *
 * @author Code Parrots <support@codeparrots.com>
 */
class PHP_Notifier_Email_Cron extends CP_PHP_Notifier {

	public function __construct() {

		add_filter( 'cron_schedules',          array( $this, 'custom_cron_schedules' ) );
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

		$prevent_email_cron = get_option( 'php_notifier_prevent_cron', false );

		if ( ! self::$options['send_email'] || $prevent_email_cron ) {

			update_option( 'php_notifier_prevent_cron', false );

			return;

		}

		$error_message = ! $this->php_version_error( false ) ? sprintf( __( '%s You are running a supported version of PHP.', 'php-notifier' ), '☑' ) : '☒ ' . wp_strip_all_tags( $this->php_version_error( false ) );

		$message = sprintf(
			__( 'The version of PHP running on the server hosting %1$s is PHP  %2$s. %3$s', 'php-notifier' ),
			esc_html( get_site_url() ),
			esc_html( self::$php_version ),
			"\r\n\r\n" . esc_html( $error_message )
		);

		wp_mail( get_option( 'admin_email' ), __( 'PHP Notifier Update', 'php-notifier' ), $message );

	}

}

$php_notifier_email_cron = new PHP_Notifier_Email_Cron();
