<?php
add_filter('gform_pre_render', 'prefill_and_disable_fields_globally');
add_filter('gform_pre_validation', 'prefill_and_disable_fields_globally');

function prefill_and_disable_fields_globally($form) {
    $formTitle = $form['title'];
    $formID = $form['id'];
    $action = 'Populate fields for form: '.$formTitle.'('.$formID.')';
    if (!is_user_logged_in()) {
        return $form; // Do nothing if not logged in
    }
    $userID = get_current_user_id();
    $personID = get_user_meta($userID, 'pipedrive_person_id', true);
    if(!$personID){
        return $form;
    }
    $pipeDriveData = [];
    $personData = pipedrive_api_request('GET', 'persons/'.$personID, [], $action);
    if(!isset($personData['data'])){
        return;
    }else{
        $pipeDriveData['person'] = $personData['data'];
    }

    $orgData = null;
    if(isset($pipeDriveData['person']['org_id']['value'])){
        $orgID = $pipeDriveData['person']['org_id']['value'];
        $orgData = pipedrive_api_request('GET', 'organizations/'.$orgID, [],  $action);
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
                        $selectedValues = array_map('trim', preg_split('/[,|-]/', $pipeVal));
                        if ($field->type === 'checkbox' || $field->type === 'radio') { //Gravity field type checked
                            foreach ($field->choices as &$choice) {
                                // Match based on label (text), not just value
                                if (in_array($choice['text'], $selectedValues) || in_array($choice['value'], $selectedValues)) {
                                    $choice['isSelected'] = true;
                                    $field->cssClass .= ' pgfc-readonly';
                                }
                            }
                        }
                        // Optional: still handle defaultValue for text-based fields
                        if($field->type == 'text') //Gravity field type checked
                        {
                            foreach ($field->inputs as &$input) {
                                $valuePart = $selectedValues[$arrKey] ?? $pipeVal;
                                if ($input['id'] == $fieldID && !in_array($input['id'], $doneFields)) {
                        
                                    $input['defaultValue'] = esc_attr(trim($valuePart));
                                    $field->cssClass .= ' pgfc-readonly';
                             
                                    $doneFields[] = $input['id'];
                                    $arrKey++;
                                }
                            }
                        }
                        if($field->type == 'address') //Gravity field type checked
                        {
                            foreach ($field->inputs as &$input) {
                                $valuePart = $selectedValues[$arrKey] ?? $pipeVal;
                                if ($input['id'] == $fieldID && !in_array($input['id'], $doneFields)) {
                        
                                    $input['defaultValue'] = esc_attr(trim($valuePart));
                                    $field->cssClass .= ' pgfc-readonly';
                             
                                    $doneFields[] = $input['id'];
                                    $arrKey++;
                                }
                            }
                        }
                        if($field->type == "date") //Gravity field type checked
                        {
                            foreach ($field->inputs as &$input) 
                            {
                                $inputID = $input['id'];
                                if(isset($data[$inputID]) &&  !in_array($input['id'], $doneFields) ){
                                    $fieldID = $data[$inputID];
                                    $pipeValDate = $pipeDriveData[$endpoint][  $fieldID ] ?? ''; //2025-05-14 custom date coming
                                    if (!empty($pipeValDate)) {
                                        [$year, $month, $day] = explode('-', $pipeValDate);
                                        if (str_ends_with($inputID, '.1')) {
                                            $input['defaultValue'] = $month;
                                        } elseif (str_ends_with($inputID, '.2')) {
                                            $input['defaultValue'] = $day;
                                        } elseif (str_ends_with($inputID, '.3')) {
                                            $input['defaultValue'] = $year;
                                        }
                                        $doneFields[] = $input['id'];
                                        $arrKey++;
                                    }
                                }
                            }
                        }
                        if($field->type== "name" ) //Gravity field type checked
                        {
                            foreach ($field->inputs as &$input) {
                                $valuePart = $selectedValues[$arrKey] ?? $pipeVal;
                                
                                if ( isset($data[$input['id']]) && $input['id'] == $fieldID &&  !in_array($input['id'], $doneFields) ) {
                        
                                    $input['defaultValue'] = esc_attr(trim($valuePart));
                                    $field->cssClass .= ' pgfc-readonly';
                             
                                    $doneFields[] = $input['id'];
                                    $arrKey++;
                                }
                            }
                        }
                        $fieldID2  = floor(  $field->id ); //Case when field id not found in pipedrive
                        if (  ($field->type=="multi_choice" || $field->type=="checkbox"))
                        {
                            $pipedriveGetData =  pipedriveGetVieldName($pipeDriveKey); //For check Pipeline return value
                            $pipeVal          =  $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';
                            if($pipedriveGetData['field_type'] == 'set' || $pipedriveGetData['field_type'] == 'enum' )
                            {
                                $options = $pipedriveGetData['options']; // array of all available options
                                $selectedIds = array_map('trim', explode(',', $pipeVal)); // convert to array
                                $selectedLabels = [];
                                foreach ($options as $option) {
                                    if (in_array($option['id'], $selectedIds)) {
                                        $selectedLabels[] = $option['label'];
                                    }
                                }
                                if(isset($field->choices)){
                                    foreach ($field->choices as &$choiceVal) {
                                        if (in_array($choiceVal['text'], $selectedLabels) || in_array($choiceVal['value'], $selectedLabels)) {
                                            $choiceVal['isSelected'] = true;
                                            $field->cssClass .= ' pgfc-readonly';
                                        }
                                    }
                                    unset($choiceVal); // good practice after reference loop
                                }  
                                $doneFields[] = $pipedriveGetData['id'];
                                $doneFields[] = $field->id;
                            }
                       }
                    }
                }    
                else {
                    if (  $field->id == $fieldID  && !in_array($field->id, $doneFields)) {
                        $pipedriveGetData =  pipedriveGetVieldName($pipeDriveKey); //For check Pipeline return value
                        $pipeVal          =  $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';
                        if(isset( $pipedriveGetData['field_type'] ) && $pipedriveGetData['field_type'] == 'set' && $field->type === 'radio'){
                            $options = $pipedriveGetData['options']; // array of all available options
                            $selectedIds = array_map('trim', explode(',', $pipeVal)); // convert to array
                            $selectedLabels = [];
                            foreach ($options as $option) {
                                if (in_array($option['id'], $selectedIds)) {
                                    $selectedLabels[] = $option['label'];
                                }
                            }
                            if (!empty($selectedLabels) && isset($selectedLabels[0])) {
                                $firstLabelVal = $selectedLabels[0]; // e.g. 'Ambiente'
                                $pipeVal = $firstLabelVal; // set the matched value //STRING 
                            }
                        }
                        if(isset( $pipedriveGetData['field_type'] ) && $pipedriveGetData['field_type'] == 'enum' && $field->type === 'radio'){
                            $options = $pipedriveGetData['options']; // array of all available options
                            $selectedIds = array_map('trim', explode(',', $pipeVal)); // convert to array
                            $selectedLabels = [];
                            foreach ($options as $option) {
                                if (in_array($option['id'], $selectedIds)) {
                                    $selectedLabels[] = $option['label'];
                                }
                            }
                            if (!empty($selectedLabels) && isset($selectedLabels[0])) {
                                $firstLabelVal = $selectedLabels[0]; // e.g. 'Ambiente'
                                $pipeVal = $firstLabelVal; // set the matched value //STRING 
                            }
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
    //echo '<pre style="width:50%; float:left;">', print_r($pipeDriveData), '</pre>';

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
        $content = '<div style="display:block;">' . $content . '</div>';

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

add_action('wp_head' , function(){
    echo '<style>
.pgfc-readonly input[type="radio"] {
  pointer-events: none;
  opacity: 1; /* Keep it visible */
  cursor: not-allowed;
  accent-color: lightgray; /* Modern browsers support this */
}

.pgfc-readonly input[type="radio"]:checked {
  accent-color: lightgray;
}

.pgfc-readonly label {
  color: #888;
}
    </style>';
});