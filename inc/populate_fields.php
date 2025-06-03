<?php
 add_filter('gform_pre_render', 'prefill_and_disable_fields_globally');
 add_filter('gform_pre_validation', 'prefill_and_disable_fields_globally');

function prefill_and_disable_fields_globally($form) {
    $formTitle = $form['title'];
    $formID = $form['id'];
    $action = 'Populate fields for form: '.$formTitle.'('.$formID.')';
    if (!is_user_logged_in()) {
        $form = assignOrganizationField($formID , $form);
        return $form; // Do nothing if not logged in
        //return $form; // Do nothing if not logged in
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
    $gravityFormField = getValidFieldGravityForm($form['fields'] , $populatedFiedls); 

    $formFields = [];
    foreach ($gravityFormField as $endpoint => $gravityfields) {
        foreach ($gravityfields as $field_id => $data) {
            $pipeDriveKey      = $data['pipedrive_key']; 
            $field              = $data['fields'];
            $pipeVal = $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';     
            if($field->visibility == 'visible'){
            }     
            if (!empty($pipeVal)) {
                $fieldFormated = assignDefaultValueByType($field, $pipeVal, ['endPoint'=>$endpoint, 'pipeFieldKey'=>$pipeDriveKey ], $pipeDriveData[$endpoint]);
                if($fieldFormated){
                    $formFields[] = $fieldFormated;
                }
                continue;
            }
        }
    }
    $form['fields'] = mergeArraysByIdAndPosition($form['fields'], $formFields);


    return $form;
}
function assignOrganizationField($formID , $form){
    $populatedFiedls = getValidPopulatdFields($formID);
    $gravityFormField = getValidFieldGravityForm($form['fields'] , $populatedFiedls); 
    $formFields = [];
    foreach ($gravityFormField as $endpoint => $gravityfields) {
        foreach ($gravityfields as $field_id => $data) {
            $pipeDriveKey       = $data['pipedrive_key']; 
            $field              = $data['fields'];
            $field->cssClass .= " ".$endpoint.'Field';    
            continue;
        }
    }
    $form['fields'] = mergeArraysByIdAndPosition($form['fields'], $formFields);
    return $form;
}


function mergeArraysByIdAndPosition($arr1, $arr2) {
    $idsToReplace = array_column($arr2, 'id');

    // Create a mapping of IDs to their respective positions in $arr1
    $positionMap = [];
    foreach ($arr1 as $index => $item) {
        $positionMap[$item['id']] = $index;
    }

    // Iterate over $arr2 and place in the same position or at the end
    foreach ($arr2 as $item) {
        $id = $item['id'];
        if (isset($positionMap[$id])) {
            // Replace in the original position
            $arr1[$positionMap[$id]] = $item;
        } else {
            // If the ID doesn't exist in the original array, add it at the end
            $arr1[] = $item;
        }
    }

    return $arr1;
}
function assignDefaultValueByType($field, $value, $pipeArr = [], $pipeData = false) {
    $fieldType = $field->type;
    $doneFields = [];
    switch ($fieldType) {
        case 'name':
            $nameParts = explode(' ', $value, 2);
            $firstName = $nameParts[0];
            $lastName = implode(' ', array_slice($nameParts, 1));
            $arrayCom = array($firstName , $lastName);
            $counts = 0;
            foreach ($field->inputs as $indexss=> &$input) {
                if (isset($input['autocompleteAttribute']) && (!isset($input['isHidden']) || $input['isHidden'] != 1 ) && !in_array($input['id'], $doneFields)) {
                    $input['defaultValue'] = $arrayCom[$counts];
                    $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';
                    $doneFields[] = $input['id'];
                    $counts++;
                }
             }
            break;

        case 'address':
            foreach ($field->inputs as &$input) {
                if (isset($input['pipedrive_key']) && isset($pipeData[$input['pipedrive_key']])) {        
                    $input['defaultValue'] = esc_attr(trim($pipeData[$input['pipedrive_key']]));
                    $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';
                }
            }
            break;
        case 'select':
        case 'radio':
        case 'checkbox':
        case 'multi_choice':
            if(isset($pipeArr['endPoint']) && isset($pipeArr['pipeFieldKey'])){
                $fieldOption = pipedriveGetVieldName($pipeArr['pipeFieldKey'], $pipeArr['endPoint']);  
            }else{
                break;
            }
            foreach ($field->choices as &$choice) {
                $selectedData = [];
                if(isset($pipeArr['endPoint']) && isset($pipeArr['pipeFieldKey'])){
                    if(isset($fieldOption['options'])){
                        $selectedData = getSelectedData($fieldOption['options'], $value);  
                    }                
                }

                $choiceTwo = $choice['value'];
                if (in_array($choiceTwo, $selectedData) || array_key_exists($choiceTwo, $selectedData)) {
                    $choice['isSelected'] = true;
                    $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';
                }                
            }
            unset($fieldOption);
            unset($choiceTwo);
            unset($choice);
            break;

        default:
            if(is_array($value) && !empty($value)){
                if(isset($value[0]))
                {
                    $value = $value[0];
                }
                if(isset($value['value'])){
                    $value = $value['value'];
                }
                $field->defaultValue = esc_attr(trim($value));
                $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';
            }else{
                $field->defaultValue = esc_attr(trim($value));
                $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';
            }
            break;
    }

    // Make read-only (optional UI hint)
    $field->cssClass .= ' pgfc-readonly '.$pipeArr['endPoint'].'Field';

    return $field;
}
function getSelectedData($options, $val){
    if(is_string($val)){
        $val = explode(',', $val);
    }
    $arr = [];
    foreach($options as $optVal){
        if((isset($optVal['id']) && in_array($optVal['id'], $val)) || isset($optVal['label']) && in_array($optVal['label'], $val)){
            $arr[$optVal['id']] = $optVal['label'];
        }
    }
    return $arr;
}
function isDecimal($number) {
    return is_numeric($number) && floor($number) != $number;
}
function getValidFieldGravityForm($formField, $populatedFiedls){
    foreach ($formField as $field) {
        foreach ($populatedFiedls as $endPoint=> $group) {
            foreach ($group as $field_id => $field_key) {                
                if (((int)$field->id === (int)$field_id) ||
                (int)$field->id === floor($field_id)
                ) {
                    if (isDecimal($field_id) && isset($field->inputs)) {
                        foreach ($field->inputs as &$subInput) {
                            if (isset($subInput['id']) && $subInput['id'] == $field_id) {
                                $subInput['pipedrive_key'] = $field_key;
                            }
                        }
                        unset($subInput); // Best practice to avoid accidental reference modification
                    }
                    $filtered_fields[$endPoint][$field->id] = [
                        'pipedrive_key'=>$field_key,
                        'fields'=>$field
                    ];
                }
            }
        }
    }
    return $filtered_fields;
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
            $content = preg_replace('/(<select[^>]*)(>)/i', '$1 readonly$2', $content);

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
            $content = preg_replace('/(<input[^>]*type=["\']?(radio|checkbox)["\']?[^>]*)(>)/i', '$1 readonly$3', $content);

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
            accent-color: #8a8a8a; /* Modern browsers support this */
        }
        .pgfc-readonly input[type=url] , .pgfc-readonly input[type=tel]{
            background: #e9ecef;
        }
        .pgfc-readonly input[type="radio"]:checked ,  .pgfc-readonly input[type="checkbox"]:checked, pgfc-readonly input[type="checkbox"]{
            accent-color: #8a8a8a;
        }
        .pgfc-readonly select{
            pointer-events: none;
        }
        .pgfc-readonly label {
            color: #888;
        }
    </style>';
});

