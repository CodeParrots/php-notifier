<?php
/**
 * Settings Page
 *
 * @since 1.0.0
 *
 * @author Code Parrots <support@codeparrots.com>
 */
class PHP_Notifier_Settings {

	/**
	 * Options
	 *
	 * @var 1.0.0
	 */
	private $options;

	/**
	 * PHP Version
	 *
	 * @var 1.0.0
	 */
	private $php_version;

	/**
	 * PHP Version Error
	 *
	 * @var 1.0.0
	 */
	private $php_version_error;

	public function __construct( $options, $php_version, $php_version_error ) {

		$this->options = $options;

		$this->php_version = $php_version;

		$this->php_version_error = $php_version_error;

		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );

		add_action( 'admin_init', array( $this, 'page_init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'page_styles' ) );

	}

	/**
	* Add options page
	*
	* @since 1.0.0
	*/
	public function add_plugin_page() {

		add_options_page(
			__( 'PHP Notifier Settings', 'php-notifier' ),
			__( 'PHP Notifier', 'php-notifier' ),
			'manage_options',
			'php-notifier',
			array( $this, 'create_admin_page' )
		);

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

		wp_mail( get_option( 'admin_email' ), 'PHP Notifier Update', $message );

	}

	/**
	* Options page callback
	*
	* @since 1.0.0
	*/
	public function create_admin_page() {

		?>

			<div class="wrap">

				<h1><?php esc_html_e( 'PHP Notifier', 'php-notifier' ); ?></h1>

				<form method="post" action="options.php">

					<?php

						printf(
							'<div class="notice notice-info"><p>%s</p></div>',
							sprintf(
								esc_html__( 'The PHP version running on this server: %s' ),
								wp_kses_post( '<span class="php-version">' . $this->php_version . '</span>' )
							)
						);

						settings_fields( 'php_notifier_settings_group' );

						do_settings_sections( 'php-notifier' );

						submit_button();

					?>

				</form>

			</div>

		<?php
	}

	/**
	* Register and add settings
	*
	* @since 1.0.0
	*/
	public function page_init() {

		register_setting(
			'php_notifier_settings_group',
			'php_notifier_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'setting_section_id',
			__( 'General Settings', 'php-notifier' ),
			array( $this, 'print_section_info' ),
			'php-notifier'
		);

		add_settings_field(
			'send_email',
			esc_html__( 'Send Email Notification?', 'php-notifier' ),
			array( $this, 'send_email_callback' ),
			'php-notifier',
			'setting_section_id'
		);

		add_settings_field(
			'email_frequency',
			esc_html__( 'Email Frequency', 'php-notifier' ),
			array( $this, 'email_frequency_callback' ),
			'php-notifier',
			'setting_section_id'
		);

	}

	/**
	 * Enqueue the admin stylesheet
	 *
	 * @since 1.0.0
	 */
	public function page_styles() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'php-notifier-style', PHP_NOTIFIER_URL . "library/css/style{$suffix}.css", array(), PHP_NOTIFIER_VERSION, 'all' );

	}

	/**
	* Sanitize each setting field as needed
	*
	* @param array $input Contains all settings fields as array keys
	*
	* @since 1.0.0
	*/
	public function sanitize( $input ) {

		$new_input = [];

		$new_input['warning_type']     = $this->options['warning_type'];
		$new_input['send_email']       = (bool) empty( $input['send_email'] ) ? false : true;
		$new_input['email_frequency']  = isset( $input['email_frequency'] ) ? sanitize_text_field( $input['email_frequency'] ) : 'Never';

		$this->email_cron();

		return $new_input;

	}

	/**
	* Print the Section text
	*
	* @since 1.0.0
	*/
	public function print_section_info() {

		esc_html_e( 'Adjust the settings below:', 'php-notifier' );

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function send_email_callback() {

		printf(
			'<input type="checkbox" id="send_email" name="php_notifier_settings[send_email]" value="1" %s />',
			checked( 1, $this->options['send_email'], false )
		);

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function email_frequency_callback() {

		$options = array(
			'never'   => __( 'Never', 'php-notifier' ),
			'daily'   => __( 'Daily', 'php-notifier' ),
			'weekly'  => __( 'Weekly', 'php-notifier' ),
			'monthly' => __( 'Monthly', 'php-notifier' ),
		);

		print( '<select name="php_notifier_settings[email_frequency]">' );

		foreach ( $options as $value => $label ) {

			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $this->options['email_frequency'], $value ),
				esc_html( $label )
			);

		}

		print( '</select>' );

	}
}

if ( is_admin() ) {

	$php_notifier_settings = new PHP_Notifier_Settings( $this->options, $this->php_version, $this->php_version_error( false ) );

}
