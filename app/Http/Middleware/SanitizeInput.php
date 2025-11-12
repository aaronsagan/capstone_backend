<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     * Sanitizes all input data to prevent XSS attacks
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't sanitize certain content-type requests
        $contentType = $request->header('Content-Type');
        if (Str::contains($contentType, ['multipart/form-data', 'application/octet-stream'])) {
            return $next($request);
        }

        // Get all input
        $input = $request->all();
        
        // Recursively sanitize all input
        $sanitized = $this->sanitizeData($input);
        
        // Replace request input with sanitized data
        $request->merge($sanitized);
        
        return $next($request);
    }

    /**
     * Recursively sanitize data
     * 
     * @param mixed $data
     * @return mixed
     */
    protected function sanitizeData($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }
        
        if (!is_string($data)) {
            return $data;
        }
        
        // List of fields that should allow HTML (WYSIWYG editors)
        // These will be sanitized differently
        $allowedHtmlFields = ['description', 'content', 'message', 'body', 'story', 'problem', 'solution', 'expected_outcome'];
        
        // Basic XSS protection for all strings
        // Remove script tags and dangerous attributes
        $data = $this->removeXSSPatterns($data);
        
        // Trim whitespace
        $data = trim($data);
        
        return $data;
    }

    /**
     * Remove common XSS attack patterns
     * 
     * @param string $value
     * @return string
     */
    protected function removeXSSPatterns(string $value): string
    {
        // Remove script tags
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $value);
        
        // Remove iframe tags
        $value = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $value);
        
        // Remove object/embed tags
        $value = preg_replace('/<(object|embed)\b[^>]*>.*?<\/\1>/is', '', $value);
        
        // Remove javascript: protocol
        $value = preg_replace('/javascript:/i', '', $value);
        
        // Remove vbscript: protocol
        $value = preg_replace('/vbscript:/i', '', $value);
        
        // Remove data: protocol (can be used for XSS)
        $value = preg_replace('/data:text\/html/i', '', $value);
        
        // Remove event handlers (onclick, onload, etc.)
        $value = preg_replace('/\s*on\w+\s*=/i', '', $value);
        
        return $value;
    }
}

