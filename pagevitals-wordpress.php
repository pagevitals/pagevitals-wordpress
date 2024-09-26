<?php
/**
 * Plugin Name: PageVitals RUM Integration
 * Plugin URI: https://www.pagevitals.com/
 * Description: Integrates PageVitals RUM script into your WordPress site.
 * Version: 1.0
 * Author: Lasse Schou
 * Author URI: https://pagevitals.com/
 * License: MIT
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class PageVitals_RUM {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_head', array($this, 'insert_pagevitals_script'));
        add_action('send_headers', array($this, 'add_csp_headers'));
    }

    public function add_plugin_page() {
        add_options_page(
            'PageVitals RUM Settings',
            'PageVitals RUM',
            'manage_options',
            'pagevitals-rum',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('pagevitals_rum_options');
        ?>
        <div class="wrap">
            <h1>PageVitals RUM Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('pagevitals_rum_option_group');
                do_settings_sections('pagevitals-rum-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'pagevitals_rum_option_group',
            'pagevitals_rum_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'pagevitals_rum_setting_section',
            'PageVitals RUM Settings',
            array($this, 'section_info'),
            'pagevitals-rum-admin'
        );

        add_settings_field(
            'website_id',
            'Website ID',
            array($this, 'website_id_callback'),
            'pagevitals-rum-admin',
            'pagevitals_rum_setting_section'
        );

        add_settings_field(
            'enable_csp',
            'Enable Content Security Policy',
            array($this, 'enable_csp_callback'),
            'pagevitals-rum-admin',
            'pagevitals_rum_setting_section'
        );

        add_settings_field(
            'page_selection',
            'Page Selection',
            array($this, 'page_selection_callback'),
            'pagevitals-rum-admin',
            'pagevitals_rum_setting_section'
        );

        add_settings_field(
            'selected_pages',
            'Selected Pages',
            array($this, 'selected_pages_callback'),
            'pagevitals-rum-admin',
            'pagevitals_rum_setting_section'
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
        echo 'Enter your settings below:';
    }

    public function website_id_callback() {
        printf(
            '<input type="text" id="website_id" name="pagevitals_rum_options[website_id]" value="%s" />',
            isset($this->options['website_id']) ? esc_attr($this->options['website_id']) : ''
        );
    }

    public function enable_csp_callback() {
        printf(
            '<input type="checkbox" name="pagevitals_rum_options[enable_csp]" %s />',
            (isset($this->options['enable_csp']) && $this->options['enable_csp'] === 'on') ? 'checked' : ''
        );
        echo '<p class="description">Warning: Enabling this option will modify your site\'s Content-Security-Policy only if one already exists. It will add the necessary directives for PageVitals to function. If your site doesn\'t have a CSP, no changes will be made. Please ensure this doesn\'t conflict with your site\'s security requirements.</p>';
    }

    public function page_selection_callback() {
        $options = array('all' => 'All Pages', 'specific' => 'Specific Pages', 'exclude' => 'All Pages Except');
        $selected = isset($this->options['page_selection']) ? $this->options['page_selection'] : 'all';
        echo '<select id="page_selection" name="pagevitals_rum_options[page_selection]">';
        foreach ($options as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                selected($selected, $value, false),
                $label
            );
        }
        echo '</select>';
    }

    public function selected_pages_callback() {
        printf(
            '<textarea id="selected_pages" name="pagevitals_rum_options[selected_pages]" rows="5" cols="50">%s</textarea>',
            isset($this->options['selected_pages']) ? esc_textarea($this->options['selected_pages']) : ''
        );
        echo '<p class="description">Enter page/post IDs or slugs, one per line.</p>';
    }

    public function insert_pagevitals_script() {
        $options = get_option('pagevitals_rum_options');
        
        if (empty($options['website_id'])) {
            return;
        }

        $should_insert = $this->should_insert_script();
        
        if (!$should_insert) {
            return;
        }

        $script = "(function(c,f,d,g,e){function h(b,l,m){b=b.createElement(l);b.async=b.defer=!0;b.src=m;k.appendChild(b)}var k=c.getElementsByTagName(d)[0].parentNode,a=c.createElement('link');f.webdriver||/lighthouse|headlesschrome|ptst/i.test(f.userAgent)||(a.relList&&'function'===typeof a.relList.supports&&a.relList.supports(g)&&'as'in a?(a.href=e,a.rel=g,a.as=d,a.addEventListener('load',function(){h(c,d,e)}),k.appendChild(a)):h(c,d,e))})(document,navigator,'script','preload','https://cdn.pgvt.io/{$options['website_id']}.js');";

        echo "<script>{$script}</script>\n";
    }

    private function should_insert_script() {
        $options = get_option('pagevitals_rum_options');
        $page_selection = isset($options['page_selection']) ? $options['page_selection'] : 'all';
        $selected_pages = isset($options['selected_pages']) ? explode("\n", $options['selected_pages']) : array();
        $selected_pages = array_map('trim', $selected_pages);

        if ($page_selection === 'all') {
            return true;
        }

        $current_id = get_the_ID();
        $current_slug = get_post_field('post_name', $current_id);

        if ($page_selection === 'specific') {
            return in_array($current_id, $selected_pages) || in_array($current_slug, $selected_pages);
        }

        if ($page_selection === 'exclude') {
            return !in_array($current_id, $selected_pages) && !in_array($current_slug, $selected_pages);
        }

        return false;
    }

    public function add_csp_headers() {
        $options = get_option('pagevitals_rum_options');
        
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

$pagevitals_rum = new PageVitals_RUM();