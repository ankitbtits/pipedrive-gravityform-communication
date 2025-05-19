<?php
add_filter('gform_field_validation', 'pgfc_check_email_in_pipedrive_globally', 10, 4);
function pgfc_check_email_in_pipedrive_globally($result, $value, $form, $field) {
    if (is_user_logged_in()) {

        return $result; // Return the default result (likely is_valid = true)

    }
 
    $formID = $form['id'];
    $mapping = getMapping($formID);
    $emailFieldID = null;
    foreach($mapping as $key => $val){
        $endPoint = getPipeDriveAPIEndPoint($key);
        if($endPoint == 'persons'){
            foreach($val as $key2 => $val2){
                if(isset($val2['apiAttribute']) && $val2['apiAttribute'] == 'email'){
                    $emailFieldID = $val2['field'];
                    $fieldEmailID = $val2['field'];
                }
            }
        }
    }
    $emailFieldID = 'input_'.$emailFieldID;
    $emailID = '';
    if(isset($_POST[$emailFieldID])){
        $emailID =  $_POST[$emailFieldID];
    }
    $emailID = sanitize_email($emailID);
    if( $emailID && $field->id == $fieldEmailID)
    {
        $action = 'Syncing custom fields';
        $response = pipedrive_api_request('GET', "persons/search", [
        'term' => $emailID,
        'fields' => 'email', // Specify searching by email
        "limit"=> 1,
        'exact_match'=>1,
        ], $action);
        if (isset($response['data']['items']) && count($response['data']['items']) > 0) {
            $result['is_valid'] = false;
            $result['message'] = __('A user already exists with this email. Please login instead.', PGFC_TEXT_DOMAIN);
            return $result;
        }
    }
    return $result;
}