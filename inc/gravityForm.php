<?php
add_action('gform_after_submission', 'handle_pipedrive_integration', 10, 2);
function handle_pipedrive_integration($entry, $form) {
    $payloads = getPayLoads($entry);
    
    $personId = null;
    $orgId = null;
    $dealID = null;

    // Check if user is logged in
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $personId = get_user_meta($user_id, 'pipedrive_person_id', true);
    }

    // 1. Create Person only if it doesn't exist
    if (!$personId) {
        $person = pipedrive_api_post('persons', $payloads['persons'] ?? []);
        if (!$person || empty($person['data']['id'])) {
            log_api_error('persons', $payloads['persons'], $person);
            insertApiErrorLog('Add Person through form - '.$entries['form_id'] ,'persons', $payloads['persons'], $person);
            return; // Stop execution if person creation fails
        }
        $personId = $person['data']['id'];

        // Save Person ID in user meta
        if (is_user_logged_in()) {
            update_user_meta($user_id, 'pipedrive_person_id', $personId);
        }
    }

    // 2. Create Organization and link with person
    if ($personId && isset($payloads['organizations']) && !empty($payloads['organizations'])) {
        $payloads['organizations']['owner_id'] = $personId;
        $org = pipedrive_api_post('organizations', $payloads['organizations']);
        
        if (!$org || empty($org['data']['id'])) {
            log_api_error('organizations', $payloads['organizations'], $org);
            insertApiErrorLog('Add organization through form - '.$entries['form_id'] ,'organizations', $payloads['organizations'], $org);
        } else {
            $orgId = $org['data']['id'];
        }
    }

    // 3. Create Deal
    if (isset($payloads['deals']) && !empty($payloads['deals'])) {
        $payloads['deals']['person_id'] = $personId;
        if (!empty($orgId)) {
            $payloads['deals']['org_id'] = $orgId;
        }
        $deal = pipedrive_api_post('deals', $payloads['deals']);
        
        if (!$deal || empty($deal['data']['id'])) {
            log_api_error('deals', $payloads['deals'], $deal);
            insertApiErrorLog('Add deals through form - '.$entries['form_id'] ,'deals', $payloads['deals'], $deal);
        } else {
            $dealID = $deal['data']['id'];
        }
    }

    // 4. Create Activity
    if (isset($payloads['activities']) && !empty($payloads['activities'])) {
        $payloads['activities']['person_id'] = $personId;
        if ($dealID) {
            $payloads['activities']['deal_id'] = $dealID;
        }
        $activity = pipedrive_api_post('activities', $payloads['activities']);

        if (!$activity || empty($activity['data']['id'])) {
            log_api_error('activities', $payloads['activities'], $activity);
            insertApiErrorLog('Add activities through form - '.$entries['form_id'] ,'activities', $payloads['activities'], $activity);
        }
    }
}
function pipedrive_api_post($endpoint, $data) {
    $pipeDriveApiToken = pipeDriveApiToken(); //https://api.pipedrive.com/v1/persons?api_token
    $domain = 'https://api.pipedrive.com';
    $url = "$domain/v1/$endpoint?api_token=$pipeDriveApiToken";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

//add_action('wp_head', 'forTesting');
function forTesting(){
    $entries = getSampleData();
    $payloads = getPayLoads($entries);
    echo '<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($entries) ,'</pre></div>';
    echo '<div style="font-size:12px; width:50%; float:left;     overflow: hidden;"><pre>',print_r($payloads) ,'</pre></div>';
    die;
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
                    if (!isset($payLoads[$key][$apiKey])) {
                        $payLoads[$endPoint][$apiKey] = $entries[$fieldID];
                    } else {
                        $payLoads[$endPoint][$apiKey] .= ' - ' . $entries[$fieldID];
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