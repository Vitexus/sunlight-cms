<?php

namespace SunlightExtend\Codemirror;

use Sunlight\Admin\Admin;
use Sunlight\Core;
use Sunlight\Plugin\ExtendPlugin;

class CodemirrorPlugin extends ExtendPlugin
{
    /**
     * Define JS variables
     *
     * @param array $args
     */
    function onCoreJavascript(array $args): void
    {
        $args['variables']['pluginCodemirror'] = [
            'userWysiwygEnabled' => _logged_in && Core::$userData['wysiwyg'],
        ];
    }

    /**
     * Load CSS and JS
     *
     * @param array $args
     */
    function onAdminHead(array $args): void
    {
        $basePath = $this->getWebPath() . '/Resources';

        $args['css']['codemirror'] = $basePath . '/lib/codemirror.css';
        $args['css']['codemirror_theme'] = $basePath . '/theme/' . (Admin::themeIsDark() ? 'ambiance' : 'eclipse') . '.css';
        $args['css']['codemirror_dialog'] = $basePath . '/addon/dialog/dialog.css';

        $args['js']['codemirror'] = $basePath . '/lib/codemirror.js';
        $args['js']['codemirror_search'] = $basePath . '/addon/search/search.js';
        $args['js']['codemirror_searchcursor'] = $basePath . '/addon/search/searchcursor.js';
        $args['js']['codemirror_dialog'] = $basePath . '/addon/dialog/dialog.js';
        $args['js']['codemirror_activeline'] = $basePath . '/addon/selection/active-line.js';
        $args['js']['codemirror_matchbrackets'] = $basePath . '/addon/edit/matchbrackets.js';
        $args['js']['codemirror_init'] = $basePath . '/lib/codemirror-init.js';
    }

    /**
     * Generate admin CSS
     *
     * @param array $args
     */
    function onAdminStyle(array $args): void
    {
        $args['output'] .= "/* codemirror */\n";
        $args['output'] .= "div.CodeMirror {\n";

        if ($GLOBALS['dark']) {
            $args['output'] .= "border: 1px solid {$GLOBALS['scheme_smoke_dark']};\n";
        } else {
            $args['output'] .= "outline: 1px solid  {$GLOBALS['scheme_white']};\n";
            $args['output'] .= "border-width: 1px;\n";
            $args['output'] .= "border-style: solid;\n";
            $args['output'] .= "border-color: {$GLOBALS['scheme_smoke_dark']} {$GLOBALS['scheme_smoke']} {$GLOBALS['scheme_smoke']} {$GLOBALS['scheme_smoke_dark']};\n";
        }

        $args['output'] .= "line-height: 1.5;\n";
        $args['output'] .= "cursor: text;\n";
        $args['output'] .= "background-color: #fff;\n";
        $args['output'] .= "}\n";
        $args['output'] .= "div.CodeMirror span.cm-hcm {color: " . ($GLOBALS['dark'] ? '#ff0' : '#f60') . ";}\n";
    }
}
