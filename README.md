# Simple Site Data

A modular WordPress plugin that surfaces hidden data inside wp-admin for troubleshooting. Only loads for administrators.

## Included Modules

**Product Data** -- Adds a metabox to the WooCommerce product edit screen showing:

- Core post fields (ID, title, status, dates, etc.)
- All post meta including hidden `_`-prefixed keys, unserialized for readability
- WooCommerce product attributes
- Taxonomy terms

The viewer includes syntax-highlighted JSON, collapsible sections, text search, and copy-to-clipboard.

## Creating a New Module

1. Create a directory under `modules/` with a descriptive name:

```
modules/
  your-module/
    class-module.php
```

2. In `class-module.php`, create a class that implements `SSD_Module_Interface` and return an instance at the end of the file:

```php
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSD_Your_Module implements SSD_Module_Interface {

    public function register(): void {
        // Hook into WordPress here.
        // This is called once during admin_init for admin users only.
    }
}

return new SSD_Your_Module();
```

3. That's it. The module loader discovers any `class-module.php` file inside a `modules/` subdirectory and calls `register()` automatically. No registration step needed.

### Interface

Every module must implement `SSD_Module_Interface`, which requires a single method:

- `register(): void` -- Hook into WordPress (add actions, filters, meta boxes, etc.)

### Assets

Place CSS and JS files in `assets/css/` and `assets/js/`. Use the `SSD_PLUGIN_URL` and `SSD_VERSION` constants when enqueuing:

```php
wp_enqueue_style(
    'ssd-your-module',
    SSD_PLUGIN_URL . 'assets/css/your-module.css',
    array(),
    SSD_VERSION
);
```

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce (for the Product Data module)
