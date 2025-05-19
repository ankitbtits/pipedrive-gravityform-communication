<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
    require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
    require_once(ABSPATH . 'wp-admin/includes/template.php');
    require_once ABSPATH . 'wp-admin/includes/screen.php';
}
$pgfcsArg = array(
    'post_type'      => 'pgfc',
    'posts_per_page' => -1, // Retrieve all posts
);
if(isset($_GET['orderby']) && !empty(sanitize_text_field($_GET['orderby']))){
    $pgfcsArg['orderby'] = sanitize_text_field($_GET['orderby']);
}
$pgfcs = new WP_Query($pgfcsArg);
// $postMeta = get_post_meta(13,'mapping');
// echo '<pre>', print_r($postMeta), '</pre>';
if (!class_exists('pgfcs_List_Table')) {
    class pgfcs_List_Table extends WP_List_Table {
        function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();

            $this->_column_headers = array($columns, $hidden, $sortable);
            $this->items = $this->pgfc_get_pgfcs_data();
        }
        function pgfc_get_pgfcs_data() {
            $data = array();
            $pgfcsArg = array(
                'post_type'      => 'pgfc',
                'posts_per_page' => -1, // Retrieve all posts
            );
            if(isset($_GET['orderby']) && !empty(sanitize_text_field($_GET['orderby']))){
                $pgfcsArg['orderby'] = sanitize_text_field($_GET['orderby']);
            }
            $pgfcs = new WP_Query($pgfcsArg);

            //edit url
            $scheme = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https' : 'http';
            $host = sanitize_text_field( $_SERVER['HTTP_HOST'] );
            $request_uri = esc_url_raw( $_SERVER['REQUEST_URI'] );

            $current_url = "{$scheme}://{$host}{$request_uri}";

            // Parse the URL into components
            $url_components = wp_parse_url( $current_url );

            // Get the query part and parse it into an array
            $query = isset( $url_components['query'] ) ? $url_components['query'] : '';
            parse_str( $query, $query_array );
            //edit url

            foreach ($pgfcs->posts as $pgfc) {
                $query_array['pgfc_id'] = $pgfc->ID; // Add or modify a query parameter
                $new_query_string = http_build_query($query_array);
                $new_url = $url_components['scheme'] . '://' . $url_components['host'] . $url_components['path'] . '?' . $new_query_string;     


                $mapping = get_post_meta($pgfc->ID, 'mapping', true);
                $apiLabels = [];
                if(is_array($mapping) && !empty($mapping)){
                    foreach ($mapping as $item) {
                        if (!in_array($item['apiLabel'], $apiLabels, true)) {
                            $apiLabels[] = $item['apiLabel'];
                        }
                    }
                }
                $apiLabelsString = implode(', ', $apiLabels);

                $pgfc_createddate = get_post_meta($pgfc->ID, 'pgfc_createddate', true);
                $data[] = array(
                    'ID'               => $pgfc->ID,
                    'form_title'             => $pgfc->post_title,
                    'linked_api'             => $apiLabelsString,
                    'published_date'             => $pgfc_createddate,
                );
                if (is_admin() && current_user_can('manage_options')) {
                    $data[count($data) - 1]['actions'] = '<a href="' . esc_url($new_url) . '">' . __('Edit', PGFC_TEXT_DOMAIN) . '</a>';
                }
            }
            return $data;
        }
        function get_columns() {
            $cols = array();
            if (is_admin() && current_user_can('manage_options')) {
                $cols['cb'] = '<input type="checkbox" />';                
            }
            $cols = array_merge($cols, array(
                'form_title'              => esc_html__('Gravity Form', PGFC_TEXT_DOMAIN),
                'linked_api'     => esc_html__('Linked APIs', PGFC_TEXT_DOMAIN),   
                'published_date'              => esc_html__('Published Date', PGFC_TEXT_DOMAIN),             
            ));
            if (is_admin() && current_user_can('manage_options')) {
                $cols['actions'] = esc_html__('Action', PGFC_TEXT_DOMAIN);
            }
            return $cols;
        }
        function get_sortable_columns() {
            return array(
                'communication_id'             => array('user', false),
                'form_title'    => array('pgfc_title', true),
            );
        }
        function column_default($item, $column_name) {
            return $item[$column_name];
        }
        function column_cb($item) {
            return '<input type="checkbox" name="pgfc[]" value="' . esc_html($item['ID']) . '" />';
        }
        function get_bulk_actions() {
            $actions = '';
            if (is_admin() && current_user_can('manage_options')) {
                $actions = array(
                    'delete' => 'Delete',
                );
            }
            return $actions;
        }
        function process_bulk_action() {
            if (isset($_POST['_wpnonce_bulk_pgfc']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_bulk_pgfc'])), 'bulk-pgfc-nonce-action')) {
                if ('delete' === $this->current_action()) {
                    $pgfcs_to_delete = isset($_REQUEST['pgfc']) ? array_map( 'absint',$_REQUEST['pgfc']) : array();
                    foreach ($pgfcs_to_delete as $pgfcID) {                       
                        wp_delete_post( $pgfcID, true );
                    }
                    echo '<div class="updated"><p>' . esc_html__('pgfcs deleted successfully!', PGFC_TEXT_DOMAIN) . '</p></div>';
                }
            } elseif (isset($_POST['_wpnonce_bulk_pgfc'])) {
                echo '<div class="error"><p>' . esc_html__('Security check failed.', PGFC_TEXT_DOMAIN) . '</p></div>';
            }
        }

    }
}
// Usage: Create an instance of your custom list table and display it
function pgfc_display_pgfcs_list_table() {
    $pgfcs_list_table = new pgfcs_List_Table();
    $pgfcs_list_table->process_bulk_action();
    $pgfcs_list_table->prepare_items();
    $pgfcs_list_table->display();
}

?>
<div class="wrap alignwide">
    <?php 
        if (is_admin() && current_user_can('manage_options')) {
            $tabs = new PGFC_Admin_Tabs();
            echo $tabs->render();
    }   ?>
    <h1 class="wp-heading-inline"><?php esc_html_e('Gravity Form Pipedrive Sync', PGFC_TEXT_DOMAIN);?></h1>
    <?php 
        if(isset($_GET['pgfc_id']) && !empty($_GET['pgfc_id'])):
            require_once'edit_pgfc.php';
        else:        
    ?>   
        <?php if (is_admin() && current_user_can('manage_options')) { ?>
            <button onclick="pgfctoggleCustomFun('.pgfc_toggleNewpgfc')" class="page-title-action"><?php esc_html_e('Add New Mapping', PGFC_TEXT_DOMAIN);?></button>
            <div class="pgfc_toggleNewpgfc" style="display:none;">
                <?php 
                    require_once'add_pgfc.php'; 
                ?>
            </div>
        <?php } ?>    
    <?php endif;?>

    <form method="post">
        <?php      
            wp_nonce_field('bulk-pgfc-nonce-action', '_wpnonce_bulk_pgfc');
            pgfc_display_pgfcs_list_table();
        ?>
    </form>
</div>

