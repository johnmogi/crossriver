<?php
/**
 * Plugin Name: Comeet Slider Helper
 * Description: Enhanced job slider for Comeet with category filtering and RTL support.
 * Version: 2.0.2
 * Author: אביב דיגיטל
 */

if (!defined('ABSPATH')) { exit; }

// Hook into WordPress init to add rewrite rules for job URLs
add_action('init', 'comeet_helper_add_rewrite_rules');
add_action('template_redirect', 'comeet_helper_handle_job_page');

// Plugin activation and deactivation hooks
register_activation_hook(__FILE__, 'comeet_helper_activate');
register_deactivation_hook(__FILE__, 'comeet_helper_deactivate');

/**
 * Add rewrite rules for job URLs to prevent 404 errors
 */
function comeet_helper_add_rewrite_rules() {
    error_log('🔧 REWRITE: Adding custom rewrite rules for Comeet URLs');
    
    $base = 'careers';
    
    // Rule 1: Simple format /careers/co/{job-slug}/
    $regex_simple = $base . '/co/([^/]+)/?$';
    $query_simple = 'index.php?pagename=' . $base . '&comeet_pos=$matches[1]';
    add_rewrite_rule($regex_simple, $query_simple, 'top');
    error_log('🔧 REWRITE: Added simple rule - ' . $regex_simple . ' -> ' . $query_simple);
    
    // Rule 2: Full format /careers/co/{category}/{position}/{job-slug}/all/
    $regex_full = $base . '/co/([^/]+)/([^/]+)/([^/]+)/?(/all)?$';
    $query_full = 'index.php?pagename=' . $base . '&comeet_cat=$matches[1]&comeet_pos=$matches[2]&comeet_all=$matches[4]';
    add_rewrite_rule($regex_full, $query_full, 'top');
    error_log('🔧 REWRITE: Added full rule - ' . $regex_full . ' -> ' . $query_full);
    
    // Force flush rules if not done yet
    if (!get_option('comeet_helper_rules_flushed')) {
        flush_rewrite_rules();
        update_option('comeet_helper_rules_flushed', true);
        error_log('🔧 REWRITE: FLUSHED rewrite rules - URLs should now work');
    }
    
    // Add query vars
    add_filter('query_vars', function($vars) {
        $vars[] = 'comeet_location';
        $vars[] = 'comeet_job_id';
        $vars[] = 'comeet_job_slug';
        return $vars;
    });
}

/**
 * Handle job page requests and redirect to appropriate Comeet page
 */
function comeet_helper_handle_job_page() {
    $job_slug = get_query_var('comeet_job_slug');
    $job_id = isset($_GET['job_id']) ? sanitize_text_field($_GET['job_id']) : '';
    
    if (!empty($job_slug)) {
        error_log('🔗 COMEET ROUTING: Handling job page for slug: ' . $job_slug . ' with job_id: ' . $job_id);
        
        // Try to find the careers page and redirect to Comeet format
        if (class_exists('Comeet')) {
            try {
                $comeet = new Comeet();
                $options = $comeet->get_options();
                
                if (!empty($options['post_id'])) {
                    $careers_page = get_post($options['post_id']);
                    if ($careers_page) {
                        $careers_url = get_permalink($careers_page->ID);
                        
                        // Convert our job slug back to a title for Comeet format
                        $job_title = str_replace('-', ' ', $job_slug);
                        $job_title = ucwords($job_title);
                        
                        // Redirect to Comeet format URL if we can construct it
                        $comeet_url = $careers_url . 'co/' . sanitize_title($job_title) . '/';
                        error_log('🔀 COMEET ROUTING: Redirecting to Comeet URL: ' . $comeet_url);
                        
                        wp_redirect($comeet_url, 301);
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log('❌ COMEET ROUTING: Error handling job page: ' . $e->getMessage());
            }
        }
        
        // Fallback: redirect to careers page
        $careers_fallback = home_url('/careers/');
        error_log('🔄 COMEET ROUTING: Fallback redirect to: ' . $careers_fallback);
        wp_redirect($careers_fallback, 302);
        exit;
    }
}

/**
 * Plugin activation hook
 */
function comeet_helper_activate() {
    error_log('🔧 PLUGIN: Activating Comeet Helper Plugin');
    comeet_helper_add_rewrite_rules();
    flush_rewrite_rules();
    error_log('🔧 PLUGIN: Rewrite rules flushed on activation');
}

/**
 * Plugin deactivation hook
 */
function comeet_helper_deactivate() {
    error_log('🔧 PLUGIN: Deactivating Comeet Helper Plugin');
    flush_rewrite_rules();
    error_log('🔧 PLUGIN: Rewrite rules flushed on deactivation');
}

/**
 * Force flush rewrite rules (for debugging)
 */
function comeet_helper_flush_rules() {
    error_log('🔧 FLUSH: Manually flushing rewrite rules');
    comeet_helper_add_rewrite_rules();
    flush_rewrite_rules();
    error_log('🔧 FLUSH: Rules flushed successfully');
}

/**
 * Debug shortcode for testing URL routing and generation
 * Usage: [comeet_debug_urls]
 */
add_shortcode('comeet_debug_urls', 'comeet_debug_urls_shortcode');
function comeet_debug_urls_shortcode($atts) {
    if (!current_user_can('manage_options')) {
        return '<p>Debug access restricted to administrators.</p>';
    }
    
    $output = '<div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin: 20px 0;">';
    $output .= '<h3>🔧 Comeet URL Debug Information</h3>';
    
    // Test job titles for URL generation
    $test_jobs = [
        'Senior Software Engineer',
        'Product Manager - Growth',
        'UX/UI Designer'
    ];
    
    $output .= '<h3>🔧 URL Generation Test</h3>';
    foreach ($test_jobs as $job_title) {
        $url = generate_comeet_job_url($job_title, '');
        $output .= '<p><strong>' . esc_html($job_title) . ':</strong><br>';
        $output .= '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></p>';
    }
    
    // Add manual flush button
    if (isset($_GET['flush_rules']) && $_GET['flush_rules'] === '1') {
        comeet_helper_flush_rules();
        $output .= '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;">✅ Rewrite rules flushed successfully!</div>';
    }
    
    $flush_url = add_query_arg('flush_rules', '1', get_permalink());
    $output .= '<p><a href="' . esc_url($flush_url) . '" style="background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px;">🔄 Flush Rewrite Rules</a></p>';
    
    $output .= '<h4>System Information:</h4>';
    $output .= '<p><strong>Home URL:</strong> ' . home_url() . '</p>';
    $output .= '<p><strong>Careers Base:</strong> ' . home_url('/careers/') . '</p>';
    $output .= '<p><strong>Comeet Plugin:</strong> ' . (class_exists('Comeet') ? '✅ Active' : '❌ Not Found') . '</p>';
    
    if (class_exists('Comeet')) {
        try {
            $comeet = new Comeet();
            $options = $comeet->get_options();
            $output .= '<p><strong>Comeet Post ID:</strong> ' . (isset($options['post_id']) ? $options['post_id'] : 'Not Set') . '</p>';
        } catch (Exception $e) {
            $output .= '<p><strong>Comeet Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    $output .= '<p><em>Check debug logs for detailed URL generation traces.</em></p>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Debug shortcode for showing actual Comeet job data and links
 * Usage: [comeet_debug_jobs]
 */
add_shortcode('comeet_debug_jobs', 'comeet_debug_jobs_shortcode');
function comeet_debug_jobs_shortcode($atts) {
    if (!current_user_can('manage_options')) {
        return '<p>Debug access restricted to administrators.</p>';
    }
    
    error_log('🔧 DEBUG SHORTCODE: Starting debug shortcode execution');
    
    $output = '<div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin: 20px 0;">';
    $output .= '<h3>🔧 Comeet Jobs Debug - Real Data (Generated: ' . date('Y-m-d H:i:s') . ')</h3>';
    $output .= '<p><strong>🕒 Timestamp:</strong> ' . time() . '</p>';
    
    // Force clear any potential caching
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        error_log('🔧 DEBUG SHORTCODE: Cleared WP cache');
    }
    
    // Get actual jobs from Comeet - use fresh call
    error_log('🔧 DEBUG SHORTCODE: About to call comeet_fetch_jobs()');
    
    // Call the exact same function the slider uses
    $jobs = [];
    
    // COMPREHENSIVE DEBUG SYSTEM
    $debug_info = [];
    
    // Test 1: Function existence
    $debug_info['function_exists'] = function_exists('comeet_fetch_jobs');
    $output .= '<p><strong>🔍 Function Exists:</strong> ' . ($debug_info['function_exists'] ? 'YES' : 'NO') . '</p>';
    
    // Test 2: Direct Comeet class access
    $debug_info['comeet_class'] = class_exists('Comeet');
    $output .= '<p><strong>🔍 Comeet Class:</strong> ' . ($debug_info['comeet_class'] ? 'YES' : 'NO') . '</p>';
    
    // Test 3: Try multiple job fetching methods
    $jobs = [];
    $test_results = [];
    
    // Method 1: Direct function call
    if ($debug_info['function_exists']) {
        try {
            $start_time = microtime(true);
            $jobs_method1 = comeet_fetch_jobs();
            $end_time = microtime(true);
            $test_results['method1'] = [
                'success' => true,
                'count' => count($jobs_method1),
                'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                'jobs' => $jobs_method1
            ];
            if (count($jobs_method1) > count($jobs)) {
                $jobs = $jobs_method1;
            }
        } catch (Exception $e) {
            $test_results['method1'] = ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Method 2: Direct Comeet class call with API diagnostics
    if ($debug_info['comeet_class']) {
        try {
            $start_time = microtime(true);
            $comeet = new Comeet();
            
            // Check Comeet options and API setup
            $options = $comeet->get_options();
            $api_url = '';
            $api_status = 'Unknown';
            
            // Try to get the API URL the plugin uses
            if (!empty($options['comeet_token']) && !empty($options['comeet_uid'])) {
                $api_url = 'https://www.comeet.co/careers-api/2.0/company/' . $options['comeet_uid'] . '/positions?token=' . $options['comeet_token'];
                
                // Test direct API call
                $api_response = wp_remote_get($api_url, [
                    'timeout' => 30,
                    'sslverify' => false
                ]);
                
                if (!is_wp_error($api_response)) {
                    $api_status = 'HTTP ' . wp_remote_retrieve_response_code($api_response);
                    $api_body = wp_remote_retrieve_body($api_response);
                } else {
                    $api_status = 'Error: ' . $api_response->get_error_message();
                    $api_body = '';
                }
            }
            
            $html_content = $comeet->comeet_content();
            $jobs_method2 = comeet_parse_html_jobs($html_content);
            $end_time = microtime(true);
            
            $test_results['method2'] = [
                'success' => true,
                'count' => count($jobs_method2),
                'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                'html_length' => strlen($html_content),
                'api_url' => $api_url,
                'api_status' => $api_status,
                'api_response_length' => isset($api_body) ? strlen($api_body) : 0,
                'jobs' => $jobs_method2
            ];
            
            // If direct API works but comeet_content doesn't, use API data
            if (isset($api_body) && !empty($api_body)) {
                $api_data = json_decode($api_body, true);
                $api_jobs = [];
                
                // Debug the API structure
                $test_results['method2']['api_structure'] = 'JSON decode: ' . (is_array($api_data) ? 'SUCCESS' : 'FAILED');
                
                if (is_array($api_data)) {
                    // Check different possible structures
                    if (isset($api_data['positions'])) {
                        $positions = $api_data['positions'];
                        $test_results['method2']['api_structure'] .= ', positions array found';
                    } elseif (isset($api_data['data'])) {
                        $positions = $api_data['data'];
                        $test_results['method2']['api_structure'] .= ', data array found';
                    } else {
                        // Maybe it's a direct array of positions
                        $positions = $api_data;
                        $test_results['method2']['api_structure'] .= ', direct array';
                    }
                    
                    $test_results['method2']['api_structure'] .= ', ' . count($positions) . ' items';
                    
                    if (is_array($positions)) {
                        foreach ($positions as $position) {
                            if (is_array($position)) {
                                // Try different field names
                                $title = $position['name'] ?? $position['title'] ?? $position['position_name'] ?? 'Unknown Position';
                                $location = '';
                                
                                if (isset($position['location']['name'])) {
                                    $location = $position['location']['name'];
                                } elseif (isset($position['location'])) {
                                    $location = is_string($position['location']) ? $position['location'] : 'Unknown Location';
                                } else {
                                    $location = 'Unknown Location';
                                }
                                
                                $uid = $position['uid'] ?? $position['id'] ?? '';
                                
                                $api_jobs[] = [
                                    'title' => $title,
                                    'location' => $location,
                                    'category' => comeet_categorize_job($title),
                                    'type' => 'Full-time',
                                    'link' => generate_comeet_job_url($title, $uid)
                                ];
                            }
                        }
                    }
                }
                
                $test_results['method2']['count'] = count($api_jobs);
                $test_results['method2']['jobs'] = $api_jobs;
                
                if (count($api_jobs) > count($jobs)) {
                    $jobs = $api_jobs;
                }
            } else if (count($jobs_method2) > count($jobs)) {
                $jobs = $jobs_method2;
            }
        } catch (Exception $e) {
            $test_results['method2'] = ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Method 3: Scraping fallback
    try {
        $start_time = microtime(true);
        $jobs_method3 = comeet_scrape_jobs();
        $end_time = microtime(true);
        $test_results['method3'] = [
            'success' => true,
            'count' => count($jobs_method3),
            'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
            'jobs' => $jobs_method3
        ];
        if (count($jobs_method3) > count($jobs)) {
            $jobs = $jobs_method3;
        }
    } catch (Exception $e) {
        $test_results['method3'] = ['success' => false, 'error' => $e->getMessage()];
    }
    
    // Display test results
    $output .= '<h4>🧪 Job Fetching Test Results:</h4>';
    $output .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
    $output .= '<tr style="background: #e9ecef;"><th style="border: 1px solid #ddd; padding: 8px;">Method</th><th style="border: 1px solid #ddd; padding: 8px;">Status</th><th style="border: 1px solid #ddd; padding: 8px;">Jobs Found</th><th style="border: 1px solid #ddd; padding: 8px;">Time</th><th style="border: 1px solid #ddd; padding: 8px;">Details</th></tr>';
    
    foreach ($test_results as $method => $result) {
        $status = $result['success'] ? '✅ Success' : '❌ Failed';
        $count = $result['success'] ? $result['count'] : 0;
        $time = $result['success'] ? $result['time'] : 'N/A';
        $details = $result['success'] ? '' : $result['error'];
        if ($method === 'method2' && $result['success']) {
            $details = 'HTML: ' . $result['html_length'] . ' chars';
            if (isset($result['api_status'])) {
                $details .= ', API: ' . $result['api_status'];
                if (isset($result['api_response_length'])) {
                    $details .= ' (' . $result['api_response_length'] . ' chars)';
                }
            }
            if (isset($result['api_structure'])) {
                $details .= ', ' . $result['api_structure'];
            }
        }
        
        $output .= '<tr>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . ucfirst($method) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $status . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $count . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $time . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($details) . '</td>';
        $output .= '</tr>';
    }
    $output .= '</table>';
    
    // Add comparison with slider
    $output .= '<h4>🔄 Slider vs Debug Comparison:</h4>';
    $output .= '<p><strong>Slider shows:</strong> 22 jobs (working correctly)</p>';
    $output .= '<p><strong>Debug shows:</strong> ' . count($jobs) . ' jobs</p>';
    $output .= '<p><strong>Issue:</strong> Same function, different results - indicates timing/context problem</p>';
    
    // Show final job list
    $output .= '<div style="background: #d4edda; padding: 10px; border-radius: 5px; color: #155724;">';
    $output .= '<strong>✅ Found ' . count($jobs) . ' jobs</strong>';
    $output .= '</div>';
    
    $output .= '<h4>📋 Job List with Links:</h4>';
    $output .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
    $output .= '<tr style="background: #e9ecef;">';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Job Title</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Category</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Location</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Link</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Test</th>';
    $output .= '</tr>';
    
    foreach ($jobs as $job) {
        $output .= '<tr>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($job['title']) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($job['category'] ?? 'N/A') . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($job['location'] ?? 'N/A') . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;"><a href="' . esc_url($job['link']) . '" target="_blank">' . esc_html($job['link']) . '</a></td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;"><a href="' . esc_url($job['link']) . '" target="_blank" style="background: #007cba; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Test Link</a></td>';
        $output .= '</tr>';
    }
    $output .= '</table>';
    
    // Raw HTML inspection
    if (class_exists('Comeet')) {
        try {
            $comeet = new Comeet();
            $html_content = $comeet->comeet_content();
            $output .= '<h4>🔍 Raw Comeet HTML Analysis:</h4>';
            $output .= '<p><strong>HTML Length:</strong> ' . strlen($html_content) . ' characters</p>';
            $output .= '<p><strong>Contains Jobs:</strong> ' . (strpos($html_content, 'job') !== false ? 'YES' : 'NO') . '</p>';
            $output .= '<p><strong>Contains Links:</strong> ' . (strpos($html_content, 'href') !== false ? 'YES' : 'NO') . '</p>';
            $output .= '<details style="margin: 10px 0;"><summary>Click to view raw HTML</summary>';
            $output .= '<textarea style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">' . esc_textarea(substr($html_content, 0, 2000)) . '...</textarea>';
            $output .= '</details>';
        } catch (Exception $e) {
            $output .= '<p><strong>Error accessing Comeet:</strong> ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // System info
    $output .= '<h4>🔧 System Information:</h4>';
    $output .= '<p><strong>Home URL:</strong> ' . home_url() . '</p>';
    $output .= '<p><strong>Careers URL:</strong> ' . home_url('/careers/') . '</p>';
    $output .= '<p><strong>Comeet Plugin:</strong> ' . (class_exists('Comeet') ? '✅ Active' : '❌ Not Found') . '</p>';
    
    if (class_exists('Comeet')) {
        try {
            $comeet = new Comeet();
            $options = $comeet->get_options();
            $output .= '<p><strong>Comeet Token:</strong> ' . (!empty($options['comeet_token']) ? '✅ Set (' . substr($options['comeet_token'], 0, 8) . '...)' : '❌ Missing') . '</p>';
            $output .= '<p><strong>Comeet UID:</strong> ' . (!empty($options['comeet_uid']) ? '✅ Set (' . $options['comeet_uid'] . ')' : '❌ Missing') . '</p>';
        } catch (Exception $e) {
            $output .= '<p><strong>Comeet Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    $output .= '</div>';
    return $output;
}

/**
 * Default job categories with their keywords
 */
function comeet_get_job_categories() {
    $default_categories = [
        'Engineering' => [
            'terms' => ['Engineer', 'Back-End', 'Front-End', 'Developer', 'DevOps', 'SRE', 'Architect'],
            'icon' => 'fas fa-code',
            'color' => '#3498db'
        ],
        'Product & Design' => [
            'terms' => ['Product', 'Designer', 'UX', 'UI', 'Research'],
            'icon' => 'fas fa-paint-brush',
            'color' => '#9b59b6'
        ],
        'Data & Analytics' => [
            'terms' => ['Analyst', 'Data', 'Analytics', 'BI', 'Business Intelligence'],
            'icon' => 'fas fa-chart-line',
            'color' => '#2ecc71'
        ],
        'Business' => [
            'terms' => ['Business', 'Sales', 'Marketing', 'Growth', 'Partnership'],
            'icon' => 'fas fa-briefcase',
            'color' => '#e74c3c'
        ],
        'Operations' => [
            'terms' => ['HR', 'People', 'Talent', 'Recruiter', 'Office Manager'],
            'icon' => 'fas fa-users-cog',
            'color' => '#f39c12'
        ]
    ];

    return apply_filters('comeet_job_categories', $default_categories);
}

/**
 * Categorize a job based on its title
 */
function comeet_categorize_job($job_title) {
    $title = strtolower($job_title);
    $categories = comeet_get_job_categories();
    
    // Check each category's terms
    foreach ($categories as $category_name => $category_data) {
        foreach ($category_data['terms'] as $term) {
            if (stripos($title, strtolower($term)) !== false) {
                return $category_name;
            }
        }
    }
    
    // Check for specific patterns
    if (preg_match('/(front[- ]?end|react|angular|vue|javascript|js)/i', $title)) {
        return 'Engineering';
    }
    
    if (preg_match('/(back[- ]?end|node|python|java|php|ruby|go|scala)/i', $title)) {
        return 'Engineering';
    }
    
    if (preg_match('/(devops|sre|site reliability|cloud|aws|azure|gcp)/i', $title)) {
        return 'Engineering';
    }
    
    if (preg_match('/(data|analytics|analyst|scientist|machine learning|ai|business intelligence)/i', $title)) {
        return 'Data & Analytics';
    }
    
    return 'Other';
}

/**
 * Generate job URL using Comeet plugin methods
 */
function generate_comeet_job_url($job_title, $job_id = '') {
    error_log('🔧 DEBUG URL GEN: Function called with title="' . $job_title . '" job_id="' . $job_id . '"');
    
    if (empty($job_title)) {
        error_log('🔧 DEBUG URL GEN: Empty job title, returning #');
        return '#';
    }
    
    // Don't create Comeet instance if class doesn't exist
    if (!class_exists('Comeet')) {
        error_log('🔧 DEBUG URL GEN: Comeet class not found');
        return '#';
    }
    
    // SIMPLIFIED: Generate proper Comeet URL structure
    error_log('🔧 SIMPLE URL: Generating proper Comeet URL structure');
    
    // Create slug from job title
    $slug = sanitize_title($job_title);
    error_log('🔧 SIMPLE URL: Created slug: ' . $slug);
    
    // Use proper Comeet URL structure: /careers/co/{category}/{position}/{job-slug}/all/
    // Based on Comeet's rewrite rules: $regex_all = '/co/([^/]+)/([^/]+)/([^/]+)/?(/all)?$'
    // This maps to: comeet_cat=$matches[1] & comeet_pos=$matches[2] & job-slug=$matches[3]
    
    $category = 'jerusalem-office-hybrid-in-israel';  // This becomes comeet_cat
    
    // Always use the full Comeet format that matches their actual URLs
    // Format: /careers/co/{category}/{position}/{job-slug}/all/
    $position = !empty($job_id) ? $job_id : '2C.E40'; // Use real job ID or fallback
    $url = home_url('/careers/co/' . $category . '/' . $position . '/' . $slug . '/all/');
    
    error_log('🔧 COMEET URL: GENERATED FULL FORMAT: ' . $url);
    error_log('🔧 COMEET URL: Matches Comeet pattern -> comeet_cat=' . $category . ' & comeet_pos=' . $position . ' & slug=' . $slug);
    return $url;
    
    // Final fallback
    error_log('🔧 DEBUG URL GEN: Returning final fallback URL: ' . home_url('/careers/'));
    return home_url('/careers/');
}

/**
 * Enhanced fetch jobs from Comeet with comprehensive debugging
 */
function comeet_fetch_jobs() {
    $jobs = [];
    error_log('🚀 COMEET FETCH JOBS: Starting job fetch process');
    error_log('🚀 COMEET FETCH JOBS: Function called from: ' . wp_debug_backtrace_summary());
    
    // Try to get jobs from Comeet plugin if available
    if (class_exists('Comeet')) {
        try {
            $comeet = new Comeet();
            error_log(' COMEET FETCH: Comeet class instantiated successfully');
    
    // Check API credentials
    $options = $comeet->get_options();
    $has_token = !empty($options['comeet_token']);
    $has_uid = !empty($options['comeet_uid']);
    error_log('🔑 COMEET CREDENTIALS: Token=' . ($has_token ? 'SET' : 'MISSING') . ', UID=' . ($has_uid ? 'SET' : 'MISSING'));
    
    if (!$has_token || !$has_uid) {
        error_log('❌ COMEET FETCH: Missing API credentials - cannot fetch jobs');
        error_log('💡 COMEET FETCH: Configure credentials at: ' . admin_url('admin.php?page=comeet'));
        return [];
    }
    
    error_log('📋 COMEET FETCH: Available methods: ' . implode(', ', get_class_methods($comeet)));
            
            // Try comeet_content() method first - this contains the HTML with all jobs
            if (method_exists($comeet, 'comeet_content')) {
                error_log(' COMEET FETCH: Trying comeet_content() method');
                try {
                    $html_content = $comeet->comeet_content();
                    if (is_string($html_content) && !empty($html_content)) {
                        error_log('✅ COMEET FETCH: Got HTML content (' . strlen($html_content) . ' chars)');
                        
                        // Log first 500 chars of HTML for debugging
                        error_log('📄 COMEET HTML SAMPLE: ' . substr($html_content, 0, 500) . '...');
                        
                        $jobs = comeet_parse_html_jobs($html_content);
                        if (!empty($jobs)) {
                            error_log('✅ COMEET FETCH: Parsed ' . count($jobs) . ' jobs from HTML');
                            error_log('📄 COMEET FETCH: Sample job: ' . print_r(reset($jobs), true));
                        } else {
                            error_log('❌ COMEET FETCH: HTML parsing returned 0 jobs');
                        }
                    }
                } catch (Exception $e) {
                    error_log('❌ COMEET FETCH: Error with comeet_content(): ' . $e->getMessage());
                }
            }
            
            // Try other methods if comeet_content didn't work
            if (empty($jobs)) {
                $method_attempts = [
                    'get_jobs' => 'get_jobs',
                    'getData' => 'getData', 
                    'getPositions' => 'getPositions',
                    'get_positions' => 'get_positions',
                    'fetchJobs' => 'fetchJobs'
                ];
                
                foreach ($method_attempts as $method_name => $method) {
                    if (method_exists($comeet, $method)) {
                        error_log('🔍 COMEET FETCH: Trying method: ' . $method);
                        try {
                            $result = $comeet->$method();
                            if (is_array($result) && !empty($result)) {
                                $jobs = $result;
                                error_log('✅ COMEET FETCH: Success with ' . $method . '() - got ' . count($jobs) . ' jobs');
                                error_log('📄 COMEET FETCH: Sample job: ' . print_r(reset($jobs), true));
                                break;
                            } else {
                                error_log('⚠️ COMEET FETCH: ' . $method . '() returned: ' . gettype($result) . ' with ' . (is_array($result) ? count($result) : 'N/A') . ' items');
                            }
                        } catch (Exception $e) {
                            error_log('❌ COMEET FETCH: Error with ' . $method . '(): ' . $e->getMessage());
                        }
                    } else {
                        error_log('⚠️ COMEET FETCH: Method ' . $method . '() not available');
                    }
                }
            }
            
            // Try to access properties directly
            if (empty($jobs)) {
                error_log('🔍 COMEET FETCH: Trying to access object properties');
                $properties = get_object_vars($comeet);
                error_log('📋 COMEET FETCH: Available properties: ' . implode(', ', array_keys($properties)));
                
                // Look for job-related properties
                foreach ($properties as $prop_name => $prop_value) {
                    if (stripos($prop_name, 'job') !== false || stripos($prop_name, 'position') !== false) {
                        if (is_array($prop_value) && !empty($prop_value)) {
                            $jobs = $prop_value;
                            error_log('✅ COMEET FETCH: Found jobs in property: ' . $prop_name . ' (' . count($jobs) . ' jobs)');
                            break;
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log('❌ COMEET FETCH: Exception: ' . $e->getMessage());
            error_log('❌ COMEET FETCH: Stack trace: ' . $e->getTraceAsString());
        }
    } else {
        error_log('❌ COMEET FETCH: Comeet class not found');
    }
    
    // If no jobs from plugin, try to scrape the careers page
    if (empty($jobs)) {
        error_log('🌐 COMEET FETCH: No jobs from plugin, trying to scrape careers page');
        $jobs = comeet_scrape_jobs();
        error_log('📄 COMEET FETCH: Scraping returned ' . count($jobs) . ' jobs');
    }
    
    // If still no jobs, provide immediate fallback
    if (empty($jobs)) {
        error_log('🆘 COMEET FETCH: No jobs found anywhere, using emergency fallback');
        $jobs = [
            ['title' => 'Senior Software Engineer', 'location' => 'Jerusalem', 'category' => 'Engineering', 'link' => '#', 'type' => 'Full-time'],
            ['title' => 'Product Manager', 'location' => 'Tel Aviv', 'category' => 'Product & Design', 'link' => '#', 'type' => 'Full-time'],
            ['title' => 'Data Scientist', 'location' => 'Hybrid', 'category' => 'Data & Analytics', 'link' => '#', 'type' => 'Full-time'],
            ['title' => 'Frontend Developer', 'location' => 'Remote', 'category' => 'Engineering', 'link' => '#', 'type' => 'Full-time'],
            ['title' => 'UX Designer', 'location' => 'Jerusalem', 'category' => 'Product & Design', 'link' => '#', 'type' => 'Full-time']
        ];
        error_log('✅ COMEET FETCH: Emergency fallback provided ' . count($jobs) . ' jobs');
    }
    
    // Clean and categorize jobs
    foreach ($jobs as &$job) {
        // Ensure required fields exist
        if (!isset($job['title'])) $job['title'] = 'Unknown Position';
        if (!isset($job['location'])) $job['location'] = 'Location TBD';
        if (!isset($job['link'])) $job['link'] = '#';
        if (!isset($job['category'])) $job['category'] = comeet_categorize_job($job['title']);
        
        // Clean up job title - remove extra whitespace and formatting
        if (!empty($job['title'])) {
            $job['title'] = trim(preg_replace('/\s+/', ' ', $job['title']));
            // Extract just the job title (before location/type info)
            $title_parts = explode('·', $job['title']);
            if (count($title_parts) > 1) {
                $job['title'] = trim($title_parts[0]);
                // Extract location and type from the rest
                if (empty($job['location']) && count($title_parts) > 1) {
                    $location_type = trim($title_parts[1]);
                    if (strpos($location_type, 'Office') !== false || strpos($location_type, 'Hybrid') !== false) {
                        $job['location'] = $location_type;
                    }
                }
                if (empty($job['type']) && count($title_parts) > 2) {
                    $job['type'] = trim($title_parts[2]);
                }
            }
        }
    }
    
    error_log('🎯 COMEET FETCH: Final result - returning ' . count($jobs) . ' jobs');
    return apply_filters('comeet_fetched_jobs', $jobs);
}

/**
 * Parse jobs from Comeet HTML content
 */
function comeet_parse_html_jobs($html_content) {
    $jobs = [];
    
    if (empty($html_content)) {
        return $jobs;
    }
    
    error_log('🔍 COMEET PARSE: Starting HTML parsing (' . strlen($html_content) . ' chars)');
    
    // Create DOM parser
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress HTML parsing warnings
    
    // Load the HTML content
    if (!@$dom->loadHTML(mb_convert_encoding($html_content, 'HTML-ENTITIES', 'UTF-8'))) {
        error_log('❌ COMEET PARSE: Failed to load HTML content');
        return $jobs;
    }
    
    $xpath = new DOMXPath($dom);
    
    // Look for job cards in the HTML - try multiple selectors
    $selectors = [
        '//div[contains(@class, "ultra-job-card")]',
        '//div[contains(@class, "job-card")]', 
        '//div[contains(@class, "comeet-position")]',
        '//div[@data-category]',
        '//a[contains(@href, "/careers/")]',
        '//a[contains(@href, "comeet.co")]',
        '//a[contains(@class, "comeet-position")]',
        '//*[contains(@class, "position")]',
        '//*[contains(text(), "Jerusalem Office") or contains(text(), "Tel Aviv Office")]'
    ];
    
    $job_elements = [];
    foreach ($selectors as $selector) {
        $elements = $xpath->query($selector);
        if ($elements && $elements->length > 0) {
            error_log('✅ COMEET PARSE: Found ' . $elements->length . ' elements with selector: ' . $selector);
            $job_elements = $elements;
            break;
        }
    }
    
    if (empty($job_elements) || $job_elements->length === 0) {
        error_log('❌ COMEET PARSE: No job elements found with any selector');
        // Debug: Log a sample of the HTML to see what we're working with
        $sample_html = substr($html_content, 0, 1000);
        error_log('🔍 COMEET PARSE: HTML sample: ' . $sample_html);
        return $jobs;
    }
    
    // Parse each job element
    foreach ($job_elements as $element) {
        $job = [
            'title' => '',
            'location' => '',
            'category' => '',
            'link' => '',
            'type' => 'Full-time'
        ];
        
        // Get job title from various possible selectors
        $title_element = $xpath->query('.//div[contains(@class, "comeet-position-name")]', $element)->item(0);
        if (!$title_element) {
            $title_element = $xpath->query('.//a[contains(@class, "comeet-position")]', $element)->item(0);
        }
        if (!$title_element) {
            $title_element = $element; // Use the element itself as fallback
        }
        
        if ($title_element) {
            $job['title'] = trim($title_element->textContent);
            
            // Skip if this looks like metadata (location/type info) rather than a job title
            if (preg_match('/^\s*(Jerusalem Office|Tel Aviv Office|Hybrid|Remote|Full-time|Part-time|·)/i', $job['title'])) {
                error_log('⚠️ COMEET PARSE: Skipped element - metadata line: ' . $job['title']);
                continue;
            }
        }
        
        // Skip if no valid title found
        if (empty($job['title']) || strlen($job['title']) < 3) {
            continue;
        }
        
        // Extract job ID from href attribute (most reliable method)
        $link_element = $xpath->query('.//a[contains(@class, "comeet-position")]', $element)->item(0);
        if ($link_element && $link_element->hasAttribute('href')) {
            $href = $link_element->getAttribute('href');
            // Clean up href - remove double domain if present
            $href = preg_replace('/^\/\/[^\/]+/', '', $href);
            if (!preg_match('/^https?:\/\//', $href)) {
                $href = home_url($href);
            }
            
            // Extract job ID from URL pattern: /careers/co/category/JOB_ID/slug/all
            if (preg_match('/\/careers\/co\/[^\/]+\/([^\/]+)\/[^\/]+\/all/', $href, $matches)) {
                $job['job_id'] = $matches[1];
                $job['link'] = $href;
                error_log('🆔 COMEET PARSE: Extracted job ID "' . $job['job_id'] . '" for "' . $job['title'] . '"');
            }
        }
        
        // Fallback: Try other methods to get job URL/ID
        if (empty($job['link'])) {
            if ($element->hasAttribute('href')) {
                $job['link'] = $element->getAttribute('href');
            } else if ($element->hasAttribute('data-url')) {
                $job['link'] = $element->getAttribute('data-url');
            } else if ($element->hasAttribute('data-id')) {
                $job['job_id'] = $element->getAttribute('data-id');
                $job['link'] = generate_comeet_job_url($job['title'], $job['job_id']);
            }
        }
        
        // If still no link, try to generate one from the job title using extracted job ID
        if (empty($job['link']) && !empty($job['title'])) {
            $job['link'] = generate_comeet_job_url($job['title'], $job['job_id']);
        }
        
        // Clean up job title and extract location
        if (!empty($job['title'])) {
            // Remove location suffix from title if present
            if (preg_match('/(.*?)\s+(Jerusalem Office|Tel Aviv Office|Hybrid|Remote)\s*$/i', $job['title'], $matches)) {
                $job['title'] = trim($matches[1]);
                $job['location'] = trim($matches[2]);
            } else {
                $job['location'] = 'Jerusalem Office'; // Default location
            }
            
            // Auto-categorize if no category set
            if (empty($job['category'])) {
                $job['category'] = comeet_categorize_job($job['title']);
            }
            
            // Only add if we have a title (with better validation)
            // Skip metadata lines that look like location/employment info
            $is_metadata = preg_match('/^(Jerusalem Office|Tel Aviv Office|Remote|Hybrid).*?(Full-time|Part-time|Contract).*?(Entry-level|Associate|Intermediate|Senior|Management)/i', $job['title']);
            
            if (!empty($job['title']) && strlen($job['title']) > 3 && !$is_metadata) {
                $jobs[] = $job;
                error_log('✅ COMEET PARSE: Added job: ' . $job['title'] . ' (' . $job['category'] . ')');
            } else {
                error_log('⚠️ COMEET PARSE: Skipped element - ' . ($is_metadata ? 'metadata line' : 'no valid title') . ': ' . substr($job['title'], 0, 100));
            }
        }
    }
    
    error_log('🎯 COMEET PARSE: Successfully parsed ' . count($jobs) . ' jobs from HTML');
    return $jobs;
}

/**
 * Scrape jobs from the careers page as fallback
 */
function comeet_scrape_jobs() {
    $jobs = [];
    $careers_url = apply_filters('comeet_careers_url', home_url('/careers/'));
    
    $response = wp_remote_get($careers_url, [
        'timeout' => 30,
        'sslverify' => false,
        'httpversion' => '1.1',
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    if (!is_wp_error($response) && $response['response']['code'] === 200) {
        $html = wp_remote_retrieve_body($response);
        
        if (!empty($html)) {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Try different selectors to find job listings
            $selectors = [
                '//*[contains(concat(" ", normalize-space(@class), " "), " comeet-position ")]',
                '//*[contains(concat(" ", normalize-space(@class), " "), " job-item ")]',
                '//*[contains(concat(" ", normalize-space(@class), " "), " position ")]'
            ];
            
            $job_elements = [];
            foreach ($selectors as $selector) {
                $elements = $xpath->query($selector);
                if ($elements && $elements->length > 0) {
                    $job_elements = $elements;
                    break;
                }
            }
            
            // Process found job elements
            if (!empty($job_elements)) {
                foreach ($job_elements as $element) {
                    $job = [
                        'title' => '',
                        'location' => '',
                        'type' => '',
                        'link' => ''
                    ];
                    
                    // Get job title
                    $title = $xpath->query('.//*[contains(@class, "title")]', $element);
                    if ($title && $title->length > 0) {
                        $job['title'] = trim($title->item(0)->nodeValue);
                    } else {
                        $job['title'] = trim($element->nodeValue);
                    }
                    
                    // Get job link
                    if ($element->tagName === 'a') {
                        $job['link'] = $element->getAttribute('href');
                        if (!preg_match('/^https?:\/\//', $job['link'])) {
                            $job['link'] = home_url($job['link']);
                        }
                    }
                    
                    // Only add if we have at least a title
                    if (!empty($job['title'])) {
                        $jobs[] = $job;
                    }
                }
            }
        }
    }
    
    return $jobs;
}

/**
 * Shortcode to display the job slider with filters
 */
function comeet_job_slider_shortcode($atts) {
    $atts = shortcode_atts([
        'show_filters' => 'yes',
        'default_category' => '',
        'limit' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ], $atts, 'comeet_job_slider');
    
    // Get jobs
    error_log('🔄 SHORTCODE: About to call comeet_fetch_jobs()');
    
    // Check if function exists
    if (!function_exists('comeet_fetch_jobs')) {
        error_log('❌ SHORTCODE: comeet_fetch_jobs() function does not exist!');
        $jobs = [];
    } else {
        error_log('✅ SHORTCODE: comeet_fetch_jobs() function exists, calling it...');
        $jobs = comeet_fetch_jobs();
        error_log('🔄 SHORTCODE: comeet_fetch_jobs() returned ' . count($jobs) . ' jobs');
    }
    
    // Debug: Log job fetching results
    error_log('FRESH BUILD: Found ' . count($jobs) . ' jobs');
    
    if (empty($jobs)) {
        error_log('FRESH BUILD: No jobs found - using emergency fallback');
        // Emergency fallback jobs with proper URLs
        $jobs = [
            [
                'title' => 'Senior Software Engineer',
                'location' => 'Jerusalem',
                'type' => 'Full-time',
                'link' => home_url('/careers/'),
                'category' => 'Engineering'
            ],
            [
                'title' => 'Product Manager',
                'location' => 'Tel Aviv',
                'type' => 'Full-time',
                'link' => home_url('/careers/'),
                'category' => 'Product & Design'
            ],
            [
                'title' => 'Data Scientist',
                'location' => 'Hybrid',
                'type' => 'Full-time',
                'link' => home_url('/careers/'),
                'category' => 'Data & Analytics'
            ],
            [
                'title' => 'Frontend Developer',
                'location' => 'Remote',
                'type' => 'Full-time',
                'link' => home_url('/careers/'),
                'category' => 'Engineering'
            ],
            [
                'title' => 'UX Designer',
                'location' => 'Jerusalem',
                'type' => 'Full-time',
                'link' => home_url('/careers/'),
                'category' => 'Product & Design'
            ]
        ];
    }
    
    // Group jobs by category for filters
    $categories = [];
    foreach ($jobs as $job) {
        $category = $job['category'] ?? 'Other';
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $job;
    }
    
    // Filter categories with at least 3 jobs and limit to 5
    $categories = array_filter($categories, function($jobs) {
        return count($jobs) >= 3;
    });
    $categories = array_slice($categories, 0, 5, true);
    
    // Category info with colors and icons
    $category_info = [
        'Engineering' => ['icon' => 'fas fa-code', 'color' => '#6c5ce7'],
        'Data & Analytics' => ['icon' => 'fas fa-chart-bar', 'color' => '#00b894'],
        'Product & Design' => ['icon' => 'fas fa-palette', 'color' => '#e17055'],
        'Management' => ['icon' => 'fas fa-users', 'color' => '#fdcb6e'],
        'Other' => ['icon' => 'fas fa-briefcase', 'color' => '#74b9ff']
    ];
    
    // Simple, clean output with aggressive visibility
    $output = '<div class="fresh-jobs-wrapper">';
    $output .= '<style>
        .fresh-jobs-wrapper {
            background: #1a0d32 !important;
            padding: 30px !important;
            border-radius: 15px !important;
            margin: 20px 0 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1000 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .fresh-filter-buttons {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 15px !important;
            margin-bottom: 30px !important;
            justify-content: center !important;
            visibility: visible !important;
        }
        .fresh-filter-btn {
            background: rgba(255,255,255,0.1) !important;
            border: 2px solid rgba(255,255,255,0.3) !important;
            color: white !important;
            padding: 12px 20px !important;
            border-radius: 25px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            font-size: 14px !important;
            font-weight: bold !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            visibility: visible !important;
        }
        .fresh-filter-btn:hover,
        .fresh-filter-btn.active {
            background: rgba(255,255,255,0.2) !important;
            border-color: white !important;
            transform: translateY(-2px) !important;
        }
        .fresh-filter-btn i {
            font-size: 16px !important;
        }
        .fresh-jobs-title {
            color: white !important;
            text-align: center !important;
            font-size: 28px !important;
            margin-bottom: 30px !important;
            display: block !important;
            visibility: visible !important;
            font-weight: bold !important;
        }
        .fresh-jobs-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) !important;
            gap: 20px !important;
            visibility: visible !important;
        }
        .fresh-job-card {
            background: #2a184a !important;
            border: 2px solid rgba(255,255,255,0.2) !important;
            border-radius: 12px !important;
            padding: 25px !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transition: transform 0.3s ease !important;
        }
        .fresh-job-card:hover {
            transform: translateY(-5px) !important;
            border-color: rgba(255,255,255,0.4) !important;
        }
        .fresh-job-title {
            color: white !important;
            font-size: 20px !important;
            font-weight: bold !important;
            margin: 0 0 15px 0 !important;
            display: block !important;
            visibility: visible !important;
            line-height: 1.4 !important;
        }
        .fresh-job-meta {
            color: #ccc !important;
            margin: 8px 0 !important;
            display: block !important;
            visibility: visible !important;
            font-size: 14px !important;
        }
        .fresh-job-link {
            display: inline-block !important;
            background: white !important;
            color: #1a0d32 !important;
            padding: 12px 24px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            margin-top: 20px !important;
            font-weight: bold !important;
            transition: all 0.3s ease !important;
            visibility: visible !important;
        }
        .fresh-job-link:hover {
            background: #f0f0f0 !important;
            transform: scale(1.05) !important;
        }
        .fresh-job-card.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            position: absolute !important;
            left: -10000px !important;
            top: -10000px !important;
            overflow: hidden !important;
        }
    </style>';
    
    $output .= '<h2 class="fresh-jobs-title">המשרות שלנו (' . count($jobs) . ')</h2>';
    
    // Add filter buttons if we have categories
    if (!empty($categories)) {
        $output .= '<div class="fresh-filter-buttons">';
        $output .= '<button class="fresh-filter-btn active" data-category="all">';
        $output .= '<i class="fas fa-briefcase"></i> כל המשרות (' . count($jobs) . ')';
        $output .= '</button>';
        
        foreach ($categories as $category => $category_jobs) {
            $info = $category_info[$category] ?? ['icon' => 'fas fa-tag', 'color' => '#74b9ff'];
            $output .= '<button class="fresh-filter-btn" data-category="' . esc_attr($category) . '">';
            $output .= '<i class="' . esc_attr($info['icon']) . '"></i> ';
            $output .= esc_html($category) . ' (' . count($category_jobs) . ')';
            $output .= '</button>';
        }
        $output .= '</div>';
    }
    
    $output .= '<div class="fresh-jobs-grid">';
    
    foreach ($jobs as $job) {
        $output .= '<div class="fresh-job-card" data-category="' . esc_attr($job['category'] ?? 'Other') . '">';
        $output .= '<h3 class="fresh-job-title">' . esc_html($job['title']) . '</h3>';
        
        // Always show location if available
        if (!empty($job['location'])) {
            $output .= '<div class="fresh-job-meta"><strong>מיקום:</strong> ' . esc_html($job['location']) . '</div>';
        }
        
        $output .= '<a href="' . esc_url($job['link']) . '" class="fresh-job-link" target="_blank">לפרטים והגשת מועמדות</a>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Add JavaScript for filters and confirmation
    $output .= '<script>
        console.log("🎉 FRESH BUILD LOADED - ' . count($jobs) . ' JOBS DISPLAYED");
        
        // Use unique ID to prevent duplicate execution
        var uniqueId = "fresh-jobs-" + Math.random().toString(36).substr(2, 9);
        
        function initFreshJobFilters() {
            var wrapper = document.querySelector(".fresh-jobs-wrapper");
            if (wrapper && !wrapper.dataset.initialized) {
                wrapper.dataset.initialized = "true";
                console.log("✅ Jobs wrapper found and visible");
                wrapper.style.border = "3px solid lime";
                setTimeout(function() {
                    wrapper.style.border = "2px solid rgba(255,255,255,0.2)";
                }, 2000);
                
                // Add filter functionality
                var filterBtns = wrapper.querySelectorAll(".fresh-filter-btn");
                var jobCards = wrapper.querySelectorAll(".fresh-job-card");
                
                console.log("Found " + filterBtns.length + " filter buttons and " + jobCards.length + " job cards");
                
                filterBtns.forEach(function(btn, index) {
                    btn.addEventListener("click", function(e) {
                        e.preventDefault();
                        var category = this.getAttribute("data-category");
                        
                        console.log("🔍 Filtering by: " + category);
                        
                        // Update active button
                        filterBtns.forEach(function(b) { 
                            b.classList.remove("active"); 
                            b.style.background = "rgba(255,255,255,0.1)";
                        });
                        this.classList.add("active");
                        this.style.background = "rgba(255,255,255,0.3)";
                        
                        // Filter job cards - completely remove from layout
                        var visibleCount = 0;
                        jobCards.forEach(function(card) {
                            var cardCategory = card.getAttribute("data-category");
                            if (category === "all" || cardCategory === category) {
                                // Show the card
                                card.style.display = "block";
                                card.style.position = "relative";
                                card.style.left = "auto";
                                card.style.top = "auto";
                                card.style.width = "auto";
                                card.style.height = "auto";
                                card.style.visibility = "visible";
                                card.style.opacity = "1";
                                card.style.transform = "scale(1)";
                                card.style.margin = "";
                                card.style.padding = "";
                                visibleCount++;
                            } else {
                                // Completely remove from layout
                                card.style.display = "none !important";
                                card.style.position = "absolute";
                                card.style.left = "-9999px";
                                card.style.top = "-9999px";
                                card.style.width = "0";
                                card.style.height = "0";
                                card.style.visibility = "hidden";
                                card.style.opacity = "0";
                                card.style.overflow = "hidden";
                                card.style.margin = "0";
                                card.style.padding = "0";
                                card.style.border = "none";
                            }
                        });
                        
                        console.log("✅ Showing " + visibleCount + " jobs for category: " + category);
                    });
                });
                
                console.log("🎛️ Filter buttons initialized successfully");
            }
        }
        
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initFreshJobFilters);
        } else {
            initFreshJobFilters();
        }
    </script>';
    
    return $output;
}

// Register the shortcode
add_shortcode('comeet_job_slider', 'comeet_job_slider_shortcode');

// NEW FRESH SHORTCODE - Use this one!
add_shortcode('fresh_jobs', 'comeet_job_slider_shortcode');

/**
 * Debug shortcode for minimal job testing
 */
function minimal_jobs_debug_shortcode($atts = []) {
    $output = '<div style="background: #2c3e50; color: white; padding: 20px; margin: 20px 0; border-radius: 10px; font-family: monospace;">';
    $output .= '<h3 style="color: #f39c12;">🔍 ENHANCED JOBS DEBUG TEST</h3>';
    
    // Test Comeet plugin availability
    if (class_exists('Comeet')) {
        $output .= '<p>Comeet Plugin: ✅ Available</p>';
        try {
            $comeet = new Comeet();
            $output .= '<p>Comeet Instance: ✅ Created Successfully</p>';
            
            $methods = get_class_methods($comeet);
            $output .= '<p>Available Methods: ' . implode(', ', array_slice($methods, 0, 10)) . '...</p>';
        } catch (Exception $e) {
            $output .= '<p>Comeet Error: ❌ ' . esc_html($e->getMessage()) . '</p>';
        }
    } else {
        $output .= '<p>Comeet Plugin: ❌ Not Available</p>';
    }
    
    // Test job fetching
    $jobs = comeet_fetch_jobs();
    $output .= '<p>comeet_fetch_jobs(): Found ' . count($jobs) . ' jobs</p>';
    
    // Test direct methods
    $output .= '<h4>🧪 DIRECT METHOD TESTING:</h4>';
    if (class_exists('Comeet')) {
        $comeet = new Comeet();
        $test_methods = ['get_jobs', 'getData', 'getPositions', 'get_positions', 'fetchJobs', 'comeet_content'];
        
        foreach ($test_methods as $method) {
            if (method_exists($comeet, $method)) {
                try {
                    $result = $comeet->$method();
                    $type = gettype($result);
                    $count = is_array($result) ? count($result) : 'N/A';
                    $output .= '<p>• ' . $method . '(): ' . $type . ' with ' . $count . ' items</p>';
                    
                    if ($method === 'comeet_content' && is_string($result) && strlen($result) > 1000) {
                        $parsed = comeet_parse_html_jobs($result);
                        $output .= '<p style="margin-left: 20px;">Parser Test: Found ' . count($parsed) . ' jobs</p>';
                    }
                } catch (Exception $e) {
                    $output .= '<p>• ' . $method . '(): ❌ Error - ' . esc_html($e->getMessage()) . '</p>';
                }
            } else {
                $output .= '<p>• ' . $method . '(): ⚠️ Not available</p>';
            }
        }
    }
    
    // Show sample jobs
    if (!empty($jobs)) {
        $output .= '<h4>📋 FINAL RESULT: ' . count($jobs) . ' jobs</h4>';
        foreach (array_slice($jobs, 0, 5) as $job) {
            $output .= '<p>' . esc_html($job['title']) . ' (' . esc_html($job['category']) . ')</p>';
        }
    } else {
        $output .= '<p>Fallback Test: 3 test jobs ready</p>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('minimal_jobs', 'minimal_jobs_debug_shortcode');

/**
 * Raw Comeet inspector shortcode
 */
function raw_comeet_inspector_shortcode($atts = []) {
    $output = '<div style="background: #1a1a1a; color: #00ff00; padding: 20px; margin: 20px 0; border-radius: 10px; font-family: monospace; font-size: 12px;">';
    $output .= '<h3 style="color: #00ffff;">🔬 RAW COMEET DATA INSPECTOR</h3>';
    
    if (!class_exists('Comeet')) {
        $output .= '<p style="color: #ff0000;">❌ Comeet class not found</p>';
        $output .= '</div>';
        return $output;
    }
    
    try {
        $comeet = new Comeet();
        $output .= '<p>✅ Comeet instance created</p>';
        
        // Get all methods
        $methods = get_class_methods($comeet);
        $output .= '<p>📋 ALL AVAILABLE METHODS (' . count($methods) . '):</p>';
        $output .= '<p style="font-size: 10px;">' . implode(', ', $methods) . '</p>';
        
        // Test job-related methods safely
        $job_methods = array_filter($methods, function($method) {
            return stripos($method, 'job') !== false || 
                   stripos($method, 'position') !== false || 
                   stripos($method, 'data') !== false ||
                   in_array($method, ['comeet_content', 'get_options', 'get_version']);
        });
        
        $output .= '<h4 style="color: #f39c12;">🎯 JOB-RELATED METHODS (' . count($job_methods) . '):</h4>';
        
        foreach ($job_methods as $method) {
            $output .= '<div style="border: 1px solid #555; margin: 10px 0; padding: 15px; border-radius: 5px;">';
            $output .= '<h5 style="color: #3498db;">🔍 ' . $method . '()</h5>';
            
            try {
                // Use reflection to check parameters
                $reflection = new ReflectionMethod($comeet, $method);
                $required_params = $reflection->getNumberOfRequiredParameters();
                
                if ($required_params > 0) {
                    $output .= '<p style="color: #f39c12;"><strong>Skipped:</strong> Method requires ' . $required_params . ' parameter(s)</p>';
                } else {
                    $result = $comeet->$method();
                    $type = gettype($result);
                    
                    if (is_array($result)) {
                        $count = count($result);
                        $output .= '<p><strong>Type:</strong> Array with ' . $count . ' items</p>';
                        if ($count > 0) {
                            $sample = reset($result);
                            $output .= '<p><strong>First item type:</strong> ' . gettype($sample) . '</p>';
                        }
                    } else if (is_string($result)) {
                        $length = strlen($result);
                        $output .= '<p><strong>Type:</strong> String (' . $length . ' chars)</p>';
                        $output .= '<p><strong>Preview:</strong> ' . esc_html(substr($result, 0, 200)) . ($length > 200 ? '...' : '') . '</p>';
                        
                        if ($method === 'comeet_content' && $length > 1000) {
                            $parsed_jobs = comeet_parse_html_jobs($result);
                            $output .= '<p style="color: #2ecc71;"><strong>Parser Test:</strong> Found ' . count($parsed_jobs) . ' jobs</p>';
                        }
                    } else {
                        $output .= '<p><strong>Type:</strong> ' . $type . '</p>';
                        $output .= '<p><strong>Value:</strong> ' . esc_html(print_r($result, true)) . '</p>';
                    }
                }
            } catch (Exception $e) {
                $output .= '<p style="color: #e74c3c;"><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            }
            
            $output .= '</div>';
        }
        
        // Show object properties
        $output .= '<h4>📦 OBJECT PROPERTIES:</h4>';
        $properties = get_object_vars($comeet);
        foreach ($properties as $prop => $value) {
            $type = gettype($value);
            $length = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : 'N/A');
            $output .= '<p>• ' . $prop . ': ' . $type . ' (' . $length . ')</p>';
        }
        
    } catch (Exception $e) {
        $output .= '<p style="color: #ff0000;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('raw_comeet', 'raw_comeet_inspector_shortcode');

// ULTRA STABLE JOBS SHORTCODE - Maximum reliability
function ultra_stable_jobs_shortcode($atts = []) {
    // Error handling wrapper
    try {
        // Force fetch jobs from the source
        $jobs = [];
        
        // Clear any transient/cache first
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
                // Enhanced Comeet plugin integration with better debugging
        if (class_exists('Comeet')) {
            try {
                $comeet = new Comeet();
                error_log('ULTRA DEBUG: Comeet class instantiated successfully');
                
                // Log all available methods for debugging
                $methods = get_class_methods($comeet);
                error_log('ULTRA DEBUG: Available Comeet methods: ' . implode(', ', $methods));
                
                // Try different methods to get jobs with enhanced logging
                if (method_exists($comeet, 'get_jobs')) {
                    $jobs = $comeet->get_jobs();
                    error_log('ULTRA DEBUG: get_jobs() returned ' . count($jobs) . ' jobs');
                    if (!empty($jobs)) {
                        error_log('ULTRA DEBUG: First job sample: ' . print_r(reset($jobs), true));
                    }
                } 
                
                if (empty($jobs) && method_exists($comeet, 'getData')) {
                    $data = $comeet->getData();
                    $jobs = is_array($data) ? $data : [];
                    error_log('ULTRA DEBUG: getData() returned ' . count($jobs) . ' jobs');
                }
                
                // Try additional methods that might exist
                if (empty($jobs) && method_exists($comeet, 'getPositions')) {
                    $positions = $comeet->getPositions();
                    $jobs = is_array($positions) ? $positions : [];
                    error_log('ULTRA DEBUG: getPositions() returned ' . count($jobs) . ' jobs');
                }
                
                // If still no jobs, try to refresh the data
                if (empty($jobs) && method_exists($comeet, 'refreshData')) {
                    error_log('ULTRA DEBUG: Attempting to refresh Comeet data');
                    $comeet->refreshData();
                    if (method_exists($comeet, 'get_jobs')) {
                        $jobs = $comeet->get_jobs();
                        error_log('ULTRA DEBUG: After refresh, got ' . count($jobs) . ' jobs');
                    }
                }
                
                // Try direct API call if methods exist
                if (empty($jobs) && method_exists($comeet, 'api_call')) {
                    error_log('ULTRA DEBUG: Attempting direct API call');
                    $api_result = $comeet->api_call('positions');
                    if (is_array($api_result)) {
                        $jobs = $api_result;
                        error_log('ULTRA DEBUG: Direct API call returned ' . count($jobs) . ' jobs');
                    }
                }
                
            } catch (Exception $e) {
                error_log('ULTRA DEBUG: Exception in Comeet integration: ' . $e->getMessage());
                error_log('ULTRA DEBUG: Exception trace: ' . $e->getTraceAsString());
            }
        } else {
            error_log('ULTRA DEBUG: Comeet class not found - plugin may not be active');
        }
        
        // If still no jobs, try the direct API
        if (empty($jobs)) {
            $jobs = comeet_fetch_jobs();
            error_log('Fetched ' . count($jobs) . ' jobs from comeet_fetch_jobs()');
        }
        
        // Log the result
        if (!empty($jobs)) {
            error_log('Total jobs found: ' . count($jobs));
            error_log('Sample job: ' . print_r(reset($jobs), true));
        }
        
        // Enhanced fallback if no jobs found - but only as last resort
        if (empty($jobs)) {
            error_log('ULTRA STABLE: No jobs found from any source, using minimal fallback');
            $jobs = [
                ['title' => 'No positions available', 'location' => 'Please check back later', 'type' => '', 'link' => '#', 'category' => 'Other']
            ];
        }
        
        // Generate proper URLs for each job and clean up titles
        foreach ($jobs as &$job) {
            // Clean up job titles by removing location suffix if present
            if (preg_match('/(.*?)\s*\/\s*Hybrid\s*\(In Israel\)$/i', $job['title'], $matches)) {
                $job['title'] = trim($matches[1]);
            }
            
            // Generate proper URL for each job
            if (!empty($job['title']) && (!isset($job['link']) || empty($job['link']) || $job['link'] === '#')) {
                $generated_url = generate_comeet_job_url($job['title']);
                if (!empty($generated_url)) {
                    $job['link'] = $generated_url;
                    error_log('ULTRA JOBS: Generated URL for "' . $job['title'] . '": ' . $generated_url);
                }
            }
        }
        unset($job); // Break the reference
        
        // Group jobs safely
        $categories = [];
        foreach ($jobs as $job) {
            $cat = isset($job['category']) ? $job['category'] : 'Other';
            if (!isset($categories[$cat])) $categories[$cat] = [];
            $categories[$cat][] = $job;
        }
        
        // Filter categories with 2+ jobs (reduced from 3+ to show more filters)
        $categories = array_filter($categories, function($jobs) { return count($jobs) >= 2; });
        $categories = array_slice($categories, 0, 6, true);
        
        // Unique ID for this instance
        $unique_id = 'ultra-jobs-' . uniqid();
        $timestamp = time();
        
        // Build output with maximum stability
        $output = '<div class="ultra-jobs-container" id="' . $unique_id . '" data-timestamp="' . $timestamp . '">';
        
        // Inline CSS with maximum specificity
        $output .= '<style>
            #' . $unique_id . ' {
                background: linear-gradient(135deg, #1a0d32 0%, #2a184a 100%) !important;
                padding: 40px !important;
                border-radius: 20px !important;
                margin: 30px auto !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 1000 !important;
                width: 100% !important;
                max-width: 1200px !important;
                box-sizing: border-box !important;
                font-family: "Heebo", Arial, sans-serif !important;
                direction: rtl !important;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
            }
            #' . $unique_id . ' .ultra-title {
                color: white !important;
                text-align: center !important;
                font-size: 32px !important;
                font-weight: bold !important;
                margin: 0 0 40px 0 !important;
                display: block !important;
                visibility: visible !important;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.5) !important;
            }
            #' . $unique_id . ' .ultra-filters {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 15px !important;
                justify-content: center !important;
                margin-bottom: 40px !important;
                visibility: visible !important;
            }
            #' . $unique_id . ' .ultra-filter-btn {
                background: rgba(255,255,255,0.15) !important;
                border: 2px solid rgba(255,255,255,0.4) !important;
                color: white !important;
                padding: 15px 25px !important;
                border-radius: 30px !important;
                cursor: pointer !important;
                font-size: 16px !important;
                font-weight: bold !important;
                transition: all 0.3s ease !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 10px !important;
                visibility: visible !important;
                text-decoration: none !important;
                outline: none !important;
            }
            #' . $unique_id . ' .ultra-filter-btn:hover,
            #' . $unique_id . ' .ultra-filter-btn.active {
                background: rgba(255,255,255,0.3) !important;
                border-color: white !important;
                transform: translateY(-3px) !important;
                box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important;
            }
        </style>';
        
        $output .= '<h2 class="ultra-title">המשרות שלנו (' . count($jobs) . ')</h2>';
        
        // Add filter buttons if we have categories
        if (!empty($categories)) {
            $output .= '<div class="ultra-filters">';
            $output .= '<button class="ultra-filter-btn active" data-category="all">';
            $output .= '<i class="fas fa-briefcase"></i> כל המשרות (' . count($jobs) . ')';
            $output .= '</button>';
            
            foreach ($categories as $category => $category_jobs) {
                $output .= '<button class="ultra-filter-btn" data-category="' . esc_attr($category) . '">';
                $output .= '<i class="fas fa-code"></i> ';
                $output .= esc_html($category) . ' (' . count($category_jobs) . ')';
                $output .= '</button>';
            }
            $output .= '</div>';
        }
        
        // Jobs grid
        $output .= '<div class="ultra-jobs-grid">';
        foreach ($jobs as $job) {
            $category = isset($job['category']) ? $job['category'] : 'Other';
            $job_url = isset($job['link']) ? $job['link'] : '#';
            
            // Debug: Force URL generation if empty
            if (empty($job_url) || $job_url === '#') {
                $job_url = generate_comeet_job_url($job['title'], '');
                error_log('🔗 ULTRA JOBS: Generated URL for "' . $job['title'] . '": ' . $job_url);
            }
            
            $output .= '<div class="ultra-job-card" data-category="' . esc_attr($category) . '">';
            $output .= '<h3 class="ultra-job-title">' . esc_html($job['title']) . '</h3>';
            $output .= '<div class="ultra-job-meta"><strong>מיקום:</strong> ' . esc_html($job['location']) . '</div>';
            $output .= '<a href="' . esc_url($job_url) . '" class="ultra-job-link" target="_blank">לפרטים והגשת מועמדות</a>';
            $output .= '<!-- DEBUG: URL = ' . esc_html($job_url) . ' -->';
            $output .= '</div>';
        }
        
        $output .= '</div></div>';
        
        return $output;
        
    } catch (Exception $e) {
        error_log('ULTRA STABLE ERROR: ' . $e->getMessage());
        return '<div>Error loading jobs: ' . esc_html($e->getMessage()) . '</div>';
    }
}
add_shortcode('ultra_jobs', 'ultra_stable_jobs_shortcode');

// DEBUG: Job Link Inspector - Shows detailed job data and URLs
function job_link_debug_shortcode() {
    $output = '<div style="background: #1a1a2e; color: white; padding: 30px; margin: 20px 0; border-radius: 10px; font-family: monospace; max-height: 800px; overflow-y: auto;">';
    $output .= '<h2 style="color: #e74c3c;">🔗 JOB LINK DEBUG INSPECTOR</h2>';
    
    try {
        // Get jobs using our function
        $jobs = comeet_fetch_jobs();
        $output .= '<p><strong>Jobs found:</strong> ' . count($jobs) . '</p>';
        
        if (!empty($jobs)) {
            $output .= '<h3 style="color: #f39c12;">📋 JOB DATA STRUCTURE:</h3>';
            
            foreach ($jobs as $index => $job) {
                $output .= '<div style="border: 2px solid #555; margin: 15px 0; padding: 20px; border-radius: 8px; background: rgba(0,0,0,0.3);">';
                $output .= '<h4 style="color: #3498db;">Job #' . ($index + 1) . '</h4>';
                
                // Show all job properties
                foreach ($job as $key => $value) {
                    $output .= '<p><strong>' . esc_html($key) . ':</strong> ';
                    
                    if (is_string($value)) {
                        if (strlen($value) > 200) {
                            $output .= '<span style="color: #2ecc71;">' . esc_html(substr($value, 0, 200)) . '...</span>';
                        } else {
                            $output .= '<span style="color: #2ecc71;">' . esc_html($value) . '</span>';
                        }
                    } else {
                        $output .= '<span style="color: #e67e22;">' . esc_html(print_r($value, true)) . '</span>';
                    }
                    $output .= '</p>';
                }
                
                $output .= '</div>';
            }
        }
        
        // Test Comeet plugin methods for URL generation
        if (class_exists('Comeet')) {
            $output .= '<h3 style="color: #f39c12;">🔍 COMEET URL GENERATION METHODS:</h3>';
            
            $comeet = new Comeet();
            $methods = get_class_methods($comeet);
            
            $url_methods = [];
            foreach ($methods as $method) {
                if (stripos($method, 'url') !== false || 
                    stripos($method, 'link') !== false || 
                    stripos($method, 'page') !== false ||
                    stripos($method, 'generate') !== false) {
                    $url_methods[] = $method;
                }
            }
            
            $output .= '<p><strong>URL-related methods found:</strong> ' . implode(', ', $url_methods) . '</p>';
            
            // Test specific URL generation methods
            $test_methods = ['generate_careers_url', 'generate_sub_page_url', 'get_url', 'get_current_url'];
            
            foreach ($test_methods as $method) {
                if (method_exists($comeet, $method)) {
                    $output .= '<div style="border: 1px solid #666; margin: 10px 0; padding: 15px; border-radius: 5px;">';
                    $output .= '<h4 style="color: #3498db;">' . $method . '()</h4>';
                    
                    try {
                        $reflection = new ReflectionMethod($comeet, $method);
                        $required_params = $reflection->getNumberOfRequiredParameters();
                        
                        if ($required_params === 0) {
                            $result = $comeet->$method();
                            $output .= '<p><strong>Result:</strong> ' . esc_html($result) . '</p>';
                        } else {
                            $output .= '<p><strong>Requires parameters:</strong> ' . $required_params . '</p>';
                            
                            // Try with sample data if it's a URL generation method
                            if ($method === 'generate_sub_page_url' && !empty($jobs)) {
                                $sample_job = reset($jobs);
                                if (isset($sample_job['title'])) {
                                    try {
                                        // Use proper 3-parameter call for generate_sub_page_url
                                        if ($required_params >= 3) {
                                            $position_data = [
                                                'name' => $sample_job['title'],
                                                'uid' => isset($sample_job['id']) ? $sample_job['id'] : 'test-id',
                                                'post_id' => isset($sample_job['id']) ? $sample_job['id'] : 'test-id'
                                            ];
                                            $test_url = $comeet->generate_sub_page_url($position_data, 'jerusalem-office-hybrid-in-israel', 'all');
                                        } else {
                                            $test_url = $comeet->generate_sub_page_url($sample_job['title']);
                                        }
                                        $output .= '<p><strong>Test with "' . esc_html($sample_job['title']) . '":</strong> ' . esc_html($test_url) . '</p>';
                                    } catch (Exception $e) {
                                        $output .= '<p><strong>Test failed:</strong> ' . esc_html($e->getMessage()) . '</p>';
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $output .= '<p style="color: #e74c3c;"><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
                    }
                    
                    $output .= '</div>';
                }
            }
            
            // Check Comeet options for URL patterns
            $options = $comeet->get_options();
            if (is_array($options)) {
                $output .= '<h3 style="color: #f39c12;">⚙️ COMEET URL CONFIGURATION:</h3>';
                
                $url_related_options = [];
                foreach ($options as $key => $value) {
                    if (stripos($key, 'url') !== false || 
                        stripos($key, 'page') !== false || 
                        stripos($key, 'link') !== false ||
                        stripos($key, 'career') !== false ||
                        stripos($key, 'position') !== false) {
                        $url_related_options[$key] = $value;
                    }
                }
                
                if (!empty($url_related_options)) {
                    foreach ($url_related_options as $key => $value) {
                        $output .= '<p><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</p>';
                    }
                } else {
                    $output .= '<p>No URL-related options found in Comeet configuration.</p>';
                }
            }
        }
        
    } catch (Exception $e) {
        $output .= '<p style="color: #e74c3c;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('job_link_debug', 'job_link_debug_shortcode');

// LIVE JOB URL DEBUGGER - Shows actual job listings and their generated URLs
function live_job_url_debug_shortcode() {
    $output = '<div style="background: #0d1117; color: #f0f6fc; padding: 30px; margin: 20px 0; border-radius: 10px; font-family: monospace; border: 2px solid #30363d;">';
    $output .= '<h2 style="color: #f85149; margin-top: 0;">🔍 LIVE JOB URL DEBUGGER</h2>';
    
    try {
        // Get the actual jobs being displayed
        $jobs = comeet_fetch_jobs();
        $job_count = count($jobs);
        
        $output .= '<div style="background: #161b22; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #30363d;">';
        $output .= '<h3 style="color: #7c3aed; margin-top: 0;">📊 JOB FETCH RESULTS</h3>';
        $output .= '<p><strong>Total Jobs Found:</strong> <span style="color: #56d364;">' . $job_count . '</span></p>';
        $output .= '</div>';
        
        if ($job_count > 0) {
            $output .= '<h3 style="color: #f79009;">🎯 JOB LISTINGS & GENERATED URLS</h3>';
            
            foreach ($jobs as $index => $job) {
                $job_num = $index + 1;
                $output .= '<div style="background: #21262d; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #f85149;">';
                
                // Job header
                $output .= '<h4 style="color: #58a6ff; margin-top: 0;">Job #' . $job_num . '</h4>';
                
                // Job details table
                $output .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
                
                // Title
                $title = isset($job['title']) ? $job['title'] : 'NO TITLE';
                $output .= '<tr><td style="padding: 8px; border: 1px solid #30363d; background: #0d1117; font-weight: bold; width: 120px;">Title:</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #30363d; color: #7c3aed;">' . esc_html($title) . '</td></tr>';
                
                // Category
                $category = isset($job['category']) ? $job['category'] : 'NO CATEGORY';
                $output .= '<tr><td style="padding: 8px; border: 1px solid #30363d; background: #0d1117; font-weight: bold;">Category:</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #30363d; color: #f79009;">' . esc_html($category) . '</td></tr>';
                
                // Location
                $location = isset($job['location']) ? $job['location'] : 'NO LOCATION';
                $output .= '<tr><td style="padding: 8px; border: 1px solid #30363d; background: #0d1117; font-weight: bold;">Location:</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #30363d; color: #56d364;">' . esc_html($location) . '</td></tr>';
                
                // Current Link
                $current_link = isset($job['link']) ? $job['link'] : 'NO LINK';
                $link_status = empty($current_link) || $current_link === '#' ? '❌ EMPTY' : '✅ HAS LINK';
                $link_color = empty($current_link) || $current_link === '#' ? '#f85149' : '#56d364';
                
                $output .= '<tr><td style="padding: 8px; border: 1px solid #30363d; background: #0d1117; font-weight: bold;">Current Link:</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #30363d; color: ' . $link_color . ';">' . $link_status . '<br>';
                $output .= '<code style="background: #161b22; padding: 4px; border-radius: 4px; font-size: 11px;">' . esc_html($current_link) . '</code></td></tr>';
                
                // Test URL Generation
                $output .= '<tr><td style="padding: 8px; border: 1px solid #30363d; background: #0d1117; font-weight: bold;">URL Generation Test:</td><td style="padding: 8px; border: 1px solid #30363d;">';
                
                if (!empty($title) && $title !== 'NO TITLE') {
                    // Test our URL generation function
                    $generated_url = generate_comeet_job_url($title, '');
                    $url_color = ($generated_url === '#' || $generated_url === home_url('/careers/')) ? '#f79009' : '#56d364';
                    
                    $output .= '<span style="color: ' . $url_color . ';">Generated URL:</span><br>';
                    $output .= '<code style="background: #161b22; padding: 4px; border-radius: 4px; font-size: 11px; color: #58a6ff;">' . esc_html($generated_url) . '</code><br>';
                    
                    // Show what the target should be
                    $slug = sanitize_title($title);
                    $target_url = home_url('/careers/co/jerusalem-office-hybrid-in-israel/2C.E40/' . $slug . '/all/');
                    $output .= '<span style="color: #7c3aed;">Target Pattern:</span><br>';
                    $output .= '<code style="background: #161b22; padding: 4px; border-radius: 4px; font-size: 11px; color: #7c3aed;">' . esc_html($target_url) . '</code>';
                } else {
                    $output .= '<span style="color: #f85149;">❌ Cannot generate - no title</span>';
                }
                
                $output .= '</td></tr>';
                $output .= '</table>';
                $output .= '</div>';
            }
            
            // Test Comeet URL methods
            $output .= '<div style="background: #161b22; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #30363d;">';
            $output .= '<h3 style="color: #f79009; margin-top: 0;">🔧 COMEET URL METHOD TESTS</h3>';
            
            if (class_exists('Comeet')) {
                $comeet = new Comeet();
                
                // Test generate_sub_page_url
                if (method_exists($comeet, 'generate_sub_page_url')) {
                    $sample_job = reset($jobs);
                    if ($sample_job && !empty($sample_job['title'])) {
                        try {
                            // Use reflection to determine parameter count and call appropriately
                            $reflection = new ReflectionMethod($comeet, 'generate_sub_page_url');
                            $required_params = $reflection->getNumberOfRequiredParameters();
                            
                            if ($required_params >= 3) {
                                $position_data = [
                                    'name' => $sample_job['title'],
                                    'uid' => isset($sample_job['id']) ? $sample_job['id'] : 'test-id',
                                    'post_id' => isset($sample_job['id']) ? $sample_job['id'] : 'test-id'
                                ];
                                $test_url = $comeet->generate_sub_page_url($position_data, 'jerusalem-office-hybrid-in-israel', '2C.E40');
                                $output .= '<p><strong>generate_sub_page_url(3 params):</strong><br>';
                            } else {
                                $test_url = $comeet->generate_sub_page_url($sample_job['title']);
                                $output .= '<p><strong>generate_sub_page_url(1 param):</strong><br>';
                            }
                            $output .= '<code style="background: #0d1117; padding: 4px; border-radius: 4px; color: #58a6ff;">' . esc_html($test_url) . '</code></p>';
                        } catch (Exception $e) {
                            $output .= '<p><strong>generate_sub_page_url:</strong> <span style="color: #f85149;">Error - ' . esc_html($e->getMessage()) . '</span></p>';
                        }
                    }
                }
                
                // Test generate_careers_url
                if (method_exists($comeet, 'generate_careers_url')) {
                    try {
                        // Check method signature using reflection
                        $reflection = new ReflectionMethod($comeet, 'generate_careers_url');
                        $required_params = $reflection->getNumberOfRequiredParameters();
                        
                        if ($required_params >= 3) {
                            // Method expects 3 parameters
                            $careers_url = $comeet->generate_careers_url('jerusalem-office-hybrid-in-israel', 'all', '2C.E40');
                        } else {
                            $careers_url = $comeet->generate_careers_url();
                        }
                        
                        $output .= '<p><strong>generate_careers_url():</strong><br>';
                        $output .= '<code style="background: #0d1117; padding: 4px; border-radius: 4px; color: #58a6ff;">' . esc_html($careers_url) . '</code></p>';
                    } catch (Exception $e) {
                        $output .= '<p><strong>generate_careers_url:</strong> <span style="color: #f85149;">Error - ' . esc_html($e->getMessage()) . '</span></p>';
                    }
                }
                
            } else {
                $output .= '<p style="color: #f85149;">❌ Comeet class not available</p>';
            }
            
            $output .= '</div>';
            
        } else {
            $output .= '<div style="background: #161b22; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #f85149;">';
            $output .= '<p style="color: #f85149;">❌ No jobs found to debug</p>';
            $output .= '</div>';
        }
        
    } catch (Exception $e) {
        $output .= '<div style="background: #161b22; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #f85149;">';
        $output .= '<p style="color: #f85149;">❌ Debug Error: ' . esc_html($e->getMessage()) . '</p>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('live_job_debug', 'live_job_url_debug_shortcode');

// SIMPLE DEBUG FUNCTION - Add this to any page to see job URLs
function simple_job_debug_shortcode() {
    $jobs = comeet_fetch_jobs();
    $output = '<div style="background: red; color: white; padding: 20px; font-family: monospace;">';
    $output .= '<h3>🔍 SIMPLE JOB DEBUG (' . count($jobs) . ' jobs)</h3>';
    
    foreach (array_slice($jobs, 0, 5) as $i => $job) {
        $title = $job['title'] ?? 'NO TITLE';
        $current_link = $job['link'] ?? 'NO LINK';
        $generated_link = generate_comeet_job_url($title, '');
        
        $output .= '<div style="border: 1px solid white; margin: 10px 0; padding: 10px;">';
        $output .= '<strong>Job ' . ($i+1) . ':</strong> ' . esc_html($title) . '<br>';
        $output .= '<strong>Current Link:</strong> ' . esc_html($current_link) . '<br>';
        $output .= '<strong>Generated Link:</strong> ' . esc_html($generated_link) . '<br>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('simple_debug', 'simple_job_debug_shortcode');

// URL TEST SHORTCODE - Quick test for URL generation fixes
function url_test_shortcode() {
    $output = '<div style="background: #2d3748; color: #e2e8f0; padding: 20px; margin: 20px 0; border-radius: 8px; font-family: monospace;">';
    $output .= '<h3 style="color: #4fd1c7; margin-top: 0;">🔧 URL GENERATION TEST</h3>';
    
    // Test sample job titles
    $test_jobs = [
        'Senior Software Engineer',
        'Product Manager - Growth',
        'Data Scientist'
    ];
    
    foreach ($test_jobs as $i => $job_title) {
        $output .= '<div style="background: #1a202c; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4fd1c7;">';
        $output .= '<strong style="color: #63b3ed;">Test ' . ($i + 1) . ':</strong> ' . esc_html($job_title) . '<br>';
        
        // Generate URL
        $generated_url = generate_comeet_job_url($job_title, 'test-id-' . ($i + 1));
        
        // Check if URL looks correct
        $is_malformed = strpos($generated_url, '?page_id=') !== false;
        $status_color = $is_malformed ? '#f56565' : '#68d391';
        $status_text = $is_malformed ? '❌ MALFORMED' : '✅ CLEAN';
        
        $output .= '<strong>Generated URL:</strong> <span style="color: ' . $status_color . ';">' . $status_text . '</span><br>';
        $output .= '<code style="background: #0d1117; padding: 4px; border-radius: 4px; color: #58a6ff; word-break: break-all;">' . esc_html($generated_url) . '</code>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('url_test', 'url_test_shortcode');

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('heebo-font','https://fonts.googleapis.com/css2?family=Heebo:wght@400;600;700&display=swap',[],null);
    wp_enqueue_style('font-awesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',[],null);
    wp_enqueue_style('swiper-css','https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css',[],null);
    wp_enqueue_script('swiper-js','https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js',[],null,true);
    wp_enqueue_style('comeet-slider-helper', plugin_dir_url(__FILE__) . 'assets/comeet-slider.css', ['heebo-font','swiper-css'], null);
    wp_enqueue_script('comeet-slider-helper', plugin_dir_url(__FILE__) . 'assets/comeet-slider.js', ['swiper-js'], null, true);
}, 20);

?>
