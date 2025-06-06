<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
add_filter('the_content', 'pgfcShortcodesinject');
function pgfcShortcodesinject($content){
    $current_page_id     = get_the_ID();
    $login_page          = get_option('login_page', '');
    if ($login_page ==  $current_page_id ) {
        $content  .= do_shortcode("[edit_pipedrive_data]");
    }
    return $content;
}
function pgfc_register_custom_pgfc_post_type() {
    $labels = array(
        'name'               => __('pgfcs', PGFC_TEXT_DOMAIN),
        'singular_name'      => __('pgfc', PGFC_TEXT_DOMAIN),
        'menu_name'          => __('pgfcs', PGFC_TEXT_DOMAIN),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false, // Set to true if you want to show in the menu
        'query_var'          => true,
        'rewrite'            => array('slug' => 'pgfc' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'can_export' => true,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
    );
    register_post_type('pgfc', $args);
}
add_action('init', 'pgfc_register_custom_pgfc_post_type');

function getGravityForms($atr = false){
    $gravityForms = GFAPI::get_forms();   
    $formsNames = [];
    $formsFields = [];
    if(!empty($gravityForms)){
        foreach($gravityForms as $form){
            $formsNames[] = [
                'id'=>$form['id'], 
                'name'=>$form['title']
            ];            
            $formsFields[$form['id']] = $form['fields'];
        }
    }
    if($atr == 'forms'){
        return $formsNames;
    }
    if($atr == 'fields'){
        return $formsFields;
    }
    return $gravityForms;
}
function sanitize_array_recursive( $array ) {
    foreach ( $array as $key => $value ) {
        if ( is_array( $value ) ) {
            $array[ $key ] = sanitize_array_recursive( $value );
        } else {
            $array[ $key ] = sanitize_text_field( $value );
        }
    }
    return $array;
}
function groupByApiLabel($inputArray) {
    $grouped = [];

    foreach ($inputArray as $item) {
        $label = $item['apiLabel'];

        if (!isset($grouped[$label])) {
            $grouped[$label] = [];
        }

        $grouped[$label][] = $item;
    }

    return $grouped;
}
function pipeDriveApiToken(){
    return get_option('pipeDriveApiToken', '');
}
function getMapping($formID = false){
    $pgfcsArg = array(
        'post_type'      => 'pgfc',
        'posts_per_page' => -1, // Retrieve all posts
    );
    if($formID){
        $pgfcsArg['meta_query'] = [
            [
                'key'     => 'form_id',
                'value'   => $formID, 
                'compare' => '=',
            ]
        ];
    }
    $query = new WP_Query($pgfcsArg);
    $mappingResult = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $mapping = get_post_meta(get_the_ID(), 'mapping', true);
            $arrangedMapping = groupByApiLabel($mapping);
            if($formID){
                $mappingResult = $arrangedMapping;
            }else{
                $mappingResult[] = [
                    'form_id' => get_post_meta(get_the_ID(), 'form_id', true),
                    'mapping' => $arrangedMapping,
                ];
            }
        }
        wp_reset_postdata();
    }
    return $mappingResult;
}   

function insertApiErrorLog($action ,$api_end_point, $payload, $response){
    $timestamp = date('Y-m-d H:i:s');
    $title = $action . ' - '. $timestamp;
    $post_data = array(
        'post_title'    => sanitize_text_field($title),
        'post_status'   => 'publish',
        'post_type'     => 'pgfc_api_logs',
    );
    // Insert the post into the database
    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    if (isset($action) && !empty($action)) {
        update_post_meta($post_id, 'action', sanitize_text_field($action));
    }
    if (isset($api_end_point) && !empty($api_end_point)) {
        update_post_meta($post_id, 'api_end_point', sanitize_text_field($api_end_point));
    }
    if (isset($payload) && !empty($payload)) {
        update_post_meta($post_id, 'payload', $payload);
    }
    if (isset($response) && !empty($response)) {
        update_post_meta($post_id, 'response', $response);
    }
    update_post_meta($post_id, 'timestamp', sanitize_text_field($timestamp));
}

function alloedProfileData(){
    $arg =[
        'post_type' => 'pgfc', 
        'posts_per_page'=> -1
    ];
    $posts = get_posts($arg);
    $mergedArray = [];
    foreach($posts as $post){
        $ID = $post->ID;
        $formID = get_post_meta($ID, 'form_id', true);
        $subArray = getMapping($formID);
        foreach ($subArray as $key2 => $items) {
            $key = getPipeDriveAPIEndPoint($key2);
            if (!is_string($key)) {                
                continue; // Skip invalid keys
            }
            if (!isset($mergedArray[$key])) {
                $mergedArray[$key] = [];
            }
    
            foreach ($items as $newItem) {
                $exists = false;
    
                // Check if the same key already exists
                foreach ($mergedArray[$key] as &$existingItem) {
                    if ($existingItem['key'] === $newItem['apiAttribute']) {
                        $exists = true;
                        break;
                    }
                }
    
                // Add only if it does not already exist
                if (!$exists) {
                    $mergedArray[$key][] = [
                        'key' => $newItem['apiAttribute']
                    ];
                }
            }
        }
    }
    return $mergedArray;
}



// custom fields handler
function pipedriveGetCustomFields($entity) {
    $action = __('Syncing custom fields', PGFC_TEXT_DOMAIN);
    $response = pipedrive_api_request('GET', "{$entity}Fields", [], $action);
    if (!empty($response['success']) && !empty($response['data'])) {
        return $response['data'];
    }  
}

function pipedriveStoreCustomFields() {
    $entities = ['person', 'organization', 'deal', 'activity'];
    $fieldsData = [];

    foreach ($entities as $entity) {
        $fieldsData[$entity] = pipedriveGetCustomFields($entity);
    }
    update_option('pipedrive_custom_fields', $fieldsData);
    update_option('pipedrive_custom_fields_last_updated', current_time('mysql'));
}
// add_action('wp_head', function(){
//     echo '<pre>', print_r(pipedriveGetVieldName()['deal']), '</pre>';
// });
function pipedriveGetVieldName($fieldID = false, $endPoint = false) {
    $fieldsData = get_option('pipedrive_custom_fields');

    if (!$fieldsData) {
        // Fetch and store fields if not found in options
        pipedriveStoreCustomFields();
        $fieldsData = get_option('pipedrive_custom_fields');
    }
    if(!$fieldID || (!$endPoint && getPipeDriveAPIArray($endPoint)['singular_end_point'])){
        return $fieldsData;
    }
    
    $endPoint = getPipeDriveAPIArray($endPoint)['singular_end_point'];
    // Search in all entities
    if(isset($fieldsData[$endPoint])){
        foreach ($fieldsData[$endPoint] as $key => $field) {
            if ($field['key'] === $fieldID) {
                return $field;
            }
        }
    }
    
    pipedriveStoreCustomFields();
    $fieldsData = get_option('pipedrive_custom_fields');
    if(isset($fieldsData[$endPoint])){
        foreach ($fieldsData[$endPoint] as $key => $field) {
            if ($field['key'] === $fieldID) {
                return $field;
            }
        }
    }
    return __('No field found with this key', PGFC_TEXT_DOMAIN);
}

// custom fields handler

function is_user_profile_page() {
    if (is_admin()) {
        $screen = get_current_screen();
        return ($screen && $screen->id === 'profile' || $screen->id === 'user-edit');
    }
    return false;
}


function custom_login_form() {
    if (is_user_logged_in()) {
        echo '<p>' . __('You are already logged in.', PGFC_TEXT_DOMAIN) . '</p>';
        return;
    }

    // Capture login errors if any
    $error_message = '';
    if (isset($_GET['login_error'])) {
        $error_code = sanitize_text_field($_GET['login_error']);
        if ($error_code === 'empty') {
            $error_message = '<p class="login-error" style="color: red;">' . __('Please fill in both fields.', PGFC_TEXT_DOMAIN) . '</p>';
        } else {
            $error_message = '<p class="login-error" style="color: red;">' . __('Invalid username or password.', PGFC_TEXT_DOMAIN) . '</p>';
        }
    }

    // Handle the form submission
    if (isset($_POST['wp_custom_login'])) {
        $username = sanitize_text_field($_POST['log']);
        $password = sanitize_text_field($_POST['pwd']);
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url();

        // If any field is empty, show error
        if (empty($username) || empty($password)) {
            wp_redirect(add_query_arg('login_error', 'empty', $redirect_to));
            exit;
        }

        // Authenticate the user
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon($creds, false);

        // On successful login
        if (!is_wp_error($user)) {
            wp_redirect($redirect_to); // Redirect to the original page after login
            exit;
        } else {
            // If there's an error with login, pass error back to the form
            wp_redirect(add_query_arg('login_error', 'invalid', $redirect_to));
            exit;
        }
    }

    // Display the login form
    ob_start();
    ?>
    <div class="pgfcLoginForm">
        <form method="post" action="">
            <?php echo $error_message; ?>
            <p>
                <label for="user_login"><?php echo __('Username or Email', PGFC_TEXT_DOMAIN); ?></label>
                <input type="text" name="log" id="user_login" required>
            </p>
            <p>
                <label for="user_pass"><?php echo __('Password', PGFC_TEXT_DOMAIN); ?></label>
                <input type="password" name="pwd" id="user_pass" required>
            </p>
            <p>
                <a href="<?php echo wp_lostpassword_url(); ?>"><?php echo __('Forgot Password?', PGFC_TEXT_DOMAIN); ?></a>
            </p>
            <p>
                <input type="submit" name="wp_custom_login" value="<?php echo __('Log In', PGFC_TEXT_DOMAIN); ?>">
            </p>
            <?php
                $redirectURL = $_SERVER['REQUEST_URI'];
                if(isset($_GET['redirect_to'])){
                    $redirectURL = $_GET['redirect_to'];
                }
            ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirectURL); ?>">
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function redirect_subscriber_after_login( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'subscriber', $user->roles ) ) {
            if ( empty( $redirect_to ) || strpos( $redirect_to, admin_url() ) === 0 ) {

                $loginPage = get_option('login_page');
                return get_the_permalink($loginPage ); 
            }
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'redirect_subscriber_after_login', 10, 3 );


