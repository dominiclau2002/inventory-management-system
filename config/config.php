<?php
/**
 * Main Application Configuration
 * Handles URLs, paths, and environment-specific settings
 */

// Set timezone to Singapore Time
date_default_timezone_set('Asia/Singapore');

// Base URL Configuration
// Auto-detect if we're on localhost or production
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    // Check if we're on localhost (development)
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        // For localhost, include the project folder name
        $script_path = dirname($_SERVER['SCRIPT_NAME']);

        // Extract project folder from path
        if (strpos($script_path, 'inventory-management-system') !== false) {
            return $protocol . $host . '/inventory-management-system';
        }

        // Fallback: try to detect from current script location
        $path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        if (in_array('inventory-management-system', $path_parts)) {
            return $protocol . $host . '/inventory-management-system';
        }

        return $protocol . $host;
    } else {
        // For production (InfinityFree, etc.), project is at domain root
        return $protocol . $host;
    }
}

// Define the base URL constant
define('BASE_URL', get_base_url());

// Helper function to generate asset URLs (CSS, JS, images)
function asset($path = '') {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

// Helper function to generate page URLs
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Environment detection
function is_localhost() {
    $host = $_SERVER['HTTP_HOST'];
    return (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
}

// Application settings
define('APP_NAME', 'Inventory Management System');
define('APP_SHORT_NAME', 'IMS');
define('APP_VERSION', '1.0.0');
?>