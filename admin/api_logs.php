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
if (!class_exists('pgfcs_Error_Log_Table')) {
    class pgfcs_Error_Log_Table extends WP_List_Table {
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
                'post_type'      => 'pgfc_api_logs',
                'posts_per_page' => -1, // Retrieve all posts
            );
            if(isset($_GET['orderby']) && !empty(sanitize_text_field($_GET['orderby']))){
                $pgfcsArg['orderby'] = sanitize_text_field($_GET['orderby']);
            }
            $pgfcs = new WP_Query($pgfcsArg);

            // Get the query part and parse it into an array
            $query = isset( $url_components['query'] ) ? $url_components['query'] : '';
            parse_str( $query, $query_array );
            //edit url

            foreach ($pgfcs->posts as $pgfc) {
                $postID = $pgfc->ID; // Add or modify a query parameter            

                $action = get_post_meta($postID, 'action', true);
                $api_end_point = get_post_meta($postID, 'api_end_point', true);
                $payload = get_post_meta($postID, 'payload', true);
                $response = get_post_meta($postID, 'response', true);
                $timestamp = get_post_meta($postID, 'timestamp', true);
                $data[] = array(
                    'ID'               => $postID,
                    'action'               => $action,
                    'api_end_point'             => $api_end_point,
                    'payload'             => is_array($payload) ? wp_json_encode($payload, JSON_PRETTY_PRINT) : $payload,
                    'response'             => is_array($response) ? wp_json_encode($response, JSON_PRETTY_PRINT) : $response,
                    'timestamp'             => $timestamp,
                );
            }
            return $data;
        }
        function get_columns() {
            $cols = array();
            if (is_admin() && current_user_can('manage_options')) {
                $cols['cb'] = '<input type="checkbox" />';                
            }
            $cols = array_merge($cols, array(
                'action'              => esc_html__('Action', PGFC_TEXT_DOMAIN),
                'api_end_point'     => esc_html__('API end point', PGFC_TEXT_DOMAIN),   
                'payload'              => esc_html__('Payload', PGFC_TEXT_DOMAIN),             
                'response'              => esc_html__('Response', PGFC_TEXT_DOMAIN), 
                'timestamp'              => esc_html__('Time', PGFC_TEXT_DOMAIN),            
            ));
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
function pgfc_display_pgfcs_Error_Log_Table() {
    $pgfcs_Error_Log_Table = new pgfcs_Error_Log_Table();
    $pgfcs_Error_Log_Table->process_bulk_action();
    if (isset($_POST['pgfc_export_all']) && check_admin_referer('bulk-pgfc-nonce-action', '_wpnonce_bulk_pgfc')) {
        pgfc_export_all_logs();
    }
    
    if (isset($_POST['pgfc_delete_all']) && check_admin_referer('bulk-pgfc-nonce-action', '_wpnonce_bulk_pgfc')) {
        pgfc_delete_all_logs();
    }
    $pgfcs_Error_Log_Table->prepare_items();
    $pgfcs_Error_Log_Table->display();
}

?>
<div class="wrap alignwide">
    <?php 
        if (is_admin() && current_user_can('manage_options')) {
            $tabs = new PGFC_Admin_Tabs();
            echo $tabs->render();
    }   ?>
    <h1 class="wp-heading-inline"><?php esc_html_e('Gravity Form Pipedrive Sync', PGFC_TEXT_DOMAIN);?></h1>   

    <form method="post">
        <div class="rightButton" style="float:right;">
            <button type="submit" name="pgfc_export_all" class="button button-primary">
                <?php esc_html_e('Export All Logs', PGFC_TEXT_DOMAIN); ?>
            </button>
            <button type="submit" name="pgfc_delete_all" class="button button-danger" onclick="return confirm('Are you sure you want to delete all logs? This action cannot be undone.');">
                <?php esc_html_e('Delete All Logs', PGFC_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php      
            wp_nonce_field('bulk-pgfc-nonce-action', '_wpnonce_bulk_pgfc');
            pgfc_display_pgfcs_Error_Log_Table();
        ?>
    </form>
</div>
<?php
function pgfc_export_all_logs() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $pgfcsArg = array(
        'post_type'      => 'pgfc_api_logs',
        'posts_per_page' => -1,
    );
    $pgfcs = new WP_Query($pgfcsArg);
    
    if ($pgfcs->have_posts()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=pgfc_logs.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Action', 'API end point', 'Payload', 'Response', 'Time'));
        
        foreach ($pgfcs->posts as $pgfc) {
            $postID = $pgfc->ID;
            $action = get_post_meta($postID, 'action', true);
            $api_end_point = get_post_meta($postID, 'api_end_point', true);
            $payload = get_post_meta($postID, 'payload', true);
            $response = get_post_meta($postID, 'response', true);
            $timestamp = get_post_meta($postID, 'timestamp', true);

            fputcsv($output, array(
                $postID,
                $action,
                $api_end_point,
                is_array($payload) ? wp_json_encode($payload, JSON_PRETTY_PRINT) : $payload,
                is_array($response) ? wp_json_encode($response, JSON_PRETTY_PRINT) : $response,
                $timestamp,
            ));
        }
        
        fclose($output);
        exit();
    }
}

function pgfc_delete_all_logs() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $pgfcs = get_posts(array(
        'post_type'      => 'pgfc_api_logs',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));

    foreach ($pgfcs as $pgfcID) {
        wp_delete_post($pgfcID, true);
    }

    echo '<div class="updated"><p>' . esc_html__('All logs deleted successfully!', PGFC_TEXT_DOMAIN) . '</p></div>';
}


?>
