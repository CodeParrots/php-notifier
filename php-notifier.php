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
	protected static $php_version;

	/**
	 * PHP Support Data
	 *
	 * @var array
	 */
	protected $php_support_data;

	/**
	 * PHP Notifier Settings Array
	 *
	 * @var array
	 */
	protected static $options;

	public function __construct() {

		define( 'PHP_NOTIFIER_PATH',    plugin_dir_path( __FILE__ ) );
		define( 'PHP_NOTIFIER_URL',     plugin_dir_url( __FILE__ ) );
		define( 'PHP_NOTIFIER_VERSION', '1.0.0' );

		self::$php_version = phpversion();

		if ( version_compare( PHP_VERSION, '5.2.7', '<' ) ) {

			add_action( 'admin_notices', array( $this, 'php_min_version_warning' ) );

			return;

		}

		self::$options = get_option( 'php_notifier_settings', array(
			'send_email'      => true,
			'email_frequency' => 'monthly',
			'warning_type'    => false,
			'dismiss_notice'  => false,
			'php_version'     => self::$php_version,
		) );

		$this->php_support_data = $this->php_notifier_version_info();

		$this->init();

	}

	/**
	 * Display a notice back to the user about the minimum requirements for this plugin
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function php_min_version_warning() {

		printf(
			'<div class="notice php-notifier-notice notice-error">
				<p>%s</p>
			</div>',
			sprintf(
				esc_html__( 'PHP Notifier requires PHP version 5.2.7 or later. Your site is running %s. Please upgrade PHP to a later version or uninstall PHP notifier to remove this warning.', 'php-notifier' ),
				PHP_VERSION
			)
		);

	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// abort if doing an ajax request
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			add_action( 'wp_ajax_php_notifier_dismiss_notice', array( $this, 'dismiss_admin_notice' ) );

			return;

		}

		add_action( 'admin_init', array( $this, 'php_notifier_cross_check_data' ) );

		add_action( 'admin_notices', array( $this, 'php_version_error' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notifier_notice_scrpits' ) );

		include_once( plugin_dir_path( __FILE__ ) . '/library/partials/class-options.php' );

		include_once( plugin_dir_path( __FILE__ ) . '/library/partials/class-filters.php' );

		include_once( plugin_dir_path( __FILE__ ) . '/library/partials/class-email-cron.php' );

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

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
			version_compare( self::$php_version, key( $this->php_support_data ), '<' ) ||
			strtotime( 'now' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['security_until']
		) {

			$this->set_warning_type( 'deprecated' );

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

				$this->set_warning_type( 'unsupported' );

				return;

			}

			// PHP Version will not actively be supported in 1 month or less
			if ( strtotime( '+1 month' ) >= $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['supported_until'] ) {

				$this->set_warning_type( 'deprecated-soon' );

			}

			$this->set_warning_type( false );

		}

	}

	/**
	 * Set the warning type
	 *
	 * @param string $type The type of warning
	 *        possible: deprecated, unsupported, deprecated-soon
	 *
	 * @since 1.0.0
	 */
	public function set_warning_type( $type = false ) {

		self::$options['warning_type'] = $type;

		update_option( 'php_notifier_settings', self::$options );

	}

	/**
	 * Display an admin notice about the PHP version
	 *
	 * @return mixed
	 */
	public function php_version_error() {

		if (
			( ! isset( self::$options['warning_type'] ) || ! self::$options['warning_type'] )
			|| self::$options['dismiss_notice']
		) {

			return;

		}

		$notice_array = array(
			'type'            => $this->get_error_notice_class( self::$options['warning_type'] ),
			'dismisslbe'      => 'deprecated' === self::$options['warning_type'] ? false : true,
			'supported_until' => isset( $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ] ) ? $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['supported_until'] : false,
			'security_until'  => isset( $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ] ) ? $this->php_support_data[ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ]['security_until'] : false,
			'message'         => $this->get_php_error_message(),
			'additional'      => '',
		);

		if ( $notice_array['supported_until'] ) {

			$notice_array['additional'] = $this->get_additional_notice_content( 'supported_until', $notice_array['supported_until'] );

		}

		if ( $notice_array['security_until'] ) {

			$notice_array['additional'] = $this->get_additional_notice_content( 'security_until', $notice_array['security_until'] );

		}

		printf(
			'<div class="notice php-notifier-notice notice-%1$s %2$s">
				<p>%3$s</p>
				%4$s
			</div>',
			$notice_array['type'],
			$notice_array['dismissible'] ? 'is-dismissible' : '',
			sprintf(
				$notice_array['message'],
				wp_kses_post( '<strong>v' . self::$php_version . '</strong>' )
			),
			wp_kses_post( $notice_array['additional'] )
		);

	}

	/**
	 * Enqueue the PHP notifier notice scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_notifier_notice_scrpits() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'php-notifier-notices', PHP_NOTIFIER_URL . "library/js/php-notifier-notices{$suffix}.js", array( 'jquery' ), PHP_NOTIFIER_VERSION, true );

		wp_localize_script( 'php-notifier-notices', 'php_notifier', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

	}

	/**
	 * Return the admin notice error class
	 *
	 * @param  string $warning_type The warning type to return.
	 *
	 * @return string
	 */
	public function get_error_notice_class( $warning_type ) {

		$type_array = array(
			'deprecated'      => 'error',
			'deprecated-soon' => 'info',
			'unsupported'     => 'warning',
		);

		return $type_array[ $warning_type ];

	}

	/**
	 * Return the additional notice content
	 *
	 * @param  string $type      The type of additional notice to retrive supported|security
	 * @param  string $timestamp The timestamp value to use in the comparison.
	 *
	 * @return mixed
	 */
	public function get_additional_notice_content( $type, $data ) {

		switch ( $type ) {

			default:
			case 'supported_until':

					$string = strtotime( 'now' ) > $notice_array['supported_until'] ? __( 'PHP %1$s was officially no longer supported on %2$s.', 'php-notifier' ) : __( 'PHP %1$s will no longer be supported on %2$s.', 'php-notifier' );

				break;

			case 'security_until':

				$string = strtotime( 'now' ) > $notice_array['security_until'] ? __( 'PHP %1$s stopped receiving security updates on %2$s.', 'php-notifier' ) : __( 'PHP %1$s will no longer receive security updates on %2$s.', 'php-notifier' );

				break;

		}

		return sprintf(
			'<p>' . esc_html( $string ) . '</p>',
			esc_html( 'v' . self::$php_version ),
			date( get_option( 'date_format' ), $notice_array[ $type ] )
		);

	}

	/**
	 * Return the PHP error message to use
	 *
	 * @param  string $warning_type The warning type to display.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_php_error_message() {

		if ( ! self::$options['warning_type'] ) {

			return;

		}

		$warning_messages = array(
			'deprecated'      => __( 'You are running PHP %s, which is deprecated and no longer supported. This is a major security issue and should be addressed immediately. It is highly recommended that you update the version of PHP on your hosting account.', 'php-notifier' ),
			'deprecated-soon' => __( 'You are running PHP %s, which is no longer actively supported. It will still receive security updates, but its recommended that you upgrade your version of PHP.', 'php-notifier' ),
			'unsupported'     => __( 'The version of PHP that you have installed (%s) will no longer be supported in 1 month or less. Please update now to avoid any security issues.', 'php-notifier' ),
		);

		return apply_filters( 'php_notifier_warning_message', $warning_messages[ self::$options['warning_type'] ], self::$options['warning_type'] );

	}

	/**
	 * AJAX Handler for the dismissible notices
	 *
	 * @return boolean True|False based on if the option was updaed.
	 *
	 * @since 1.0.0
	 */
	public function dismiss_admin_notice() {

		self::$options['dismiss_notice'] = true;

		update_option( 'php_notifier_settings', self::$options );

		wp_die();

	}

	/**
	 * Get the PHP verison info
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function php_notifier_version_info() {

		if ( WP_DEBUG || false === ( $php_version_info = get_transient( 'php_notifier_verison_info' ) ) ) {

			if ( phpversion() !== self::$options['php_version'] ) {

				self::$options['dismiss_notice'] = false;
				self::$options['php_version']    = phpversion();

				update_option( 'php_notifier_settings', self::$options );

			}

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

			$column_text = array();

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

			$php_version_info = array();

			$y = 0;

			foreach ( $column_text as $php_info ) {

				$php_version_info[ $php_info[0] ] = array(
					'released'        => strtotime( $php_info[1] ),
					'supported_until' => strtotime( $php_info[3] ),
					'security_until'  => strtotime( $php_info[5] ),
				);

				$y++;

			}

			set_transient( 'php_notifier_verison_info', $php_version_info, 12 * HOUR_IN_SECONDS );

		}

		return $php_version_info;

	}

	/**
	 * Plugin Activation
	 *
	 * @since 1.0.0
	 */
	public function plugin_activation() {

		if ( wp_next_scheduled( 'cp_php_notifier_email' ) || ! self::$options['email_frequency'] ) {

			return;

		}

		update_option( 'php_notifier_prevent_cron', true );

		wp_schedule_event( time(), self::$options['email_frequency'], 'php_notifier_email_cron' );

	}

	/**
	 * Plugin Deactivation
	 *
	 * @since 1.0.0
	 */
	public function plugin_deactivation() {

		wp_clear_scheduled_hook( 'php_notifier_email_cron' );

	}

}

$cp_php_notifier = new CP_PHP_Notifier();
