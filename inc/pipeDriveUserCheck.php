<?php
add_filter('gform_field_validation', 'pgfc_check_email_in_pipedrive_globally', 10, 4);
function pgfc_check_email_in_pipedrive_globally($result, $value, $form, $field) {
    $formID = $field->id;
    $mapping = getMapping($formID);
    $emailFieldID = null;
    echo $emailFieldID.'<pre style="width:48%; float:left; height 1000px; overflow:auto;">', print_r($mapping), '</pre>';
    foreach($mapping as $key => $val){
        $endPoint = getPipeDriveAPIEndPoint($key);
        if($endPoint == 'persons'){
            echo $endPoint .' = ';
            foreach($val as $key2 => $val2){
                if(isset($val2['apiAttribute']) && $val2['apiAttribute'] == 'email'){
                    $emailFieldID = $val2['field'];
                }
            }
        }
    }
    echo $emailFieldID. '=' .'<pre style="width:48%; float:left; height 1000px; overflow:auto;">', print_r($value), '</pre>';
    
    // Only run for email fields
    if ($field->type !== 'email') {
        return $result;
    }

    // Sanitize email
    $email = sanitize_email($value);
    if (empty($email)) {
        return $result;
    }

    // Call your helper function for Pipedrive
    $response = pipedrive_api_request('GET', 'persons/search', [
        'term' => $email,
        'fields' => 'email',
        'exact_match' => true
    ]);

    // If user already exists, make it invalid
    if (isset($response['data']['items']) && count($response['data']['items']) > 0) {
        $result['is_valid'] = false;
        $result['message'] = __('A user already exists with this email. Please login instead.', 'pgfc');
    }

    return $result;
}