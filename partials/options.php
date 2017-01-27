<?php
/**
 * Settings Page
 *
 * @since 1.0.0
 *
 * @author Code Parrots <support@codeparrots.com>
 */
class PHPNotifier_Settings {

	private $options;

	public function __construct() {

		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );

		add_action( 'admin_init', [ $this, 'page_init' ] );

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
			[ $this, 'create_admin_page' ]
		);

	}

	/**
	* Options page callback
	*
	* @since 1.0.0
	*/
	public function create_admin_page() {

		$this->options = get_option( 'phpnotifier_settings' );

		?>

			<div class="wrap">

				<h1><?php esc_html_e( 'PHP Notifier', 'php-notifier' ); ?></h1>

				<form method="post" action="options.php">

					<br />
					<h2 class="php_notifier_version_title"> The PHP Version running on this server: <span class="php_notifier_version_number"> <?php echo phpversion(); ?> </span> </h2>
					<br />
					<br />

					<?php
						settings_fields( 'phpnotifier_settings_group' );
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
			'phpnotifier_settings_group',
			'phpnotifier_settings',
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'setting_section_id',
			__( 'General Settings', 'php-notifier' ),
			[ $this, 'print_section_info' ],
			'php-notifier'
		);

		add_settings_field(
			'php_notifier_send_email',
			__( 'Send Email Notification?', 'php-notifier' ),
			[ $this, 'php_notifier_send_email_callback' ],
			'php-notifier',
			'setting_section_id'
		);

		add_settings_field(
			'php_notifier_how_often',
			__( 'How Often?<br />\'Never\', \'Daily\', \'Weekly\', \'Monthly\', \'On Update\',', 'php-notifier' ),
			[ $this, 'php_notifier_how_often_callback' ],
			'php-notifier',
			'setting_section_id'
		);

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

		$new_input['phpnotifier_send_email'] = isset( $input['phpnotifier_send_email'] ) ? absint( $input['phpnotifier_send_email'] ) : '';
	/*	$new_input['pn_how_often']     = isset( $input['pn_how_often'] ) ? sanitize_text_field( $input['pn_how_often'] ) : ''; */
		$new_input['php_notifier_how_often'] = empty( $input['php_notifier_how_often'] ) ? 'Never' : $input['php_notifier_how_often'];

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
	public function php_notifier_send_email_callback() {

		printf(
			'<input type="text" id="phpnotifier_send_email" name="phpnotifier_settings[phpnotifier_send_email]" value="%s" />',
			isset( $this->options['phpnotifier_send_email'] ) ? esc_attr( $this->options['phpnotifier_send_email'] ) : ''
		);

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function php_notifier_how_often_callback() {

		printf(
			'<input type="text" id="pn_how_often" name="phpnotifier_settings[php_notifier_how_often]" value="%s" />',
			isset( $this->options['php_notifier_how_often'] ) ? esc_attr( $this->options['php_notifier_how_often'] ) : ''
		);

	}
}

if ( is_admin() ) {

	$php_notifier_settings = new PHPNotifier_Settings();

}
