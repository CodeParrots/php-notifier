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

		define( 'PHP_NOTIFIER_PATH',    plugin_dir_path( __FILE__ ) );
		define( 'PHP_NOTIFIER_URL',     plugin_dir_url( __FILE__ ) );
		define( 'PHP_NOTIFIER_VERSION', '1.0.0' );

		$this->php_version = phpversion();

		$this->php_support_data = $this->php_notifier_version_info();

		$this->init();

	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'admin_init', [ $this, 'php_notifier_cross_check_data' ] );

		include_once( plugin_dir_path( __FILE__ ) . '/library/partials/class-options.php' );

		include_once( plugin_dir_path( __FILE__ ) . '/library/partials/class-filters.php' );

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

			$this->php_version_error();

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

				$this->php_version_error();

				return;

			} // @codingStandardsIgnoreLine

			// PHP Version will not actively be supported in 1 month or less
			if ( strtotime( '+1 month' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['supported_until'] ) {

				$this->warning_type = 'deprecated-soon';

				$this->php_version_error();

			} // @codingStandardsIgnoreLine

		}

	}

	/**
	 * Display an admin notice about the PHP version
	 *
	 * @return mixed
	 */
	public function php_version_error( $echo = true ) {

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

		$notice = sprintf(
			'<div class="notice notice-%1$s">
				<p>%2$s</p>
			</div>',
			$type,
			sprintf(
				$message,
				wp_kses_post( '<strong>v' . $this->php_version . '</strong>' )
			)
		);

		if ( $echo ) {

			echo $notice;

			return;

		}

		return $notice;

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

			$body = str_replace( '<link rel="shortcut icon" href="http://php.net/favicon.ico">', '', wp_remote_retrieve_body( $contents ) );

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

					$column_text[ $x ] = trim( str_replace( '*', '', $column->textContent ) ); // @codingStandardsIgnoreLine

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
