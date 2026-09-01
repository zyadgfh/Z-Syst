<?php

namespace App\Helpers;

use HTMLPurifier;

class HtmlSanitizer
{
    protected static ?HTMLPurifier $purifier = null;

    /**
     * Sanitize HTML content using HTMLPurifier.
     * Allows safe tags (bold, italic, links, lists, headings, etc.)
     * while stripping dangerous elements (script, iframe, event handlers).
     */
    public static function sanitize(string $html): string
    {
        return static::getPurifier()->purify($html);
    }

    protected static function getPurifier(): HTMLPurifier
    {
        if (static::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Allowed', 'p,b,i,u,a[href|title],strong,em,br,ul,ol,li,blockquote,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th[align],td[align|colspan|rowspan],img[src|alt|width|height],hr,span[class],div[class]');
            $config->set('HTML.AllowedAttributes', 'a.href, a.title, img.src, img.alt, img.width, img.height, td.align, td.colspan, td.rowspan, th.align, span.class, div.class');
            $config->set('HTML.TargetBlank', true);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('Cache.DefinitionImpl', null);

            static::$purifier = new HTMLPurifier($config);
        }

        return static::$purifier;
    }
}
