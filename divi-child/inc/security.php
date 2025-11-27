<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Child Theme Security Features
 *
 * Contains filters for safe SVG uploads and other security enhancements.
 *
 * @package buildx
 */

// Allow safe SVG uploads for administrators only.
add_filter('upload_mimes', function($mimes){
    if (current_user_can('manage_options')) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['svg','svgz'])) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 4);

add_filter('wp_handle_upload_prefilter', function($file){
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['svg','svgz'])) return $file;
    if (!current_user_can('manage_options')) {
        $file['error'] = 'Only administrators may upload SVG files.';
        return $file;
    }
    
    // NOTE: This uses file_get_contents on a temporary user file.
    // The explicit, conservative regex checks below are your current defense.
    $raw = @file_get_contents($file['tmp_name']);
    if ($raw === false) {
        $file['error'] = 'SVG could not be read.';
        return $file;
    }
    
    // Very conservative checks for script/event handler injection
    $danger = [
        '/<\s*script\b/i',
        '/<\s*foreignObject\b/i',
        '/on\w+\s*=/i',                 // onload=, onclick=, etc
        '/xlink:href\s*=\s*["\']\s*javascript:/i',
        '/href\s*=\s*["\']\s*javascript:/i',
        '/data\s*:\s*text\/html/i'
    ];
    foreach ($danger as $re) {
        if (preg_match($re, $raw)) {
            $file['error'] = 'Unsafe SVG content detected. Please export a clean SVG (no scripts or event handlers).';
            return $file;
        }
    }
    return $file;
});
// No PHP closing tag (clean code standard)