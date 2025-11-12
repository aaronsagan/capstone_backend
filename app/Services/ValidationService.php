<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidationService
{
    /**
     * Common validation rules for different input types
     */
    const RULES = [
        'email' => 'required|email:rfc,dns|max:255',
        'password' => 'required|string|min:8|max:255',
        'name' => 'required|string|max:255|regex:/^[a-zA-Z\s\-\'\.]+$/',
        'phone' => 'nullable|string|regex:/^[\d\s\-\+\(\)]+$/|max:20',
        'url' => 'nullable|url:http,https|max:500',
        'text' => 'nullable|string|max:1000',
        'textarea' => 'nullable|string|max:10000',
        'number' => 'required|numeric|min:0',
        'amount' => 'required|numeric|min:0.01|max:9999999.99',
        'date' => 'required|date',
        'alphanumeric' => 'required|string|alpha_num|max:255',
    ];

    /**
     * Validate and sanitize email
     */
    public static function validateEmail(string $email): ?string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        return $email;
    }

    /**
     * Validate and sanitize URL
     */
    public static function validateUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        
        // Remove dangerous protocols
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return null;
        }
        
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Ensure it's http or https
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        return $url;
    }

    /**
     * Sanitize text input (remove HTML but keep basic formatting)
     */
    public static function sanitizeText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }
        
        // Remove all HTML tags
        $text = strip_tags($text);
        
        // Convert HTML entities
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Sanitize HTML content (for WYSIWYG editors)
     * Allows safe HTML tags but removes scripts
     */
    public static function sanitizeHtml(?string $html): ?string
    {
        if (!$html) {
            return null;
        }
        
        // Allowed tags for rich content
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><hr><img>';
        
        // Strip dangerous tags
        $html = strip_tags($html, $allowedTags);
        
        // Remove javascript: and other dangerous protocols from links
        $html = preg_replace('/<a([^>]+)href=(["\'])javascript:/i', '<a$1href=$2#', $html);
        $html = preg_replace('/<a([^>]+)href=(["\'])data:/i', '<a$1href=$2#', $html);
        
        // Remove event handlers
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Remove style attributes that could contain expressions
        $html = preg_replace('/\s*style\s*=\s*["\'][^"\']*expression[^"\']*["\']/i', '', $html);
        
        return $html;
    }

    /**
     * Validate phone number format
     */
    public static function validatePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        
        // Remove all non-digit characters except + - ( ) and spaces
        $phone = preg_replace('/[^\d\s\-\+\(\)]/', '', $phone);
        
        // Basic phone number validation (10-15 digits)
        if (!preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone)) {
            return null;
        }
        
        return trim($phone);
    }

    /**
     * Validate and sanitize name
     */
    public static function validateName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }
        
        // Allow letters, spaces, hyphens, apostrophes, and periods
        $name = preg_replace('/[^a-zA-Z\s\-\'\.]/u', '', $name);
        
        $name = trim($name);
        
        if (strlen($name) < 2 || strlen($name) > 255) {
            return null;
        }
        
        return $name;
    }

    /**
     * Check if input contains SQL injection patterns
     */
    public static function detectSQLInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bTRUNCATE\b)/i',
            '/(\bEXEC(\s|\+)+(s|x)p\w+)/i',
            '/(;|\||<|>)/i',
            '/(\bOR\b.*\d+\s*=\s*\d+)/i',
            '/(\bAND\b.*\d+\s*=\s*\d+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if input contains XSS patterns
     */
    public static function detectXSS(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/data:text\/html/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
}
