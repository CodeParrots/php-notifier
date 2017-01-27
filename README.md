# PHP Notifier v1.0.0

Notify users when the version of PHP running on their server has been or is about to be deprecated and will no longer be supported with security fixes.

### Filters

`php_notifier_admin_footer_text`

Boolean: True or False
Default: True
Description: Toggle on/off the additional admin footer text displaying the PHP version.

```php
/**
 * Turn off the admin footer text
 */
add_filter( 'php_notifier_admin_footer_text', '__return_false' );
```
