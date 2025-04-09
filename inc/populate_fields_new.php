<?php
add_filter('gform_pre_render', 'prefill_and_disable_fields_globally');
add_filter('gform_pre_validation', 'prefill_and_disable_fields_globally');

function prefill_and_disable_fields_globally($form) {
    if (!is_user_logged_in()) {
        return $form;
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
        return $form;
    } else {
        $pipeDriveData['person'] = $personData['data'];
    }

    if(isset($pipeDriveData['person']['org_id']['value'])){
        $orgID = $pipeDriveData['person']['org_id']['value'];
        $orgData = pipedrive_api_request('GET', 'organizations/'.$orgID, []);
        $pipeDriveData['organization'] = $orgData['data'] ?? [];
    }

    $populatedFiedls = getValidPopulatdFields($formID);
    $doneFields = [];

    foreach ($form['fields'] as &$field) {
        foreach ($populatedFiedls as $endpoint => $data) {
            foreach ($data as $fieldID => $pipeDriveKey) {
                $pipeVal = $pipeDriveData[$endpoint][$pipeDriveKey] ?? '';
                if (is_array($pipeVal)) {
                    $pipeVal = $pipeVal['value'] ?? reset($pipeVal)['value'] ?? '';
                }

                if (empty($pipeVal)) continue;

                if (isset($field->inputs) && is_array($field->inputs)) {
                    foreach ($field->inputs as &$input) {
                        if ($input['id'] == $fieldID && !in_array($input['id'], $doneFields)) {
                            $valuePart = getFormattedFieldValue($field, $pipeVal, $input['name']);

                            if (in_array($field->type, skipPopulateFieldTypes())) {
                                $field->label = 'skip field';
                                $field->cssClass .= ' pgfc-hidden';
                                continue;
                            }

                            $input['defaultValue'] = esc_attr(trim($valuePart));
                            $field->cssClass .= ' pgfc-readonly';
                            $doneFields[] = $input['id'];
                        }
                    }
                } else {
                    if ($field->id == $fieldID && !in_array($field->id, $doneFields)) {
                        $valuePart = getFormattedFieldValue($field, $pipeVal);

                        if (in_array($field->type, skipPopulateFieldTypes())) {
                            $field->label = 'skip field';
                            $field->cssClass .= ' pgfc-hidden';
                            continue;
                        }

                        $field->defaultValue = esc_attr(trim($valuePart));
                        $field->cssClass .= ' pgfc-readonly';
                        $doneFields[] = $field->id;
                    }
                }
            }
        }
    }

    return $form;
}

function getValidPopulatdFields($formId){
    $finalArray = [];
    if($formId){
        $keep = ['person', 'organization'];
        $mapping = getMapping($formId);
        $mapping = array_intersect_key($mapping, array_flip($keep));
        foreach($mapping as $key => $val){
            if(is_array($val) && !empty($val)){
                foreach($val as $key2 => $val2){
                    $finalArray[$key][$val2['field']] = $val2['apiAttribute'];               
                }
            }
        }
    }
    return $finalArray;
}

function getFormattedFieldValue($field, $rawValue, $inputName = '') {
    if (is_array($rawValue)) {
        $rawValue = $rawValue['value'] ?? reset($rawValue)['value'] ?? '';
    }

    switch ($field->type) {
        case 'date':
            return formatPipedriveDateForGravityForm($field->dateFormat ?? 'ymd', $rawValue);
        case 'checkbox':
            return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
        case 'name':
            $nameParts = explode(' ', trim($rawValue), 2);
            if (strpos($inputName, '.3') !== false) {
                return $nameParts[0] ?? '';
            } elseif (strpos($inputName, '.6') !== false) {
                return $nameParts[1] ?? '';
            }
            return $rawValue;
        default:
            return $rawValue;
    }
}

add_filter('gform_field_content', 'make_pgfcFieldsReadonly', 10, 5);
function make_pgfcFieldsReadonly($content, $field, $value, $lead_id, $form_id) {
    if (strpos($field->cssClass, 'pgfc-readonly') !== false) {
        $content = preg_replace('/(<input[^>]*type=["\']?(text|hidden|email|number|url|tel)["\']?[^>]*)(>)/i', '$1 readonly$3', $content);
        $content = preg_replace('/(<textarea[^>]*)(>)/i', '$1 readonly$2', $content);

        if ($field->type === 'select') {
            $content = preg_replace('/(<select[^>]*)(>)/i', '$1 disabled$2', $content);
            preg_match('/name=[\'"]([^\'"]+)[\'"]/', $content, $nameMatch);
            $name = $nameMatch[1] ?? '';
            preg_match('/<option[^>]*selected[^>]*value=[\'"]?([^\'"]+)[\'"]?/i', $content, $valueMatch);
            $val = $valueMatch[1] ?? '';
            if ($name && $val !== '') {
                $content .= "<input type='hidden' name='{$name}' value='" . esc_attr($val) . "' />";
            }
        }

        if ($field->type === 'radio' || $field->type === 'checkbox') {
            $content = preg_replace('/(<input[^>]*type=["\']?(radio|checkbox)["\']?[^>]*)(>)/i', '$1 disabled$3', $content);
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
        $content = '<div style="display:none;">' . $content . '</div>';
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
    $date = \DateTime::createFromFormat('Y-m-d', $pipedrive_date) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $pipedrive_date);
    if (!$date) {
        return '';
    }

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
            return $date->format('F-j-Y');
        default:
            return $date->format('Y-m-d');
    }
}
