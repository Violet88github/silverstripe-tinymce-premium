<?php

namespace Violet88\TinyMCE;

use BadMethodCallException;
use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;

/**
 * The TinyMCEPremiumHandler class is used to be able to fetch and use TinyMCE premium plugins from tiny.cloud. Using the config it can find the correct version of tinymce and fetch the correct javascript files from the cdn.
 *
 * @package Violet88\TinyMCE
 * @author Violet88 <info@violet88.nl>
 * @author Roël Couwenberg <contact@roelc.me>
 * @access public
 * @see https://www.tiny.cloud/docs/premium/
 * @see https://www.tiny.cloud/docs/premium/plugins/
 */
class TinyMCEPremiumHandler
{
    use Configurable;

    /**
     * @config
     * @var string The API key for TinyMCE Premium
     */
    private static string $api_key = '';

    /**
     * @config
     * @var string The tinymce version to use
     */
    private static string $tinymce_version = '4';

    /**
     * @config
     * @var string The tinymce cdn to use
     */
    private static string $tinymce_cdn = 'https://cdn.tiny.cloud/1';

    /**
     * @var string The environment prefix to use when fetching config values from the environment
     */
    private static string $environment_prefix = 'TINYMCE_PREMIUM_';

    /**
     * @var null|string The resolved tinymce version, set after fetching the version from the cdn
     */
    private ?string $tinymce_resolved_version = null;

    /**
     * @var array The javascript options to parse in the JSHandler.php file.
     * @see client/JSHandler.php
     */
    private array $jsOptions = [];

    /**
     * @var TinyMCEPremiumHandler The global handler
     */
    private static $instance;

    public function __construct()
    {
        $this->tinymce_resolved_version = $this->resolve_tinymce_version();
    }

    /**
     * Create a new instance of the handler, or return the global handler if it already exists
     * @return TinyMCEPremiumHandler The handler
     */
    public static function create()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Require the javascript file from the cdn
     * @return void
     * @throws Exception If the global handler is not defined
     */
    public static function require()
    {
        $handler = self::create();
        Requirements::javascript($handler->getRequiredUrl(), [
            'defer' => true
        ]);
    }

    /**
     * Get the config API key
     * @return string The API key
     * @throws BadMethodCallException If the API key is not set
     */
    public function getApiKey()
    {
        return trim(self::get_config('api_key'), '/');
    }

    /**
     * Get the config tinymce version
     * @return string The tinymce version
     * @throws BadMethodCallException If the tinymce version is not set
     */
    public function getTinyMCEVersion()
    {
        return trim(self::get_config('tinymce_version'), '/');
    }

    /**
     * Get the resolved tinymce version
     * @return string The resolved tinymce version
     * @throws BadMethodCallException If the tinymce version is not set
     * @throws Exception If the tinymce version cannot be resolved
     */
    public function getResolvedTinyMCEVersion()
    {
        if (!$this->tinymce_resolved_version)
            $this->tinymce_resolved_version = $this->resolve_tinymce_version();

        return $this->tinymce_resolved_version;
    }

    /**
     * Get the config tinymce cdn
     * @return string The tinymce cdn
     * @throws BadMethodCallException If the tinymce cdn is not set
     */
    public function getTinyMCEDN()
    {
        return trim(self::get_config('tinymce_cdn'), '/');
    }

    /**
     * Get the url to the tinymce premium javascript file from the cdn
     * @param string $path The name of the javascript file
     * @return string The url to the javascript file
     * @throws BadMethodCallException If the tinymce version is not set
     * @throws Exception If the tinymce version cannot be resolved
     */
    public function getUrl(string $path = 'plugins.min.js')
    {
        $version = $this->getTinyMCEVersion();
        $cdn = $this->getTinyMCEDN();
        $api_key = $this->getApiKey();

        if (!$version)
            throw new \Exception('TinyMCE version not set');

        if (!$cdn)
            throw new \Exception('TinyMCE CDN not set');

        if (!$api_key)
            throw new \Exception('TinyMCE Premium API key not set');

        return "{$cdn}/{$api_key}/tinymce/{$version}/{$path}";
    }

    /**
     * Get the url to the tinymce premium plugin javascript file from the cdn
     * @param string $plugin The name of the plugin
     * @return string The url to the javascript file
     * @throws BadMethodCallException If the tinymce version is not set
     * @throws Exception If the tinymce version cannot be resolved
     */
    public function getPluginUrl(string $plugin)
    {
        $version = $this->getResolvedTinyMCEVersion();
        $cdn = $this->getTinyMCEDN();
        $api_key = $this->getApiKey();

        if (!$version)
            throw new \Exception('TinyMCE version not set');

        if (!$cdn)
            throw new \Exception('TinyMCE CDN not set');

        if (!$api_key)
            throw new \Exception('TinyMCE Premium API key not set');

        return "{$cdn}/{$api_key}/tinymce/{$version}/plugins/{$plugin}/plugin.min.js";
    }

    /**
     * Get the url to this plugin's required javascript file, this file is used for loading javascript options in the editor
     * @return string The url to the javascript file
     */
    public function getRequiredUrl()
    {
        return Director::absoluteURL('_violet88/tinymce-premium.js');
    }

    /**
     * Set a javascript option
     * @param string $key The key of the option
     * @param string $value The value of the option
     * @return void
     */
    public function setJsOption(string $key, string $value)
    {
        if ($value !== null && !empty($value))
            $this->jsOptions[$key] = $value;
        else if (isset($this->jsOptions[$key]))
            unset($this->jsOptions[$key]);
    }

    /**
     * Set multiple javascript options
     * @param array $options The options to set
     * @return void
     * @throws Exception If any of the keys or values are not strings
     */
    public function setJsOptions(array $options)
    {
        // If any of the keys and values are not strings, throw an exception
        foreach ($options as $key => $value) {
            if (!is_string($key) || !is_string($value))
                throw new \Exception('Javascript options must be strings');
        }

        $this->jsOptions = $options;
    }

    /**
     * Get all javascript options
     * @return array The javascript options
     */
    public function getJsOptions()
    {
        return $this->jsOptions;
    }

    /**
     * Resolve the tinyMCE version from the cdn using it's 302 redirect
     * @return string The resolved tinymce version
     * @throws BadMethodCallException If the tinymce version is not set
     * @throws Exception If the tinymce version cannot be resolved
     */
    private function resolve_tinymce_version()
    {
        $version = $this->getTinyMCEVersion();

        if (!$version)
            throw new \Exception('TinyMCE version not set');

        // Send a curl request to the url and log any redirects
        $curl = curl_init($this->getUrl());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);

        curl_close($curl);

        if ($info['http_code'] !== 200)
            throw new \Exception('TinyMCE version not found');

        $match = '/tinymce\/([0-9.-]+)\/plugins.min.js/';
        preg_match($match, $info['url'], $matches);
        if (isset($matches[1]))
            return $matches[1];

        throw new \Exception('TinyMCE version not found');
    }

    /**
     * Get a config value from the environment or the config
     * @param string $key The key of the config value
     * @return mixed The config value or null if it doesn't exist
     * @throws BadMethodCallException If the key is not a string
     */
    private function get_config(string $key)
    {
        $value = Environment::getEnv(self::$environment_prefix . $key);
        if ($value !== null && !empty($value))
            return $value;

        $value = self::config()->get($key);
        if ($value !== null && !empty($value))
            return $value;

        return null;
    }
}
