<?php
/**
 * PHP Notifier filters
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class PHP_Notifier_Filters {

	private $php_version;

	public function __construct( $php_version ) {

		$this->php_version = $php_version;

		add_filter( 'admin_footer_text', [ $this, 'php_version_footer_text' ] );

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
			__( 'Your site is running PHP version %s.', 'php-notifier' ),
			$this->php_version
		);

	}

}

$php_notifier_filters = new PHP_Notifier_Filters( $this->php_version );
