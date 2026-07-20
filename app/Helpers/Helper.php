<?php

// Helper functions file
// This file provides global helper functions for the application

if (!function_exists('app_url')) {
    /**
     * Get the application URL
     */
    function app_url($path = '')
    {
        return config('app.url') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('is_active')) {
    /**
     * Check if current route matches given path
     */
    function is_active($path)
    {
        return request()->is($path) ? 'active' : '';
    }
}