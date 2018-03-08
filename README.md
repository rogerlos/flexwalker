# flexwalker

WordPress plugin which allows flexibly-formatted menus via templates. For developers.

## Description

This plugin extends the WordPress nav walker with a version allowing for fairly complex menu structures. Wanting to use
"split buttons" within a Bootstrap 4 `navbar` component and use a WordPress menu was the prompt for writing Flexwalker, but it's
coded in a way as to be useful even if you're not using Bootstrap 4.

Loading the JavaScript file is optional. It contains some DOM manipulation options to help with forcing clicks, adding
helper classes, moving DOM elements if a toggle becomes visible, and the like.

All configuration of the plugin is via JSON. Avoid overwriting the core JSON by creating a `flexwalker` directory in
the root of your theme directory and copying the plugin's JSON files there.

[See the wiki](https://github.com/rogerlos/flexwalker/wiki) for in-depth information.

### Using

To get a menu, call or echo:

```php
flexwalker( $args );
```

Arguments can be any var set in the JSON files. Note that they are stored internally by their filename, minus the 
'.json':

```php
[
    'display'   => [], // contents of display.json, decoded
    'tags'      => [], // etc
    'templates' => [],
    'walker'    => [],
];
```

If you want to see what the config looks like, you can call `flexwalker_cfg()`. If you want a specific part of the
configuration, use a "dots" key, where each nested level is separated by a dot: `display.use` will retrieve 
`$this->cfg['display']['use']`.


## Revisions

### 1.4.1

* Checked for empty 'tag' attribute in templates.json

### 1.4

* Fixed bug with "content" configured in JSON

### 1.3.3

* Fixed bug where menus too large for even the largest space did not flip to the "hamburger" menu
* Fixed bug with the dom repositioning not iterating over multiple elements found with same selector.
* Fixed bug which did not pass any extra classes added via WP menu to the walker

### 1.3.2

* Added composer.json and packagist hook

### 1.3.1

* Modified 'dom' to be consistent with other optional features

### 1.3

* Modified the display JSON to allow 'use' flag, changed 'modify' to 'modifymenu'

### 1.2

* Bootstrap 4 beta squished what was being used to detect things, changes to reflect that.

### 1.1

* Changes to try and ensure greater reliability to menu detection

### 1.0

* Initial release. Not thoroughly tested across platforms or browsers yet!