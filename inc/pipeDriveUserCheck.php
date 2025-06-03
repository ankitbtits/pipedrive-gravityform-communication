<?php
// add_filter('gform_field_validation', 'pgfc_check_email_in_pipedrive_globally', 10, 4);
// function pgfc_check_email_in_pipedrive_globally($result, $value, $form, $field) {
//     if (is_user_logged_in()) {

//         return $result; // Return the default result (likely is_valid = true)

//     }
 
//     $formID = $form['id'];
//     $mapping = getMapping($formID);
//     $emailFieldID = null;
//     foreach($mapping as $key => $val){
//         $endPoint = getPipeDriveAPIEndPoint($key);
//         if($endPoint == 'persons'){
//             foreach($val as $key2 => $val2){
//                 if(isset($val2['apiAttribute']) && $val2['apiAttribute'] == 'email'){
//                     $emailFieldID = $val2['field'];
//                     $fieldEmailID = $val2['field'];
//                 }
//             }
//         }
//     }
//     $emailFieldID = 'input_'.$emailFieldID;
//     $emailID = '';
//     if(isset($_POST[$emailFieldID])){
//         $emailID =  $_POST[$emailFieldID];
//     }
//     $emailID = sanitize_email($emailID);
//     if( $emailID && $field->id == $fieldEmailID)
//     {
//         $action = 'Syncing custom fields';
//         $response = pipedrive_api_request('GET', "persons/search", [
//         'term' => $emailID,
//         'fields' => 'email', // Specify searching by email
//         "limit"=> 1,
//         'exact_match'=>1,
//         ], $action);
//         if (isset($response['data']['items']) && count($response['data']['items']) > 0) {
//             error_log('TRUE');
//             $result['is_valid'] = false;
//             $result['message'] = __('A user already exists with this email. Please login instead.', PGFC_TEXT_DOMAIN);
//             return $result;
//         }
//     }
//     return $result;
// }


add_action('wp_ajax_check_or_create_user_by_email', 'check_or_create_user_by_email');
add_action('wp_ajax_nopriv_check_or_create_user_by_email', 'check_or_create_user_by_email');
function check_or_create_user_by_email() {
    $emailID = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $emailExistPipeDrive  = false;
    if (is_user_logged_in()) {
         wp_send_json_success(['message' => '']); //Go for submit
         wp_die();
    }
    if (!is_user_logged_in()) {
        if (toCheckEmailExistsMore($emailID)) { // PipeDriveCheck
            $id = get_option('contact_support_page');
            $url = $id ? get_permalink($id) : '';
            $link = $url ? '<a href="' . esc_url($url) . '">' . esc_html__('contact support', PGFC_TEXT_DOMAIN) . '</a>' : esc_html__('contact support', PGFC_TEXT_DOMAIN);
            wp_send_json_error([
                'message' => sprintf(
                    __('We have detected more than one user with this email. Please %s to resolve this issue first.', PGFC_TEXT_DOMAIN),
                    $link
                )
            ]);
            wp_die();
        }
        if (email_exists($emailID)) {
            $id         = get_option('login_page');
            $current_url = '';
            if(isset($_POST['current_url']))
            {
                $current_url = '?redirect_to='.$_POST['current_url'];
            }
            $login_link = $id ? get_permalink($id).$current_url : '';
            $login_link = '<a href="' . esc_url($login_link) . '">' . esc_html__('log in', PGFC_TEXT_DOMAIN) . '</a>';
            wp_send_json_error([
                'message' => sprintf(
                    __('Please %s first.', PGFC_TEXT_DOMAIN),
                    $login_link
                )
            ]);
            wp_die();
        }
        wp_send_json_success(['message' => '']); //Go for submit
        wp_die();
    }
}
function toCheckEmailExistsMore($emailID){
    $emailExistPipeDriveMore = false;
    if( $emailID)
    {
        $action = __('Checking Email Exist PipeDrive More than one', PGFC_TEXT_DOMAIN);
        $response = pipedrive_api_request('GET', "persons/search", [
        'term' => $emailID,
        'fields' => 'email', // Specify searching by email
        "limit"=> 2,
        'exact_match'=>1,
        ], $action);
        if (isset($response['data']['items']) && count($response['data']['items']) == 2) {
           $emailExistPipeDriveMore = true;
        }
    }
    return $emailExistPipeDriveMore;
}

add_action('wp_ajax_search_organizations_by_name', 'search_organizations_by_name');
add_action('wp_ajax_nopriv_search_organizations_by_name', 'search_organizations_by_name');
function search_organizations_by_name() {
    if (empty($_POST['term'])) {
        wp_send_json_error(['message' => 'Missing search term']);
        wp_die();
    }

    $term = sanitize_text_field($_POST['term']);
    $action = 'Search Organizations';

    $response = pipedrive_api_request('GET', 'organizations/search', [
        'term' => $term,
        'fields' => 'name',
        'limit' => 5,
        'exact_match' => 0,
    ], $action);

    $results = [];

    if (!empty($response['data']['items'])) {
        foreach ($response['data']['items'] as $item) {
            if (isset($item['item']['id'], $item['item']['name'])) {
                $results[] = [
                    'id' => $item['item']['id'],
                    'name' => $item['item']['name'],
                ];
            }
        }
    }
    wp_send_json_success($results);
    wp_die();
}
