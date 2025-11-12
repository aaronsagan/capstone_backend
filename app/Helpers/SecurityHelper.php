<?php

namespace App\Helpers;

use Mews\Purifier\Facades\Purifier;

class SecurityHelper
{
    /**
     * Sanitize rich text content (from WYSIWYG editors)
     * Allows safe HTML tags but removes dangerous scripts and attributes
     *
     * @param string|null $content
     * @return string
     */
    public static function sanitizeRichText(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Use HTML Purifier with custom config
        $config = [
            'HTML.Allowed' => 'p,b,strong,i,em,u,a[href|title],ul,ol,li,br,h1,h2,h3,h4,h5,h6,blockquote,code,pre,img[src|alt|title|width|height]',
            'HTML.AllowedAttributes' => 'a.href,a.title,img.src,img.alt,img.title,img.width,img.height',
            'HTML.TargetBlank' => true,
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
            'AutoFormat.AutoParagraph' => false,
            'HTML.Nofollow' => true,
        ];

        return Purifier::clean($content, $config);
    }

    /**
     * Sanitize plain text - strip all HTML
     *
     * @param string|null $text
     * @return string
     */
    public static function sanitizePlainText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Strip all HTML tags
        $text = strip_tags($text);
        
        // Remove any remaining script patterns
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $text);
        
        // Trim whitespace
        return trim($text);
    }

    /**
     * Sanitize filename - remove dangerous characters
     *
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters except dots, dashes, underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple dots (prevent double extension attacks)
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        return $filename;
    }

    /**
     * Validate and sanitize URL
     *
     * @param string|null $url
     * @return string|null
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Remove javascript: and data: protocols
        $url = preg_replace('/^(javascript|data|vbscript):/i', '', $url);
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    /**
     * Escape output for safe display
     *
     * @param string|null $text
     * @return string
     */
    public static function escape(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Check if string contains SQL injection patterns
     *
     * @param string $input
     * @return bool
     */
    public static function containsSQLInjection(string $input): bool
    {
        $patterns = [
            '/union.*select/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/update.*set/i',
            '/exec(\s|\()/i',
            '/script/i',
            '/<script/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string contains XSS patterns
     *
     * @param string $input
     * @return bool
     */
    public static function containsXSS(string $input): bool
    {
        $patterns = [
            '/<script/i',
            '/javascript:/i',
            '/onerror=/i',
            '/onload=/i',
            '/onclick=/i',
            '/<iframe/i',
            '/<embed/i',
            '/<object/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}
