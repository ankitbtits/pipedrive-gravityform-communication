<?php
/***
	Plugin Name: Pipedrive / Gravity Form Sync
	Description: The Gravity Form Pipedrive Sync Plugin provides an intuitive solution to manage communication between gravity forms and pipedrive APIs
	Requires at least: 5.2.0
	Tested up to: 6.7.1
	Author: Shambix
	Author URI: https://www.shambix.com/
	Version: 1.0.4
	Text Domain: pipedrive-gravityform-sync
    Domain Path: /languages
 ***/

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

if (!defined('PGFC_VERSION')) {
	define('PGFC_VERSION', '1.0.4');
}

if (!defined('PGFC_TEXT_DOMAIN')) {
    define('PGFC_TEXT_DOMAIN', plugin_basename(dirname(__FILE__)));
}
if (!defined('PGFC_MENU_SLUG')) {
    define('PGFC_MENU_SLUG', 'pipedrive-gravityform-sync');
}

define('PGFC_SLUG', 'pgf-communication');

require_once 'inc/hardCoded.php';
require_once 'inc/functions.php';
require_once 'inc/gravityForm.php';
require_once 'admin/tabs.php';
require_once 'admin/show_pipedrive_data.php';
require_once 'admin/manage_pipedrive_data.php';
require_once 'admin/manage_organizations_profile.php';
require_once 'inc/populate_fields.php';
require_once 'inc/pipeDriveUserCheck.php';

add_action('plugins_loaded', 'pgfc_load_textdomain');
function pgfc_load_textdomain() {
    load_plugin_textdomain('pipedrive-gravityform-sync', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('admin_notices', 'pgfc_admin_notice_notice');
function pgfc_admin_notice_notice(){
    if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) and current_user_can( 'activate_plugins' ) ) {
        ?>

        <div class="notice notice-error is-dismissible">
            <p><?php esc_attr_e( 'Gravity Form Pipedrive Sync plugin requires Gravity form plugin to be install.', PGFC_TEXT_DOMAIN ); ?></p>
        </div>
        <?php
    }
}

add_action('admin_enqueue_scripts', 'pgfc_pluginAdminScripts');
function pgfc_pluginAdminScripts() {    
    wp_enqueue_media(); 
    wp_enqueue_style(PGFC_SLUG.'_admin_style', plugin_dir_url(__FILE__).'admin/css/admin_style.css', array(), PGFC_VERSION);
    //wp_enqueue_script('jquery', false, array(), true, true); // Load jQuery in the footer
    wp_enqueue_script(PGFC_SLUG.'_admin_js', plugin_dir_url(__FILE__).'admin/js/admin_script.js?v='.time().'', array('jquery'),PGFC_VERSION, true); 
    wp_localize_script(PGFC_SLUG.'_admin_js', 'pgfc_ajax_admin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'addArrayKey'=>__('Add Array key' , PGFC_TEXT_DOMAIN ),
        'nonce' => wp_create_nonce('pgfc_generate_pdf_nonce'),
    ));
}

/**
 * Never worry about cache again!
 */
function scriptsFrontendBackend() {
	wp_enqueue_style(PGFC_SLUG.'_style', plugin_dir_url(__FILE__).'assets/css/style.css', array(), PGFC_VERSION);
    //wp_enqueue_script('jquery', false, array(), true, true); // Load jQuery in the footer
    wp_enqueue_script(PGFC_SLUG.'_js', plugin_dir_url(__FILE__).'assets/js/script.js', array('jquery'), PGFC_VERSION, true); 
    // wp_localize_script(PGFC_SLUG.'_js', 'dynamicConten', array(
    //     'ajaxurl' => admin_url('admin-ajax.php'),
    //     'loadingText' => __('Checking', PGFC_TEXT_DOMAIN),
    //     'searchingText'=> __('Searching', PGFC_TEXT_DOMAIN),
    //     'orgNotFound'=> __('A new organization with this name will be created.', PGFC_TEXT_DOMAIN),
    //     'nonce' => wp_create_nonce('pgfc_generate_pdf_nonce'),
    // ));

    wp_localize_script(PGFC_SLUG . '_js', 'dynamicConten', array_merge(
        staticText(),
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('pgfc_generate_pdf_nonce'),
        )
    ));

}
add_action('wp_enqueue_scripts', 'scriptsFrontendBackend');
add_action('admin_enqueue_scripts', 'scriptsFrontendBackend');

// Hook into plugin activation
register_activation_hook( __FILE__, 'pgfc_plugin_activation_hook' );

function pgfc_plugin_activation_hook() {
    update_option('pgfc_plugin_activated', true);
    update_option('pgfc_plugin_version', PGFC_VERSION);
}

if (!class_exists('pgfc_Communication')) {
    class pgfc_Communication {
        public function __construct() {
            add_action('admin_menu', array($this, 'pgfc_add_admin_menu'));
                     
        }
        public function pgfc_add_admin_menu() {
            // Add the top-level menu item
            add_menu_page(
                __('Gravity Form Pipedrive Sync', PGFC_TEXT_DOMAIN),  // Page title
                __('Pipedrive Sync', PGFC_TEXT_DOMAIN),  // Menu title
                'manage_options',  
                PGFC_MENU_SLUG,      
                array($this, 'pgfc_render_page'), 
                'dashicons-admin-generic', 
                20                 
            );        
        }

        public function pgfc_render_page(){
            if(isset($_GET['tab']) && $_GET['tab'] === 'settings'){
                require_once plugin_dir_path(__FILE__).'./admin/pgfc_settings.php';
            }elseif(isset($_GET['tab']) && $_GET['tab'] === 'api-logs'){
                require_once plugin_dir_path(__FILE__).'./admin/api_logs.php';
            }
            else{
                require_once plugin_dir_path(__FILE__).'./admin/all_pgfc_entries.php';
            }            
        }        
    }
    // Instantiate the class
    $pgfc_Communication = new pgfc_Communication();
}