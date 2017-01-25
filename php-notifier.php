<?php
/*
 * Plugin Name: PHP Notifier
 * Plugin URI:  https://wordpress.org/plugins/rename-wp-login/
 * Description: Notify user when current version of PHP is marked inactive
 * Version:     1.0.0
 * Author:      Code Parrots
 * Author URI:  https://www.codeparrots.com
 * Text Domain: php-notifier
 * License:     GPL-2.0+
 */

class CP_PHP_Notifier {

	/**
	 * PHP Version
	 *
	 * @var integer
	 */
	private $php_version;

	/**
	 * PHP Support Data
	 *
	 * @var array
	 */
	private $php_support_data;

	/**
	 * Warning type
	 *
	 * @var string
	 */
	private $warning_type;

	public function __construct() {

		$this->php_version = phpversion();

		$this->php_support_data = $this->php_notifier_version_info();

		add_action( 'admin_init', [ $this, 'php_notifier_cross_check_data' ] );

	}

	/**
	 * Cross check the installed PHP version with PHP.net support versions
	 *
	 * @return mixed
	 */
	public function php_notifier_cross_check_data() {

		if ( ! $this->php_support_data ) {

			return;

		}

		// PHP Version is deprecated
		//  - Less than last PHP version supported
		//  - Current time is greater than or equal to "Security Support Until"
		if (
			version_compare( $this->php_version, key( $this->php_support_data ), '<' ) ||
			strtotime( 'now' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['security_until']
		) {

			$this->warning_type = 'deprecated';

			add_action( 'admin_notices', [ $this, 'php_version_error' ] );

			return;

		}

		if ( isset( $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ] ) ) {

			// PHP Version no longer supported
			// Current time is greater than or equal to "Active Support Until" AND
			// Current time is less than or equal to "Security Support Until"
			if (
				strtotime( 'now' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['supported_until'] &&
				strtotime( 'now' ) <= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['security_until']
			) {

				$this->warning_type = 'unsupported';

				add_action( 'admin_notices', [ $this, 'php_version_error' ] );

				return;

			} // @codingStandardsIgnoreLine

			// PHP Version will not actively be supported in 1 month or less
			if ( strtotime( '+1 month' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['supported_until'] ) {

				$this->warning_type = 'deprecated-soon';

				add_action( 'admin_notices', [ $this, 'php_version_error' ] );

			} // @codingStandardsIgnoreLine

		}

	}

	/**
	 * Display an admin notice about the PHP version
	 *
	 * @return mixed
	 */
	public function php_version_error() {

		$type = 'error';

		switch ( $this->warning_type ) {

			default:
			case 'deprecated':

				$message = __( 'You are running PHP %s, which is deprecated and no longer supported. This is a major security issue and should be addressed immediately. It is highly recommended that you update the version of PHP on your hosting account.', 'php-notifier' );

				break;

			case 'unsupported':

				$message = __( 'You are running PHP %s, which is no longer actively supported. It will still receive security updates, but its recommended that you upgrade your version of PHP.', 'php-notifier' );

				break;

			case 'deprecated-soon':

				$type = 'info';

				$message = __( 'The version of PHP that you have installed (%s) will no longer be supported in 1 month or less. Please update now to avoid any security issues.', 'php-notifier' );

				break;

		}

		printf(
			'<div class="notice notice-%1$s">
				<p>%2$s</p>
			</div>',
			$type,
			sprintf(
				$message,
				wp_kses_post( '<strong>v' . $this->php_version . '</strong>' )
			)
		);

	}

	/**
	 * Get the PHP verison info
	 *
	 * @return array
	 */
	public function php_notifier_version_info() {

		delete_transient( 'php_notifier_verison_info' );

		if ( WP_DEBUG || false === ( $php_version_info = get_transient( 'php_notifier_verison_info' ) ) ) {

			$contents = wp_remote_get( 'http://php.net/supported-versions.php' );

			if ( is_wp_error( $contents ) ) {

				return false;

			}

			$body = wp_remote_retrieve_body( $contents );

			$dom = new DOMDocument;

			// Surpress PHP HTML5 tag warnings
			libxml_use_internal_errors( true );

			$dom->loadHTML( $body );

			$tr = $dom->getElementsByTagName( 'tr' );

			$column_text = [];

			$x = 1;

			foreach ( $tr as $row ) {

				$columns = $row->getElementsByTagName( 'td' );

				foreach ( $columns as $column ) {

					$column_text[ $x ] = trim( str_replace( '*', '', $column->textContent ) );

					$x++;

				} // @codingStandardsIgnoreLine

			}

			$column_text = array_chunk( $column_text, 7 );

			unset( $column_text[3] );

			$php_version_info = [];

			$y = 0;

			foreach ( $column_text as $php_info ) {

				$php_version_info[ $php_info[0] ] = [
					'released'        => strtotime( $php_info[1] ),
					'supported_until' => strtotime( $php_info[3] ),
					'security_until'  => strtotime( $php_info[5] ),
				];

				$y++;

			}

			set_transient( 'php_notifier_verison_info', $php_version_info, 12 * HOUR_IN_SECONDS );

		}

		return $php_version_info;

	}

}

$cp_php_notifier = new CP_PHP_Notifier();




/* Options Page */


// create custom plugin settings menu
add_action('admin_menu', 'pn_create_menu');

function pn_create_menu() {

	//create new top-level menu
	add_menu_page('My Cool Plugin Settings', 'PHP Notifier', 'administrator', __FILE__, 'pn_settings_page' , plugins_url('/images/icon.png', __FILE__) );

	//call register settings function
	add_action( 'admin_init', 'register_my_cool_plugin_settings' );
}


function pn_settings() {
	//register our settings
	register_setting( 'pn-email-enabled', 'new_option_name' );
	register_setting( 'pn-periodic-emails', 'some_other_option' );
	register_setting( 'pn-periodic-email-value', 'option_etc' );
}

function pn_settings_page() {
?>
<div class="wrap">
<h1>Your Plugin Name</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'my-cool-plugin-settings-group' ); ?>
    <?php do_settings_sections( 'my-cool-plugin-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Send Email to Admin?</th>
        <td><input type="text" name="new_option_name" value="<?php echo esc_attr( get_option('new_option_name') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Send Emails periodically?</th>
        <td><input type="checkbox" name="some_other_option" value="<?php echo esc_attr( get_option('some_other_option') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">How Frequently should Emails be sent?.</th>
        <td><input type="text" name="option_etc" value="<?php echo esc_attr( get_option('option_etc') ); ?>" /></td>
        </tr>
    </table>

    <?php submit_button(); ?>

</form>
</div>
<?php } ?>
