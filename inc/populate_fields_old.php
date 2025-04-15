<?php
add_filter('gform_pre_render', 'prefill_and_disable_fields_globally');
add_filter('gform_pre_validation', 'prefill_and_disable_fields_globally');

function prefill_and_disable_fields_globally($form) {
    if (!is_user_logged_in()) {
        return $form; // Do nothing if not logged in
    }
    $userID = get_current_user_id();
    $formID = $form['id'];
    $personID = get_user_meta($userID, 'pipedrive_person_id', true);
    if(!$personID){
        return $form;
    }
    $pipeDriveData = [];
    $personData = pipedrive_api_request('GET', 'persons/'.$personID, []);
    if(!isset($personData['data'])){
        return;
    }else{
        $pipeDriveData['person'] = $personData['data'];
    }

    $orgData = null;
    if(isset($pipeDriveData['person']['org_id']['value'])){
        $orgID = $pipeDriveData['person']['org_id']['value'];
        $orgData = pipedrive_api_request('GET', 'organizations/'.$orgID, []);
        $pipeDriveData['organization']= $orgData['data'];
    }
    $populatedFiedls = getValidPopulatdFields($formID);
    
    $doneFields = [];

    foreach ($form['fields'] as &$field) {   
        foreach ($populatedFiedls as $endpoint => $data) {
            $arrKey = 0;   
            foreach ($data as $fieldID => $pipeDriveKey) {
                if (isset($field->inputs) && is_array($field->inputs)) {
                    $pipeVal = $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';
                    if (!empty($pipeVal)) {
                        if (is_array($pipeVal)) {
                            $pipeVal = $pipeVal[0]['value'] ?? '';
                        }
                        $explodeArr = explode('-', $pipeVal);  
                        foreach ($field->inputs as &$input) {
                            $valuePart = $explodeArr[$arrKey] ?? $pipeVal;
                            if ($input['id'] == $fieldID && !in_array($input['id'], $doneFields)) {
                                if(in_array($field->type,skipPopulateFieldTypes())){
                                    $field->label = 'skip field';
                                    $field->cssClass .= ' pgfc-hidden';
                                    continue;
                                }
                                $input['defaultValue'] = esc_attr( trim( $valuePart ));
                                $field->cssClass .= ' pgfc-readonly';
                                $doneFields[] = $input['id'];
                                $arrKey++;
                            }
                        }                        
                    }
                }    
                else {
                    if ($field->id == $fieldID && !in_array($field->id, $doneFields)) {
                        $pipeVal = $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';
                        if(in_array($field->type,skipPopulateFieldTypes())){
                            $field->label = 'skip field';
                            $field->cssClass .= ' pgfc-hidden';
                            continue;
                        }
                        if (is_array($pipeVal)) {
                            $pipeVal = $pipeVal[0]['value'] ?? '';
                        }
                        if (!empty($pipeVal)) {
                            $field->defaultValue = esc_attr( trim( $pipeVal));    
                            $field->cssClass .= ' pgfc-readonly';
                        } 
                        $doneFields[] = $field->id;
                    }
                }
            }
        }
    }
    // echo '<pre style="width:50%; float:left;">', print_r($populatedFiedls), '</pre>';
    // echo '<pre style="width:50%; float:left;">', print_r($form['fields']), '</pre>';
    return $form;
}
function getValidPopulatdFields($formId){
    $finalArray = [];
    if($formId){
        $keep = ['person', 'organization'];
        $mapping = getMapping($formId);
        $mapping = array_intersect_key($mapping, array_flip($keep));
        foreach($mapping as $key => $val){
            $endPoint = getPipeDriveAPIEndPoint($key);
            if(is_array($val) && !empty($val)){
                foreach($val as $key2 => $val2){
                    $finalArray[$key][$val2['field']] = $val2['apiAttribute'];               
                }
            }
        }
    }
    return  $finalArray;
}

add_filter('gform_field_content', 'make_pgfcFieldsReadonly', 10, 5);
function make_pgfcFieldsReadonly($content, $field, $value, $lead_id, $form_id) {
    if (strpos($field->cssClass, 'pgfc-readonly') !== false) {

        // Handle <input> and <textarea> fields
        $content = preg_replace('/(<input[^>]*type=["\']?(text|hidden|email|number|url|tel)["\']?[^>]*)(>)/i', '$1 readonly$3', $content);
        $content = preg_replace('/(<textarea[^>]*)(>)/i', '$1 readonly$2', $content);

        // Handle <select> fields
        if ($field->type === 'select') {
            $content = preg_replace('/(<select[^>]*)(>)/i', '$1 disabled$2', $content);

            // Preserve selected value
            preg_match('/name=[\'"]([^\'"]+)[\'"]/', $content, $nameMatch);
            $name = $nameMatch[1] ?? '';
            preg_match('/<option[^>]*selected[^>]*value=[\'"]?([^\'">]+)[\'"]?/i', $content, $valueMatch);
            $val = $valueMatch[1] ?? '';
            if ($name && $val !== '') {
                $content .= "<input type='hidden' name='{$name}' value='" . esc_attr($val) . "' />";
            }
        }

        // Handle radio and checkbox fields
        if ($field->type === 'radio' || $field->type === 'checkbox') {
            // Disable the inputs
            $content = preg_replace('/(<input[^>]*type=["\']?(radio|checkbox)["\']?[^>]*)(>)/i', '$1 disabled$3', $content);

            // Add hidden inputs for checked values
            preg_match_all('/<input[^>]*checked[^>]*name=["\']?([^"\']+)["\']?[^>]*value=["\']?([^"\']+)["\']?/i', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $name = $match[1];
                $val = $match[2];
                $content .= "<input type='hidden' name='" . esc_attr($name) . "' value='" . esc_attr($val) . "' />";
            }
        }
    }
    return $content;
}


add_filter('gform_field_content', 'pgfc_hide_fields_and_remove_required_attr', 10, 5);
function pgfc_hide_fields_and_remove_required_attr($content, $field, $value, $lead_id, $form_id) {
    if (strpos($field->cssClass, 'pgfc-hidden') !== false) {
        // Hide field with CSS inline
        $content = '<div style="display:none;">' . $content . '</div>';

        // Optionally remove required attribute from HTML
        $content = preg_replace('/<([^>]+)required([^>]*)>/i', '<$1$2>', $content);
    }
    return $content;
}

add_filter('gform_field_validation', 'pgfc_skip_validation_for_hidden_fields', 10, 4);
function pgfc_skip_validation_for_hidden_fields($result, $value, $form, $field) {
    if (strpos($field->cssClass, 'pgfc-hidden') !== false) {
        $result['is_valid'] = true;
        $result['message'] = '';
    }
    return $result;
}

function formatPipedriveDateForGravityForm($gf_format, $pipedrive_date) {
    // Try to parse Pipedrive date (handle both date and datetime)
    $date = \DateTime::createFromFormat('Y-m-d', $pipedrive_date) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $pipedrive_date);
    
    // If still invalid
    if (!$date) {
        return '';
    }

    // Map Gravity Forms date formats to PHP date() equivalents
    switch (strtolower($gf_format)) {
        case 'mdy':
            return $date->format('m-d-Y');
        case 'dmy':
            return $date->format('d-m-Y');
        case 'ymd':
            return $date->format('Y-m-d');
        case 'd-m-y':
            return $date->format('d-m-Y');
        case 'm-d-y':
            return $date->format('m-d-Y');
        case 'y-m-d':
            return $date->format('Y-m-d');
        case 'human-readable':
            return $date->format('F-j-Y'); // e.g., January 3, 2024
        default:
            // Fallback to ISO format
            return $date->format('Y-m-d');
    }
}
