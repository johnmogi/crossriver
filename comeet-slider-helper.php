<?php
/**
 * Plugin Name: Comeet Slider Helper
 * Description: Enhanced job slider for Comeet with category filtering and RTL support.
 * Version: 2.0.2
 * Author: אביב דיגיטל
 */

if (!defined('ABSPATH')) { exit; }

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
    if (empty($job_title)) {
        return '#';
    }
    
    // Don't create Comeet instance if class doesn't exist
    if (!class_exists('Comeet')) {
        error_log('⚠️ COMEET URL: Comeet class not found');
        return '#';
    }
    
    // Wrap entire function in try-catch for production safety
    try {
        $comeet = new Comeet();
        
        // Try generate_sub_page_url method first (requires 3 parameters)
        if (method_exists($comeet, 'generate_sub_page_url') && !empty($job_title)) {
            try {
                // Check method signature using reflection
                $reflection = new ReflectionMethod($comeet, 'generate_sub_page_url');
                $required_params = $reflection->getNumberOfRequiredParameters();
                
                if ($required_params === 3) {
                    // Method expects (position_data, location, group) - create proper structure
                    // Ensure we have proper array structure to avoid TypeError
                    $position_data = array(
                        'name' => (string)$job_title,
                        'uid' => !empty($job_id) ? (string)$job_id : '2C.E40',
                        'post_id' => !empty($job_id) ? (string)$job_id : '2C.E40',
                        'location' => array('name' => 'Jerusalem Office / Hybrid (In Israel)'),
                        'department' => array('name' => 'All Departments')
                    );
                    $location = 'jerusalem-office-hybrid-in-israel';
                    $group = 'all';
                    
                    // Add try-catch to prevent fatal errors in production
                    try {
                        $url = $comeet->generate_sub_page_url($position_data, $location, $group);
                    } catch (Exception $e) {
                        error_log('⚠️ COMEET URL: generate_sub_page_url failed with proper data: ' . $e->getMessage());
                        $url = null;
                    }
                } else if ($required_params === 1) {
                    $url = $comeet->generate_sub_page_url($job_title);
                } else {
                    error_log('⚠️ COMEET URL: generate_sub_page_url requires ' . $required_params . ' parameters');
                    $url = null;
                }
                
                if (!empty($url) && $url !== '#') {
                    // Check if URL is malformed (contains page_id query parameter)
                    if (strpos($url, '?page_id=') !== false) {
                        error_log('⚠️ COMEET URL: Malformed URL detected: ' . $url);
                        // Extract the path part after page_id= and reconstruct proper URL
                        $parsed = parse_url($url);
                        if (isset($parsed['query'])) {
                            parse_str($parsed['query'], $query_params);
                            if (isset($query_params['page_id'])) {
                                $path_part = $query_params['page_id'];
                                // Clean up the path and create proper URL
                                $clean_path = trim($path_part, '/');
                                $url = home_url('/careers/' . $clean_path . '/');
                                error_log('✅ COMEET URL: Fixed malformed URL to: ' . $url);
                            }
                        }
                    } else {
                        // Check for double domain issue and fix it
                        if (strpos($url, 'c148-cross-river.local/c148-cross-river.local') !== false) {
                            $url = str_replace('c148-cross-river.local/c148-cross-river.local', 'c148-cross-river.local', $url);
                            error_log('✅ COMEET URL: Fixed double domain in URL: ' . $url);
                        }
                        error_log('✅ COMEET URL: Generated URL for "' . $job_title . '": ' . $url);
                    }
                    return $url;
                }
            } catch (Exception $e) {
                error_log('⚠️ COMEET URL: generate_sub_page_url failed: ' . $e->getMessage());
            }
        }
        
        // Try generate_careers_url method
        if (method_exists($comeet, 'generate_careers_url')) {
            try {
                // Check method signature using reflection
                $reflection = new ReflectionMethod($comeet, 'generate_careers_url');
                $required_params = $reflection->getNumberOfRequiredParameters();
                
                if ($required_params >= 3) {
                    // Method expects 3 parameters
                    $base_url = $comeet->generate_careers_url('jerusalem-office-hybrid-in-israel', 'all', '2C.E40');
                } else {
                    $base_url = $comeet->generate_careers_url();
                }
                
                if (!empty($base_url) && !empty($job_title)) {
                    // Create slug from job title
                    $slug = sanitize_title($job_title);
                    $url = rtrim($base_url, '/') . '/' . $slug . '/';
                    error_log('✅ COMEET URL: Generated careers URL: ' . $url);
                    return $url;
                }
            } catch (Exception $e) {
                error_log('⚠️ COMEET URL: generate_careers_url failed: ' . $e->getMessage());
            }
        }
        
        // Try to get URL pattern from Comeet options
        if (method_exists($comeet, 'get_options')) {
            $options = $comeet->get_options();
            if (is_array($options)) {
                // Look for URL patterns in options
                foreach ($options as $key => $value) {
                    if ((stripos($key, 'career') !== false || stripos($key, 'page') !== false) && 
                        !empty($value) && is_string($value) && 
                        (strpos($value, 'http') === 0 || strpos($value, '/') === 0)) {
                        
                        $base_url = $value;
                        if (!empty($job_title)) {
                            $slug = sanitize_title($job_title);
                            $url = rtrim($base_url, '/') . '/' . $slug . '/';
                            error_log('✅ COMEET URL: Generated from options (' . $key . '): ' . $url);
                            return $url;
                        }
                        break;
                    }
                }
            }
        }
        
        // Fallback: construct URL based on site structure
        $careers_base = home_url('/careers/');
        if (!empty($job_title)) {
            // Create URL pattern similar to: /careers/co/location/id/job-title/all/
            $slug = sanitize_title($job_title);
            $location_slug = 'jerusalem-office-hybrid-in-israel';
            $job_id_placeholder = !empty($job_id) ? $job_id : '2C.E40';
            
            $url = $careers_base . 'co/' . $location_slug . '/' . $job_id_placeholder . '/' . $slug . '/all/';
            error_log('✅ COMEET URL: Generated fallback URL: ' . $url);
            return $url;
        }
        
    } catch (Exception $e) {
        error_log('❌ COMEET URL: Error generating job URL: ' . $e->getMessage());
    } catch (TypeError $e) {
        error_log('❌ COMEET URL: TypeError in production: ' . $e->getMessage());
    } catch (Throwable $e) {
        error_log('❌ COMEET URL: Fatal error prevented: ' . $e->getMessage());
    }
    
    // Final fallback
    return home_url('/careers/');
}

/**
 * Enhanced fetch jobs from Comeet with comprehensive debugging
 */
function comeet_fetch_jobs() {
    $jobs = [];
    error_log('🚀 COMEET FETCH JOBS: Starting job fetch process');
    
    // Try to get jobs from Comeet plugin if available
    if (class_exists('Comeet')) {
        try {
            $comeet = new Comeet();
            error_log('✅ COMEET FETCH: Comeet class instantiated successfully');
            
            // Debug: Log available methods
            $methods = get_class_methods($comeet);
            error_log('📋 COMEET FETCH: Available methods: ' . implode(', ', $methods));
            
            // Try comeet_content() method first - this contains the HTML with all jobs
            if (method_exists($comeet, 'comeet_content')) {
                error_log('🔍 COMEET FETCH: Trying comeet_content() method');
                try {
                    $html_content = $comeet->comeet_content();
                    if (is_string($html_content) && !empty($html_content)) {
                        error_log('✅ COMEET FETCH: Got HTML content (' . strlen($html_content) . ' chars)');
                        $jobs = comeet_parse_html_jobs($html_content);
                        if (!empty($jobs)) {
                            error_log('✅ COMEET FETCH: Parsed ' . count($jobs) . ' jobs from HTML');
                            error_log('📄 COMEET FETCH: Sample job: ' . print_r(reset($jobs), true));
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
        
        // Get job title - try multiple approaches for comeet-position elements
        $title_elements = $xpath->query('.//h3[contains(@class, "ultra-job-title")] | .//h3 | .//a[contains(@class, "comeet-position-name")] | .//a', $element);
        if ($title_elements && $title_elements->length > 0) {
            $job['title'] = trim($title_elements->item(0)->textContent);
        }
        
        // If no title found, try getting it from the element's text content or data attributes
        if (empty($job['title'])) {
            // Try data attributes first
            if ($element->hasAttribute('data-name')) {
                $job['title'] = trim($element->getAttribute('data-name'));
            } else if ($element->hasAttribute('data-title')) {
                $job['title'] = trim($element->getAttribute('data-title'));
            } else {
                // Try getting text content and clean it up
                $text_content = trim($element->textContent);
                if (!empty($text_content)) {
                    // Take first line as title
                    $lines = explode("\n", $text_content);
                    $job['title'] = trim($lines[0]);
                }
            }
        }
        
        // Get category from data attribute
        if ($element->hasAttribute('data-category')) {
            $job['category'] = $element->getAttribute('data-category');
        }
        
        // Get job link - try multiple approaches
        $link_elements = $xpath->query('.//a[contains(@class, "ultra-job-link") or contains(@href, "/careers/") or contains(@href, "comeet.co")]', $element);
        if ($link_elements && $link_elements->length > 0) {
            $job['link'] = $link_elements->item(0)->getAttribute('href');
        } else if ($element->tagName === 'a') {
            $job['link'] = $element->getAttribute('href');
        } else {
            // Try data attributes for link
            if ($element->hasAttribute('data-href')) {
                $job['link'] = $element->getAttribute('data-href');
            } else if ($element->hasAttribute('data-url')) {
                $job['link'] = $element->getAttribute('data-url');
            } else if ($element->hasAttribute('data-id')) {
                // Try to generate URL from job ID
                $job_id = $element->getAttribute('data-id');
                $job['link'] = generate_comeet_job_url($job['title'], $job_id);
            }
        }
        
        // If still no link, try to generate one from the job title
        if (empty($job['link']) && !empty($job['title'])) {
            $job['link'] = generate_comeet_job_url($job['title'], '');
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
    $jobs = comeet_fetch_jobs();
    
    // Debug: Log job fetching results
    error_log('FRESH BUILD: Found ' . count($jobs) . ' jobs');
    
    if (empty($jobs)) {
        error_log('FRESH BUILD: No jobs found - using test data');
        // Add test jobs for debugging
        $jobs = [
            [
                'title' => 'Senior Software Engineer',
                'location' => 'Tel Aviv',
                'type' => 'Full-time',
                'link' => '#test1',
                'category' => 'Engineering'
            ],
            [
                'title' => 'Product Manager',
                'location' => 'Jerusalem',
                'type' => 'Full-time',
                'link' => '#test2',
                'category' => 'Product'
            ],
            [
                'title' => 'Data Scientist',
                'location' => 'Hybrid',
                'type' => 'Full-time',
                'link' => '#test3',
                'category' => 'Data'
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
