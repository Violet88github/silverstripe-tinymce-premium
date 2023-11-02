<?php

namespace Violet88\TinyMCE;

use Exception;
use JSMin\JSMin;
use JSMin\UnterminatedStringException;
use JSMin\UnterminatedRegExpException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;

/**
 * The TinyMCEPremiumHandlerController class is responsible for creating the javascript file that is used to initialise TinyMCE Premium and it's various javascript settings using jQuery and entwine.
 *
 * @package Violet88\TinyMCE
 * @author Violet88 <info@violet88.nl>
 * @author Roël Couwenberg <contact@roelc.me>
 * @access public
 */
class TinyMCEPremiumHandlerController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    /**
     * The index action is responsible for creating the javascript file that is used to initialise TinyMCE Premium and it's various javascript settings using jQuery and entwine.
     * @return HTTPResponse The javascript file that is used to initialise TinyMCE Premium and it's various javascript settings using jQuery and entwine.
     * @throws Exception If jQuery or TinyMCE is not defined.
     * @throws UnterminatedStringException If the javascript is not terminated correctly.
     * @throws UnterminatedRegExpException If the javascript is not terminated correctly.
     */
    public function index()
    {
        $handler = TinyMCEPremiumHandler::create();
        $params = $this->getRequest()->getVars();

        $jsOptions = $handler->getJsOptions();

        $jsOptionsString = '';
        foreach ($jsOptions as $key => $value) {
            // Validate the JS using JSMin, if it's invalid, skip it and log an error
            try {
                JSMin::minify($value);
            } catch (UnterminatedStringException $e) {
                error_log('TinyMCEPremium: The option ' . $key . ' contains an unterminated string');
                throw $e;
            } catch (UnterminatedRegExpException $e) {
                error_log('TinyMCEPremium: The option ' . $key . ' contains an unterminated regular expression');
                throw $e;
            } catch (Exception $e) {
                error_log('TinyMCEPremium: The option ' . $key . ' is not valid javascript');
                error_log($value);
                error_log($e->getMessage());
                continue;
            }

            $jsOptionsString .= "'$key': $value,";
        }
        $jsOptionsString = "{" . substr($jsOptionsString, 0, -1) . "}";

        $js = <<<JS
        const options = $jsOptionsString;

        (function($) {
            $.entwine('ss', function($) {
                $('textarea.htmleditor[data-editor="tinyMCE"]').entwine({
                    onmatch: function() {
                        var editor = tinymce.get(this.attr('id'));

                        console.debug('TinyMCE Premium: Initialising editor ' + this.attr('id'));

                        if (!editor) {
                            console.warn('TinyMCE Premium: Could not find editor ' + this.attr('id'));
                            return;
                        }

                        if (!(editor instanceof tinymce.Editor)) {
                            console.warn('TinyMCE Premium: Editor ' + this.attr('id') + ' is not a TinyMCE editor');
                            return;
                        }

                        try {
                            editor.settings = $.extend(editor.settings, options);
                        } catch (e) {
                            console.error('TinyMCE Premium: Could not extend settings for editor ' + this.attr('id'));
                            console.error(e);
                            return;
                        }

                        this._super();
                    }
                });
            });
        })(jQuery);
        JS;

        if (!isset($params['debug']) || !$params['debug'] || !Director::isDev())
            try {
                $js = JSMin::minify($js);
            } catch (UnterminatedStringException $e) {
                error_log('Unterminated string in TinyMCEPremiumHandlerController::index()');
                throw $e;
            } catch (UnterminatedRegExpException $e) {
                error_log('Unterminated regular expression in TinyMCEPremiumHandlerController::index()');
                throw $e;
            }

        $response = new HTTPResponse($js, 200);
        $response->addHeader('Content-Type', 'application/javascript');

        return $response;
    }
}
