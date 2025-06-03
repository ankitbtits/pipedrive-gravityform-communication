<?php

add_action('gform_after_submission', 'handle_pipedrive_integration', 10, 2);
function handle_pipedrive_integration($entries, $form) {
    $formTitle = $form['title'];
    $formID = $form['id'];
    $action = 'Through Form: '.$formTitle.'('.$formID.')';
    $payloads = getPayLoads($entries); 
    $personId = null;
    $orgId = null;
    $dealID = null;
    $user_id = null;
    $create_account = false;
    $userEmail = null;
    if(isset($payloads['persons']['email'])){
        $userEmail = $payloads['persons']['email'];
    }
    if (!is_user_logged_in()) {
         $emailID   = $payloads['persons']['email'];
         $pipePerson =  toemailExistPipeDrive($emailID);
         if (!email_exists($emailID) && $pipePerson) {
            $create_account = true;
            $personId = $pipePerson;
         }
    }
    foreach($form['fields'] as $field){
        $fieldType = $field->type;
        if(isset($field->cssClass) && !empty($field->cssClass)){
            $adminClass = $field->cssClass;
            if($adminClass == 'userEmail' && $fieldType == 'email'){
                $fieldID = $field->id;
                $userEmail = $entries[$fieldID];
                break;
            }
        }
    }
    foreach ($entries as $field_value) {
        if ($field_value === 'createAccountWP') {
            $create_account = true;
            break;
        }
    }
    // Create WordPress account only if "createAccountWP" is found
    if ( ( $create_account && !is_user_logged_in() && $userEmail ) ||  $flagCreateAccont ) {
        $user_id = createAccount($userEmail);
    }
    // Check if user is logged in
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $personId = get_user_meta($user_id, 'pipedrive_person_id', true);
    }
    if($personId){
        $personData = pipedrive_api_request('GET', 'persons/'.$personId, [], $action);
        if(isset($personData['data']['org_id']['value'])){
            $orgId = $personData['data']['org_id']['value'];
        }
    }
    // 1. Create Organization and link with person
    if (!$orgId && isset($payloads['organizations']) && !empty($payloads['organizations'])) {
        $searchRes = [];
        if (isset($payloads['organizations']['name']) && !empty($payloads['organizations']['name'])) {
            $orgName = $payloads['organizations']['name']; // Organization name
            $searchRes = pipedrive_api_request('GET', 'organizations/search', [
                'term'   => $orgName,
                'fields' => 'name',
                'exact_match'=>1,
                'limit'  => 1,
            ], $action);            
        }
        if (isset($searchRes['data']['items']) && isset($searchRes['data']['items'][0]) && !empty($searchRes['data']['items'][0]['item']['id'])) 
        {
            $orgId = $searchRes['data']['items'][0]['item']['id'];
        }else{
            $org = pipedrive_api_request('POST','organizations', $payloads['organizations'], $action);     
            if (isset($org['data']['id']) && !empty($org['data']['id'])) {
                $orgId = $org['data']['id'];
            }
        }
    }
    // 1. Create Person only if it doesn't exist
    if (!$personId && isset($payloads['persons']) && !empty($payloads['persons'])) {
        if (!empty($orgId)) {
            $payloads['persons']['org_id'] = $orgId;
        }
        $person = pipedrive_api_request('POST','persons', $payloads['persons'], $action);
        if (!$person || empty($person['data']['id'])) {
            return; // Stop execution if person creation fails
        }
        $personId = $person['data']['id'];
        // Save Person ID in user meta        
    }else{
        if (empty($orgId)) {
            return;
        }
        $updatePerson = pipedrive_api_request('PUT','persons/'.$personId, ["org_id"=> $orgId], $action);
        if (!$updatePerson || empty($updatePerson['data']['id'])) {
            return; // Stop execution if person creation fails
        }
    }
    if ($user_id && $personId) {
        update_user_meta($user_id, 'pipedrive_person_id', $personId);
    }
    // 3. Create Deal
    if (isset($payloads['deals']) && !empty($payloads['deals'])) {
        $payloads['deals']['person_id'] = $personId;
        if (!empty($orgId)) {
            $payloads['deals']['org_id'] = $orgId;
        }
        $deal = pipedrive_api_request('POST','deals', $payloads['deals'], $action);        
        if (isset($deal['data']['id']) && !empty($deal['data']['id'])) {
            $dealID = $deal['data']['id'];
        }
    }
    // 4. Create Activity
    if (isset($payloads['activities']) && !empty($payloads['activities'])) {        
        foreach($payloads['activities'] as $activity){
            if(!is_array($activity)){ return;}
            $activity['person_id'] = $personId;
            if ($dealID) {
                $activity['deal_id'] = $dealID;
            }
            if ($dealID) {
                $activity['org_id'] = $orgId;
            }
            $activityResponse = pipedrive_api_request('POST','activities', $activity, $action);
        }
    }
}
function pipedrive_api_request($method, $endpoint, $data = [], $action = false) {
    $pipeDriveApiToken = pipeDriveApiToken();
    $domain = 'https://api.pipedrive.com';
    $url = "$domain/v1/$endpoint?api_token=$pipeDriveApiToken";
    // Initialize cURL session
    $ch = curl_init();
    // Set HTTP method
    switch (strtoupper($method)) {
        case "POST":
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case "PUT":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case "DELETE":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        case "GET":
            if (!empty($data)) {
                $url .= "&" . http_build_query($data); // Append query parameters for GET
            }
            break;
        default:
            return false; // Invalid method
    }
    // Set common cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // Decode response
    $decodedResponse = json_decode($response, true);
    if (!isset($decodedResponse['success']) || $decodedResponse['success'] === false) {
        insertApiErrorLog($action, $endpoint, $data, $decodedResponse);
    }
    return $decodedResponse;
}
function getPayLoads($entries){
    $payLoads = [];
    if(isset($entries['form_id'])){
        $formId = $entries['form_id'];
        $mapping = getMapping($formId);
        foreach($mapping as $key => $val){
            $endPoint = getPipeDriveAPIEndPoint($key);
            if(is_array($val) && !empty($val)){
                foreach($val as $key2 => $val2){
                    $fieldID = $val2['field'];
                    $fieldIDFloor = floor($fieldID);
                    $apiKey = $val2['apiAttribute'];
                    $fieldType = pipedriveGetVieldName($apiKey, $endPoint)['field_type'];                    
                    $entryVal = 'X';                    
                    if(array_key_exists($fieldID, $entries)){                        
                        $entryVal = $entries[$fieldID];
                    }elseif(array_key_exists($fieldIDFloor, $entries)){
                        $entryVal = $entries[$fieldIDFloor];                       
                    }else{
                        if($fieldType == 'set' || $fieldType == 'enum'){
                            $entryVal = extractAndJoinDecimalValues($entries, $fieldID, '||');  
                        }else{
                            $entryVal = extractAndJoinDecimalValues($entries, $fieldID); 
                        }
                    }
                    if(empty($entryVal)){
                        continue;
                    }                   
                    if(isset($val2['apiLabelIndex'])){
                        $theIndex = $val2['apiLabelIndex'];
                        if (!isset($payLoads[$endPoint][$theIndex][$apiKey])) {
                            $payLoads[$endPoint][$theIndex][$apiKey] = $entryVal;
                        } elseif(isset($entryVal)) {
                            if($fieldType == 'daterange'){
                                $payLoads[$endPoint][$theIndex][$apiKey.'_until'] = $entryVal;
                            }else{
                                $payLoads[$endPoint][$theIndex][$apiKey] .= ' ' . $entryVal;
                            }
                        }  
                    }else{
                        if (!isset($payLoads[$endPoint][$apiKey])) {
                            if($fieldType == 'set' || $fieldType == 'enum'){
                                $pipedriveGetData   =  pipedriveGetVieldName($apiKey, $endPoint); //For check Pipeline return value
                                $options            =  $pipedriveGetData['options']; // array of all available options
                                $matches = [];
                                $arrayExplod = explode('||', $entryVal);
                                $payLoads[$endPoint][$apiKey] = $entryVal;
                                foreach ($options as $option) {
                                    if ( (isset($option['id']) && in_array($option['id'], $arrayExplod)) ||  ( isset($option['label']) && in_array($option['label'], $arrayExplod) )  ) { 
                                            $matches[] = $option['id'];                                       
                                    }
                                }
                                $matchesVals =  implode(', ', $matches);
                                $payLoads[$endPoint][$apiKey] = $matchesVals;
                            }else{
                                $payLoads[$endPoint][$apiKey] = $entryVal;
                            }     
                        } elseif(isset($entryVal) && $fieldType != 'set' && $fieldType != 'enum') {
                            if($fieldType == 'daterange'){
                                $payLoads[$endPoint][$apiKey.'_until'] = $entryVal;
                            }elseif($fieldType == 'set' || $fieldType == 'enum'){
                                $payLoads[$endPoint][$apiKey] .= '====';
                                $pipedriveGetData   =  pipedriveGetVieldName($apiKey, $endPoint); //For check Pipeline return value
                                $options            =  $pipedriveGetData['options']; // array of all available options
                                foreach ($options as $option) {
                                    if ( isset($option['id']) && ( $option['id'] == $entryVal ||  strtolower($option['label']) == strtolower($entryVal) )  ) {
                                        $payLoads[$endPoint][$apiKey] .= ', ' . $option['id'];
                                        break;
                                    }
                                }
                            }else{
                                $payLoads[$endPoint][$apiKey] .= ' ' . $entryVal ;
                            }
                        }  
                    }
                                    
                }
            }
        }
    }
    return $payLoads;
}

function createAccount($email){
    if (!$email) {
        return; // Stop execution if email is missing
    }
    // Use the email address as the username
    $username = sanitize_user(explode('@', $email)[0]); // Extract username part from email
    $email = sanitize_email($email);

    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => wp_generate_password(), // Generate a random password
        'role' => 'subscriber', // Set the role as needed
    ]);
    if (is_wp_error($user_id)) {
        return;
    }
    // Generate password reset key
    $reset_key = get_password_reset_key(get_user_by('ID', $user_id));
    if (!is_wp_error($reset_key)) {
        $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($username));
        // Email subject and message
        $subject = __('Set Your Password', PGFC_TEXT_DOMAIN);
        $message = sprintf(
        __("Hello %s,\n\n%s\n\n%s\n\n%s", PGFC_TEXT_DOMAIN),
        $username,
        __("Your account has been created. Please use the link below to set your password:", PGFC_TEXT_DOMAIN),
        $reset_link,
        __("Thank you!", PGFC_TEXT_DOMAIN)
        );
        // Send email
        wp_mail($email, $subject, $message);
    }
    return $user_id;
}
function extractAndJoinDecimalValues(&$array, $baseKey, $separator = ' ') {
    $matches = [];
    // Step 1: Find all keys that start with baseKey + decimal (e.g., "1.3", "1.6")
    foreach ($array as $key => $value) {
        if (preg_match('/^' . preg_quote($baseKey, '/') . '\.\d+$/', $key)) {
            $matches[$key] = $value;
        }
    }
    if (!empty($matches)) {
        // Step 2: Sort keys numerically by their float value (1.3 < 1.6)
        uksort($matches, function ($a, $b) {
            return floatval($a) <=> floatval($b);
        });
        // Step 3: Collect only non-empty values
        $values = array_filter($matches, function($val) {
            return $val !== null && $val !== '';
        });
        // Step 4: Remove those keys from original array
        foreach (array_keys($matches) as $key) {
            unset($array[$key]);
        }
        // Step 5: Concatenate the values and return
        return implode($separator, $values);
    }
    // Fallback: If no decimals found, return the plain key if it exists
    if (array_key_exists($baseKey, $array)) {
        $val = $array[$baseKey];
        unset($array[$baseKey]);
        return $val;
    }
    return null; // Nothing found
}
add_action('wp_head', 'updatePersonManually');
function updatePersonManually(){
    if(isset($_GET['pipedrive_person']) && is_user_logged_in()){
        update_user_meta(get_current_user_id(), 'pipedrive_person_id', $_GET['pipedrive_person']);
    }
}
function toemailExistPipeDrive($emailID){
    $emailExistPipeDrive = false;
    if( $emailID)
    {
        $action = 'Checking email Id pipedrive';
        $response = pipedrive_api_request('GET', "persons/search", [
        'term' => $emailID,
        'fields' => 'email', // Specify searching by email
        "limit"=> 1,
        'exact_match'=>1,
        ], $action);
        if (isset($response['data']['items']) && count($response['data']['items']) > 0) {
           return $response['data']['items'][0]['item']['id']; //personID
        }
    }
    return $emailExistPipeDrive;
}