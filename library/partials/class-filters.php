<?php
/**
 * PHP Notifier filters
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class CP_PHP_Notifier_Filters extends CP_PHP_Notifier {

	public function __construct() {

		add_filter( 'admin_footer_text', array( $this, 'php_version_footer_text' ) );

	}

	/**
	 * PHP version in WordPress admin footer
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function php_version_footer_text( $footer_text ) {

		if ( ! apply_filters( 'php_notifier_admin_footer_text', true ) ) {

			return $footer_text;

		}

		return $footer_text . ' | ' . sprintf(
			__( 'The server hosting your site is running PHP version %s.', 'php-notifier' ),
			self::$php_version
		);

	}

}

$cp_php_notifier_filters = new CP_PHP_Notifier_Filters();
