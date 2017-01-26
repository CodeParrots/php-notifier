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
			'id_number',
			__( 'ID Number', 'php-notifier' ),
			[ $this, 'id_number_callback' ],
			'php-notifier',
			'setting_section_id'
		);

		add_settings_field(
			'title',
			__( 'Title', 'php-notifier' ),
			[ $this, 'title_callback' ],
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

		$new_input['id_number'] = isset( $input['id_number'] ) ? absint( $input['id_number'] ) : '';
		$new_input['title']     = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';

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
	public function id_number_callback() {

		printf(
			'<input type="text" id="id_number" name="phpnotifier_settings[id_number]" value="%s" />',
			isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number'] ) : ''
		);

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function title_callback() {

		printf(
			'<input type="text" id="title" name="phpnotifier_settings[title]" value="%s" />',
			isset( $this->options['title'] ) ? esc_attr( $this->options['title'] ) : ''
		);

	}
}

if ( is_admin() ) {

	$php_notifier_settings = new PHPNotifier_Settings();

}
