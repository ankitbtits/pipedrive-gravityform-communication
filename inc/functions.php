<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function pgfc_register_custom_pgfc_post_type() {
    $labels = array(
        'name'               => __('pgfcs', 'pgfc'),
        'singular_name'      => __('pgfc', 'pgfc'),
        'menu_name'          => __('pgfcs', 'pgfc'),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false, // Set to true if you want to show in the menu
        'query_var'          => true,
        'rewrite'            => array('slug' => 'pgfc'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
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
        foreach ($subArray as $key => $items) {
            $key = getPipeDriveAPIEndPoint($key);
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
    $response = pipedrive_api_request('GET', "{$entity}Fields");
    if (!empty($response['success']) && !empty($response['data'])) {
        return $response['data'];
    }  else{
        insertApiErrorLog('Getting custom fields API failed ',"{$entity}Fields", '', $response);   
    }
}

function pipedriveStoreCustomFields() {
    $entities = ['person', 'organization', 'deal', 'activity'];
    $fieldsData = [];

    foreach ($entities as $entity) {
        $fieldsData[$entity] = pipedriveGetCustomFields($entity);
    }
    update_option('pipedrive_custom_fields', $fieldsData);
}

function pipedriveGetVieldName($fieldID = false) {
    $fieldsData = get_option('pipedrive_custom_fields');

    if (!$fieldsData) {
        // Fetch and store fields if not found in options
        pipedriveStoreCustomFields();
        $fieldsData = get_option('pipedrive_custom_fields');
    }
    if(!$fieldID){
        return $fieldsData;
    }
    // Search in all entities
    foreach ($fieldsData as $entityFields) {
        foreach ($entityFields as $field) {
            if ($field['key'] === $fieldID) {
                return $field;
            }
        }
    }

    // If field is not found, refresh fields and try again
    pipedriveStoreCustomFields();
    $fieldsData = get_option('pipedrive_custom_fields');

    foreach ($fieldsData as $entityFields) {
        foreach ($entityFields as $field) {
            if ($field['key'] === $fieldID) {
                return $field;
            }
        }
    }

    return "No field found with this key";
}

// custom fields handler
