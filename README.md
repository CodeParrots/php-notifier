# PHP Notifier v1.0.0

Notify users when the version of PHP running on their server has been or is about to be deprecated and will no longer be supported with security fixes.

**Contributors:** codeparrots, eherman24, brothman01 <br />
**Tags:** [php](https://wordpress.org/plugins/tags/php), [version](https://wordpress.org/plugins/tags/version), [notifier](https://wordpress.org/plugins/tags/notifier), [notice](https://wordpress.org/plugins/tags/notice), [deprecated](https://wordpress.org/plugins/tags/deprecated), [alert](https://wordpress.org/plugins/tags/alert), [email](https://wordpress.org/plugins/tags/email) <br />
**Requires at least:** 4.4 <br />
**Tested up to:** WordPress v4.7.2 <br />
**Stable tag:** 1.0.0 <br />
**License:** [GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.html) <br />

### Filters ###

`php_notifier_admin_footer_text`

Description: Toggle on/off the additional admin footer text displaying the PHP version.
Boolean: True or False
Default: True

```php
/**
 * Turn off the admin footer text
 */
add_filter( 'php_notifier_admin_footer_text', '__return_false' );
```


`php_notifier_warning_message`

Description: Alter the PHP warning message displayed.
Parameters:
- `$message` - The warning message to be displayed.
- `$warning_type` - The type used when displaying the warning message. (Warning types: deprecated, deprecated-soon, unsupported)

```php
/**
 * Change the deprecated warning message
 */
function alter_deprecated_warning_message( $message, $warning_type ) {

	if ( 'deprecated' === $warning_type ) {

		return 'This is the new message that will be displayed.';

	}

	return $message;

}
add_filter( 'php_notifier_warning_message', 'alter_deprecated_warning_message', 10, 2 );
```
