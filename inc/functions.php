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