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
        $handler = TinyMCEPremiumHandler::get();
        $js = <<<JS
        var options = [];
        function initialiseTinyMCEPremium() {
            if (typeof jQuery === 'undefined')
                console.error('jQuery is not defined, cannot load TinyMCE Premium');

            if (typeof tinymce === 'undefined')
                console.error('TinyMCE is not defined, cannot load TinyMCE Premium');

            jQuery.entwine('ss', function($) {
                $('textarea.htmleditor[data-editor="tinyMCE"]').entwine({
                    onmatch: function() {
                        this._super();

                        var editor = tinymce.get(this.attr('id'));

                        if (editor === null) {
                            console.warn('TinyMCE Premium: Could not find editor ' + this.attr('id'));
                            return;
                        }

                        if (!(editor instanceof tinymce.Editor)) {
                            console.warn('TinyMCE Premium: Editor ' + this.attr('id') + ' is not a TinyMCE editor');
                            return;
                        }

                        var settings = editor.settings;
                        options.forEach(function(option) {
                            settings[option.key] = option.value;
                        });

                        try {
                            editor.destroy();
                            tinymce.init(settings);
                        } catch (e) {
                            console.error('TinyMCE Premium: Could not re-initialise editor ' + this.attr('id'));
                            console.error(e);
                        }
                    }
                });
            });

        }

        window.addEventListener('load', initialiseTinyMCEPremium);


        JS;

        foreach ($handler->getJsOptions() as $key => $value)
            $js .= "options['$key'] = $value;";

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
