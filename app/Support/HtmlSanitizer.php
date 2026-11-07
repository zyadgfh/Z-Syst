<?php

namespace App\Support;

/**
 * HtmlSanitizer — allowlist-based sanitizer for admin-authored rich text
 * (terms/policy pages rendered with {!! !!}). Strips scripts, event
 * handlers, javascript:/data: URLs and style blocks; keeps basic markup.
 * Dependency-free (regex allowlist layer on top of strip_tags) — replace
 * with ezyang/htmlpurifier if richer policy is ever needed.
 */
class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // drop dangerous blocks entirely
        $html = preg_replace('#<\s*(script|iframe|object|embed|form|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('#<\s*(script|iframe|object|embed|form|style)[^>]*/?>#is', '', $html);

        // strip event handlers and dangerous URL schemes
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*("\s*(javascript|vbscript|data(?!:image\/)):.*?)"/i', '$1="#"', $html);

        // keep an allowlist of tags only
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6>'
            .'<blockquote><a><img><table><thead><tbody><tr><td><th><hr><span><div><sup><sub>';
        $html = strip_tags($html, $allowed);

        // neutralize remaining inline styles (style attr dropped)
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);

        return $html;
    }
}
