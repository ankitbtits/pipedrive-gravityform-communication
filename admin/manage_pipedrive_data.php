<?php

function showPipedriveData($userID){
    if(!$userID){
        return;
    }
    $personID = get_user_meta($userID, 'pipedrive_person_id', true);
    if(!$personID){
        return;
    }
    $pipeDriveData = [];
    $personData = pipedrive_api_request('GET', 'persons/'.$personID, []);
    $personData = $personData['data'];
    $pipeDriveData['persons'] = $personData;
    
    $orgData = null;
    if(isset($personData['org_id']['value'])){
        $orgID = $personData['org_id']['value'];
        $orgData = pipedrive_api_request('GET', 'organizations/'.$orgID, []);
        $pipeDriveData['organizations']= $orgData['data'];
    }

    $allDeals = pipedrive_api_request('GET', 'persons/'.$personID.'/deals/', []);
    $pipeDriveData['deals'] = $allDeals['data'];


    $allActivites = pipedrive_api_request('GET', 'persons/'.$personID.'/activities/', []);
    $pipeDriveData['activities'] = $allActivites['data'];
    ?>
    <h3>Pipedrive Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="custom_field">Person ID</label></th>
            <td>
                <?php echo $personID; ?>
            </td>
        </tr>
    </table>
    <?php
    if(!empty($personData) && is_array($personData)):
    $alloweData = alloedProfileData();
    foreach($alloweData as $endPoint => $data):
        $apiData = $pipeDriveData[$endPoint];
    ?>
    <h3><?php echo $endPoint;?></h3>
    <table class="adminTable" border="1">
    <?php
    if(in_array($endPoint, ['deals','activities'])){        
        foreach($apiData as $key3 => $val3){
            foreach($data as $key2 => $data2){
                $key = $data2['key'];
                $value = $val3[$key];
                $keyInfo = pipedriveGetVieldName($key);
                $keyName = $keyInfo['name'];
            ?>
                <input type="hidden" name="pipedrive[<?php echo $endPoint;?>][<?php echo $key3 + 1;?>][id]" value="<?php echo $val3['id'];?>">
                <tr>
                    <th><?php echo $keyName;?></th>
                    <td>
                        <?php echo formatDisplayData($keyInfo, $key, $value, $endPoint, $key3 + 1);?>
                    </td>
                </tr>
                <?php
            }            
        }  
    } else{
        foreach($data as $key2 => $data2){
            $key = $data2['key'];
            $value = $apiData[$key];
            $keyInfo = pipedriveGetVieldName($key);
            $keyName = $keyInfo['name'];
    ?>    
        <input type="hidden" name="pipedrive[<?php echo $endPoint;?>][id]" value="<?php echo $apiData['id'];?>">                
        <tr>
            <th><?php echo $keyName;?></th>
            <td>
                <?php echo formatDisplayData($keyInfo, $key, $value, $endPoint);?>
            </td>
        </tr>
    <?php
        }
    }
    ?>
    </table>
    <?php 
        endforeach; 
    endif;
}

function formatDisplayData($keyInfo, $key, $value, $endpoint, $key3 = false){
    $fieldType = isset($keyInfo['field_type'])? $keyInfo['field_type']:null;
    $fieldOption = isset($keyInfo['options'])? $keyInfo['options']:null;    
    $name = 'pipedrive['.$endpoint.']'.($key3?'['.$key3.']':'').'['.$key.']';
    $res = getRightFieldType($fieldType, $name, $value, $fieldOption);    
    return $res;
}

function getRightFieldType($type, $name, $value, $options = []) {
    $res = ''; // Default response

    // Detect multi-value fields that Pipedrive marks as "varchar"
    $isMultiValue = (stripos($name, 'email') !== false || stripos($name, 'phone') !== false);

    switch ($type) {
        case 'varchar': // Handles both single and multi-value varchar fields
        case 'text':
            if ($isMultiValue && is_array($value)) {
                // Multi-value handling (email/phone)
                foreach ($value as $item) {
                    $res .= '<input type="text" value="'.esc_attr($item['value']).'" name="'.esc_attr($name).'[]" /><br/>';
                }
                $res .= '<input type="text" name="'.esc_attr($name).'[]" placeholder="Add new value" />';
            } else {
                // Standard text input
                $res = '<input type="text" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            }
            break;

        case 'int': // Integer Field
        case 'double': // Decimal Field
            $res = '<input type="number" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'date': // Date Field
            $res = '<input type="date" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'time': // Time Field
            $res = '<input type="time" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'daterange': // DateTime Field
            $res = '<input type="datetime-local" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'enum': // Single Select Dropdown
            if (!empty($options) && is_array($options)) {
                $res = '<select name="'.esc_attr($name).'">';
                foreach ($options as $option) {
                    $id = esc_attr($option['id']);
                    $label = esc_html($option['label']);
                    $selected = ($id == $value) ? 'selected' : '';
                    $res .= "<option value='$id' $selected>$label</option>";
                }
                $res .= '</select>';
            } else {
                $res = 'Options not provided';
            }
            break;

        case 'set': // Multiple Select (Checkboxes)
            if (!empty($options) && is_array($options)) {
                // Convert string to array
                $selectedValues = (!empty($value)) ? explode(',', $value) : [];

                foreach ($options as $option) {
                    $id = esc_attr($option['id']);
                    $label = esc_html($option['label']);
                    $checked = (in_array($id, $selectedValues)) ? 'checked' : '';
                    $res .= "<label><input type='checkbox' name='".esc_attr($name)."[]' value='$id' $checked> $label</label><br/>";
                }
            } else {
                $res = 'Options not provided';
            }
            break;

        case 'user': // User Field (Dropdown)
        case 'org': // Organization Field (Dropdown)
        case 'people': // Person Field (Dropdown)
            if (!empty($options) && is_array($options)) {
                $res = '<select name="'.esc_attr($name).'">';
                foreach ($options as $option) {
                    $id = esc_attr($option['id']);
                    $label = esc_html($option['name']);
                    $selected = ($id == $value) ? 'selected' : '';
                    $res .= "<option value='$id' $selected>$label</option>";
                }
                $res .= '</select>';
            } else {
                $res = 'Options not provided';
            }
            break;

        case 'address': // Address Field
            $res = '<input type="text" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'phone': // Phone Field (Multiple Values Possible)
            $res = '';
            if (!empty($value) && is_array($value)) {
                foreach ($value as $phone) {
                    $phoneValue = esc_attr($phone['value']);
                    $res .= "<input type='text' name='".esc_attr($name)."[]' value='$phoneValue' /><br/>";
                }
            }
            $res .= "<input type='text' name='".esc_attr($name)."[]' placeholder='Add new phone' />";
            break;

        default:
            $res = 'Unsupported field type';
            break;
    }

    return $res;
}

function updatePipeDriveData($data){
    if (isset($_POST['pipedrive'])) {
        $_POST['pipedrive']['persons'] = $_POST['pipedrive']['persons'];
        $apiData = $_POST['pipedrive'];
        foreach($apiData as $key => $val){
            if(in_array($key, ['deals','activities'])){
                foreach($val as $val2){
                    $id = $val2['id'];
                    $apiRes = pipedrive_api_request('PUT',$key.'/'.$id, $val2);
                    if(!isset($apiRes['success'])){
                        insertApiErrorLog('Updating  '.$key.' through profile page for userID '.$user_id ,$key, $val2, $apiRes);
                    }
                }
            }else{
                $id = $val['id'];
                $apiRes = pipedrive_api_request('PUT',$key.'/'.$id, $val);
                if(!isset($apiRes['success'])){
                    insertApiErrorLog('Updating  '.$key.' through profile page for userID '.$user_id ,$key, $val, $apiRes);
                }
            }
        }
    }else{
        return;
    }
}