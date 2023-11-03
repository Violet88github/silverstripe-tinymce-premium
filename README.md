# SilverStripe TinyMCE Premium Module

This module provides a way to use your own [TinyMCE Cloud](https://tiny.cloud) API key with SilverStripe TinyMCE fields including the use of using JavaScript as config values.

## Requirements

* SilverStripe 4.0+
* PHP >= 7.4, >= 8.0
* [TinyMCE Cloud](https://tiny.cloud) API key
* [TinyMCE Premium](https://www.tiny.cloud/pricing/) subscription

## Installation

Install the module using composer.

```bash
composer require violet88/silverstripe-tinymce-premium
```

## Configuration

### TinyMCE

The module requires a TinyMCE Cloud API key to be configured. Make sure to [approve your domains](https://www.tiny.cloud/my-account/domains/) before using the API key. Your API key can be found in the [TinyMCE Cloud dashboard](https://www.tiny.cloud/my-account/dashboard/).

### SilverStripe

#### Configuration File

Your cloud API key can be configured in the `tinymce.yml` file.

```yaml
---
name: tinymce-premium
---

Violet88\TinyMCE\TinyMCEPremiumHandler:
    api_key: # Your TinyMCE Cloud API key
```

Additionally, you can configure the TinyMCE Premium plugin options in the `tinymce.yml` file. Don't do this if you don't know what you're doing, the default options are configured to work with SilverStripe 4.

```yaml
Violet88\TinyMCE\TinyMCEPremiumHandler:
    tinymce_version: 4                      # TinyMCE version
    tinymce_cdn: "https://cdn.tiny.cloud/1" # TinyMCE CDN
```

## Usage

The module can be used in the `_config.php` file to enable TinyMCE premium plugins and set JavaScript config values.

### Enabling premium plugins

Enabling premium plugins is as easy as enabling any other plugin. The following example enables the `tinymcespellchecker`, `advcode` and `mentions` plugins.

```php
$editorConfig = HTMLEditorConfig::get('cms');

if ($editorConfig instanceof TinyMCEConfig) {
    $handler = TinyMCEPremiumHandler::create();

    $editorConfig->enablePlugins([
        'tinymcespellchecker' => $handler->getPluginUrl('tinymcespellchecker'),
        'advcode' => $handler->getPluginUrl('advcode'),
        'mentions' => $handler->getPluginUrl('mentions')
    ]);
}
```

### Setting JavaScript config values

Most of the premium plugins allow you to set callbacks and other JavaScript config values. Since this is not natively supported in the built in PHP based SilverStripe TinyMCE config manager, this module provides a way to set JavaScript config values using the `setJsConfig` method. The following example sets the `mentions` plugin config value `mentions_fetch` to a demo callback.

```php
$editorConfig = HTMLEditorConfig::get('cms');

if ($editorConfig instanceof TinyMCEConfig) {
    $handler = TinyMCEPremiumHandler::create();

    $editorConfig->enablePlugins([
        'mentions' => $handler->getPluginUrl('mentions')
    ]);

    $handler->setJsOptions([
        'mentions_fetch' => <<<JS
            function (query, success) {
                // Fetch your full user list from somewhere
                var users = [
                    { id: '1', name: 'wyatt', fullName: 'Wyatt Wilson' },
                    { id: '2', name: 'gabriel', fullName: 'Gabriel Brown' },
                    { id: '3', name: 'hazel', fullName: 'Hazel Lee' },
                    { id: '4', name: 'owen', fullName: 'Owen Johnson' },
                    { id: '5', name: 'lily', fullName: 'Lily Davis' },
                ];

                users = users.filter(function (user) {
                    return user.name.toLowerCase().indexOf(query.term.toLowerCase()) !== -1;
                });

                window.setTimeout(function () {
                    success(users);
                }, 0);
            }
            JS
    ]);
}
```