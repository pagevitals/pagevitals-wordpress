<?php
/**
 * Plugin Name: PageVitals
 * Plugin URI: https://www.pagevitals.com/
 * Description: Monitor the Core Web Vitals of your site and stay fast!
 * Version: 1.0
 * Author: PageVitals
 * Author URI: https://pagevitals.com/
 * License: MIT
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class PageVitals {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_pagevitals_script'));
        add_action('send_headers', array($this, 'add_csp_headers'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=pagevitals">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_plugin_page() {
        add_options_page(
            'PageVitals Settings',
            'PageVitals',
            'manage_options',
            'pagevitals',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('pagevitals_options');
        ?>
        <div class="wrap">
            <h1>PageVitals</h1>
            <br>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'admin/static/logo.png'); ?>" alt="PageVitals">
            <br><br>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'admin/static/hero.webp'); ?>" alt="PageVitals hero" width="300">
        </div>
            <form method="post" action="options.php">
            <?php
                settings_fields('pagevitals_option_group');
                do_settings_sections('pagevitals-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'pagevitals_option_group',
            'pagevitals_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'pagevitals_setting_section',
            'PageVitals Settings',
            array($this, 'section_info'),
            'pagevitals-admin'
        );

        add_settings_field(
            'website_id',
            'Website ID',
            array($this, 'website_id_callback'),
            'pagevitals-admin',
            'pagevitals_setting_section'
        );

        add_settings_field(
            'enable_csp',
            'Adjust Content Security Policy',
            array($this, 'enable_csp_callback'),
            'pagevitals-admin',
            'pagevitals_setting_section'
        );

        add_settings_field(
            'page_selection',
            'Page Selection',
            array($this, 'page_selection_callback'),
            'pagevitals-admin',
            'pagevitals_setting_section'
        );

        add_settings_field(
            'selected_pages',
            'Selected Pages',
            array($this, 'selected_pages_callback'),
            'pagevitals-admin',
            'pagevitals_setting_section'
        );

        // Enqueue custom script
        wp_enqueue_script(
            'pagevitals-admin-script', 
            plugins_url('admin/admin.js', __FILE__), 
            array(), 
            filemtime(plugin_dir_path(__FILE__) . 'admin.js'),
            true
        );
    }

    public function sanitize($input) {
        $sanitary_values = array();
        if (isset($input['website_id'])) {
            $sanitary_values['website_id'] = sanitize_text_field($input['website_id']);
        }
        if (isset($input['enable_csp'])) {
            $sanitary_values['enable_csp'] = $input['enable_csp'];
        }
        if (isset($input['page_selection'])) {
            $sanitary_values['page_selection'] = $input['page_selection'];
        }
        if (isset($input['selected_pages'])) {
            $sanitary_values['selected_pages'] = sanitize_textarea_field($input['selected_pages']);
        }
        return $sanitary_values;
    }

    public function section_info() {
        echo 'Enter your info below and hit Save Changes to enable PageVitals';
    }

    public function website_id_callback() {
        printf(
            '<input type="text" id="website_id" name="pagevitals_options[website_id]" value="%s" />',
            isset($this->options['website_id']) ? esc_attr($this->options['website_id']) : ''
        );
        echo '<p class="description">You can find your website ID by going to <a href="https://app.pagevitals.com/website/settings" target="_blank">Settings</a> in your PageVitals account. The blue box next to "Website Settings" is your website ID.</p><p class="description">Leave blank to disable.';
    }

    public function enable_csp_callback() {
        printf(
            '<input type="checkbox" name="pagevitals_options[enable_csp]" %s />',
            (isset($this->options['enable_csp']) && $this->options['enable_csp'] === 'on') ? 'checked' : ''
        );
        echo '<p class="description">Warning: Enabling this option will modify your site\'s Content-Security-Policy only if one already exists. It will add the necessary directives for PageVitals to function. If your site doesn\'t have a CSP, no changes will be made. Please ensure this doesn\'t conflict with your site\'s security requirements.</p>';
    }

    public function page_selection_callback() {
        $options = array('all' => 'All Pages', 'specific' => 'Specific Pages', 'except' => 'All Pages Except');
        $selected = isset($this->options['page_selection']) ? $this->options['page_selection'] : 'all';
        echo '<select id="page_selection" name="pagevitals_options[page_selection]">';
        foreach ($options as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($selected, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    public function selected_pages_callback() {
        printf(
            '<textarea id="selected_pages" name="pagevitals_options[selected_pages]" rows="5" cols="50">%s</textarea>',
            isset($this->options['selected_pages']) ? esc_textarea($this->options['selected_pages']) : ''
        );
        echo wp_kses_post('<p class="description">Enter page/post IDs or slugs, one per line.</p>');
    }

    public function enqueue_pagevitals_script() {
        $options = get_option('pagevitals_options');
        
        if (empty($options['website_id'])) {
            return;
        }
    
        if (!$this->should_insert_script()) {
            return;
        }
    
        // Use plugin version as script version
        $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
        $plugin_version = $plugin_data['Version'];
    
        wp_enqueue_script(
            'pagevitals-script',
            'https://cdn.pgvt.io/' . esc_js($options['website_id']) . '.js',
            array(),
            $plugin_version,
            true
        );
    
        // Add async and defer attributes to the script
        add_filter('script_loader_tag', function($tag, $handle) {
            if ('pagevitals-script' === $handle) {
                return str_replace(' src', ' async defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }

    private function should_insert_script() {
        $options = get_option('pagevitals_options');
        $page_selection = isset($options['page_selection']) ? $options['page_selection'] : 'all';
        $selected_pages = isset($options['selected_pages']) ? array_map('trim', explode("\n", $options['selected_pages'])) : array();

        if ($page_selection === 'all') {
            return true;
        }

        $current_id = get_the_ID();
        $current_slug = get_post_field('post_name', $current_id);

        if ($page_selection === 'specific') {
            return in_array($current_id, $selected_pages) || in_array($current_slug, $selected_pages);
        }

        if ($page_selection === 'except') {
            // Only return true if the current page is not in the exclude list
            return !in_array($current_id, $selected_pages) && !in_array($current_slug, $selected_pages);
        }

        return false;
    }

    public function add_csp_headers() {
        $options = get_option('pagevitals_options');
        
        if (isset($options['enable_csp']) && $options['enable_csp'] === 'on') {
            $csp_updates = array(
                'script-src' => 'cdn.pgvt.io',
                'connect-src' => 'in.pgvt.io'
            );

            // Check if CSP header already exists
            $headers = headers_list();
            $existing_csp = '';
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Security-Policy:') === 0) {
                    $existing_csp = substr($header, strlen('Content-Security-Policy:'));
                    break;
                }
            }

            if ($existing_csp) {
                // CSP exists, append our values
                $csp_parts = explode(';', $existing_csp);
                $csp_array = array();
                foreach ($csp_parts as $part) {
                    $part = trim($part);
                    if ($part) {
                        $directive_parts = explode(' ', $part, 2);
                        $directive = $directive_parts[0];
                        $csp_array[$directive] = isset($directive_parts[1]) ? $directive_parts[1] : '';
                    }
                }

                foreach ($csp_updates as $directive => $value) {
                    if (isset($csp_array[$directive])) {
                        if (strpos($csp_array[$directive], $value) === false) {
                            $csp_array[$directive] .= ' ' . $value;
                        }
                    } else {
                        $csp_array[$directive] = "'self' " . $value;
                    }
                }

                $new_csp = '';
                foreach ($csp_array as $directive => $value) {
                    $new_csp .= "$directive $value; ";
                }

                header("Content-Security-Policy: $new_csp", false);
            }
            // If no existing CSP, we don't add a new one
        }
    }
}

$pagevitals = new PageVitals();