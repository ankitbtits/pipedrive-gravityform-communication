<?php



add_action('gform_after_submission', 'handle_pipedrive_integration', 10, 2);
function handle_pipedrive_integration($entries, $form) {
    $payloads = getPayLoads($entries);       
    $personId = null;
    $orgId = null;
    $dealID = null;
    $user_id = null;
    $create_account = false;
    foreach ($entries as $field_value) {
        if ($field_value === 'createAccountWP') {
            $create_account = true;
            break;
        }
    }
    // Create WordPress account only if "createAccountWP" is found
    if ($create_account && !is_user_logged_in()) {
        $email = $payloads['persons']['email'] ?? null;
        $user_id = createAccount($email);
    }
    
    // Check if user is logged in
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $personId = get_user_meta($user_id, 'pipedrive_person_id', true);
    }

    // 1. Create Organization and link with person
    if (isset($payloads['organizations']) && !empty($payloads['organizations'])) {
        $org = pipedrive_api_request('POST','organizations', $payloads['organizations']);     
        if (!$org || empty($org['data']['id'])) {
            insertApiErrorLog('Add organization through form - '.$entries['form_id'] ,'organizations', $payloads['organizations'], $org);   
            log_api_error('organizations', $payloads['organizations'], $org);
        } else {
            $orgId = $org['data']['id'];
        }
    }

    // 1. Create Person only if it doesn't exist
    if (!$personId) {
        if (!empty($orgId)) {
            $payloads['persons']['org_id'] = $orgId;
        }
        $person = pipedrive_api_request('POST','persons', $payloads['persons']);
        if (!$person || empty($person['data']['id'])) {
            insertApiErrorLog('Add Person through form - '.$entries['form_id'] ,'persons', $payloads['persons'], $person);
            log_api_error('persons', $payloads['persons'], $person);
            return; // Stop execution if person creation fails
        }
        $personId = $person['data']['id'];

        // Save Person ID in user meta
        if ($user_id) {
            update_user_meta($user_id, 'pipedrive_person_id', $personId);
        }
    }else{
        if (empty($orgId)) {
            return;
        }
        $updatePerson = pipedrive_api_request('PUT','persons/'.$personId, ["org_id"=> $orgId]);
        if (!$updatePerson || empty($updatePerson['data']['id'])) {
            insertApiErrorLog('Add Person through form - '.$entries['form_id'] ,'persons', ["org_id"=> $orgId], $updatePerson);
            log_api_error('persons', ["org_id"=> $orgId], $updatePerson);
            return; // Stop execution if person creation fails
        }
    }

    // 3. Create Deal
    if (isset($payloads['deals']) && !empty($payloads['deals'])) {
        $payloads['deals']['person_id'] = $personId;
        if (!empty($orgId)) {
            $payloads['deals']['org_id'] = $orgId;
        }
        $deal = pipedrive_api_request('POST','deals', $payloads['deals']);
        
        if (!$deal || empty($deal['data']['id'])) {
            insertApiErrorLog('Add deals through form - '.$entries['form_id'] ,'deals', $payloads['deals'], $deal);
            log_api_error('deals', $payloads['deals'], $deal);
        } else {
            $dealID = $deal['data']['id'];
        }
    }

    // 4. Create Activity
    if (isset($payloads['activities']) && !empty($payloads['activities'])) {
        
        foreach($payloads['activities'] as $activity){
            $activity['person_id'] = $personId;
            if ($dealID) {
                $activity['deal_id'] = $dealID;
            }
            if ($dealID) {
                $activity['org_id'] = $orgId;
            }
            $activityResponse = pipedrive_api_request('POST','activities', $activity);
            if (!$activityResponse || empty($activityResponse['data']['id'])) {
                insertApiErrorLog('Add activities through form - '.$entries['form_id'] ,'activities', $activity, $activityResponse);
                log_api_error('activities', $activity, $activityResponse);
            }
        }
    }
}
function pipedrive_api_request($method, $endpoint, $data = []) {
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
                    $apiKey = $val2['apiAttribute'];
                    if(isset($val2['apiLabelIndex'])){
                        $theIndex = $val2['apiLabelIndex'];
                        if (!isset($payLoads[$endPoint][$theIndex][$apiKey]) && isset($entries[$fieldID])) {
                            $payLoads[$endPoint][$theIndex][$apiKey] = $entries[$fieldID];
                        } elseif(isset($entries[$fieldID])) {
                            $payLoads[$endPoint][$theIndex][$apiKey] .= ' - ' . $entries[$fieldID];
                        }  
                    }else{
                        if (!isset($payLoads[$endPoint][$apiKey]) && isset($entries[$fieldID])) {
                            $payLoads[$endPoint][$apiKey] = $entries[$fieldID];
                        } elseif(isset($entries[$fieldID])) {
                            $payLoads[$endPoint][$apiKey] .= ' - ' . $entries[$fieldID];
                        }  
                    }
                                    
                }
            }
        }
    }
    return $payLoads;
}


function log_api_error($api_name, $payload, $response) {
    error_log('log_api_error init');
    $log_file = __DIR__ . '/errors.log';
    $timestamp = date('Y-m-d H:i:s');

    $log_data = "[$timestamp] API Error: $api_name\n";
    $log_data .= "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    $log_data .= "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    $log_data .= "-------------------------------------------------\n";

    file_put_contents($log_file, $log_data, FILE_APPEND);
}


function createAccount($email){
    if (!$email) {
        // Log error if email is missing
        log_api_error('missing_email', ['error' => 'Email is missing in the form'], null);
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
        log_api_error('wordpress_user', ['username' => $username, 'email' => $email], $user_id->get_error_message());
        return;
    }
    // Generate password reset key
    $reset_key = get_password_reset_key(get_user_by('ID', $user_id));
    if (!is_wp_error($reset_key)) {
        $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($username));

        // Email subject and message
        $subject = 'Set Your Password';
        $message = "Hello $username,\n\n";
        $message .= "Your account has been created. Please use the link below to set your password:\n\n";
        $message .= "$reset_link\n\n";
        $message .= "Thank you!";

        // Send email
        wp_mail($email, $subject, $message);
    }
    return $user_id;
}

add_action('wp_head', 'forTesting');
function forTesting(){
    // $fieldsData = pipedriveGetVieldName();
    // echo '<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($fieldsData) ,'</pre></div>';
    if(isset($_GET['pipe_drive_id'])){
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'pipedrive_person_id', $_GET['pipe_drive_id']);
    }
    if(isset($_GET['debug'])){
        $entries = getSampleData2();
        $payloads = getPayLoads($entries);
        $mapping = getMapping(3);
        echo '<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($entries) ,'</pre></div>';
        echo '2222222<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($payloads) ,'</pre></div>';
        echo '3333<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($mapping) ,'</pre></div>';
        die;
    }
}

