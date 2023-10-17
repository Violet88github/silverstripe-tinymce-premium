<?php

use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use Violet88\TinyMCE\TinyMCEPremiumHandler;

$editorConfig = HTMLEditorConfig::get('cms');

if ($editorConfig instanceof TinyMCEConfig)
    TinyMCEPremiumHandler::require();
