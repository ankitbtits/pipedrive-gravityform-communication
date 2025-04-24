<?php

function showPipedriveData($userID){    
    $action = 'showPipedriveData';
    if(!$userID){
        return;
    }
    $personID = (int) get_user_meta($userID, 'pipedrive_person_id', true);
    if(!$personID){
        return;
    }
    $pipeDriveData = [];
    $personData = pipedrive_api_request('GET', 'persons/'.$personID, [], $action);
    if(!isset($personData['data'])){
        echo 'We are unable to load data for personID : '.$personID.'. Either person does not exist in the pipedrive. Or contact plugin developer.';
        return;
    }
    $personData = $personData['data'];
    $pipeDriveData['persons'] = $personData;
    
    $orgData = null;
    if(isset($personData['org_id']['value'])){
        $orgID = $personData['org_id']['value'];
        $orgData = pipedrive_api_request('GET', 'organizations/'.$orgID, [], $action);
        $pipeDriveData['organizations']= $orgData['data'];
    }

    $allDeals = pipedrive_api_request('GET', 'persons/'.$personID.'/deals/', [], $action);
    $pipeDriveData['deals'] = $allDeals['data'];
    ?>
    <?php
        if(!is_user_profile_page()){
            echo '<form method="post">';
        }
    ?>
    <h3><?php _e('Pipedrive Information', 'pgfc');?></h3>
    <table class="form-table">
        <tr>
            <th><label for="custom_field"><?php _e('Person ID', 'pgfc');?></label></th>
            <td>
                <?php echo $personID; ?>
            </td>
        </tr>
    </table>
    <?php
    if(!empty($personData) && is_array($personData)):
    $alloweData = alloedProfileData();
    unset($alloweData['activities']);

    echo '<div class="dataTabs"><ul>';
    $count = 0;
    foreach($alloweData as $endPoint => $data){
        $count++;
        echo '<li><a href="javascript:;" data-id="'.$endPoint.'" class="
        '.(($count == 1)?'active':'').'
        ">'.$endPoint.'</a></li>';
    }   
    echo '</ul></div>';
    $count = 0;
    foreach($alloweData as $endPoint => $data):
        $count++;
        $apiData = [];
        if(isset($pipeDriveData[$endPoint])){
            $apiData = $pipeDriveData[$endPoint];  
        }     
    ?>
    <div class="manage-pipe-table dataTabsContent" id="dataTabs_<?php echo $endPoint;?>"
    <?php echo (($count != 3)?'style="display:none;"':'');?>
    >
        <h3><?php echo $endPoint;?></h3>   
        <?php
             if(empty($apiData)){
                _e('Could not find any '.$endPoint.' for this user.');
             }
        ?>

        <?php
        if(!empty($apiData)){
            if(in_array($endPoint, ['deals'])){ //'activities' excluded activities because now we are fetching activities for each deal      
                foreach($apiData as $eachDeal){
                    echo '<div class="eachDealCon"><table>';
                    if (isset($eachDeal['title'])) {
                        echo "<tr><th>Name: </th><td>" .$eachDeal['title'].'</td></tr>';
                    }
                    if (isset($eachDeal['label'])) {
                        $dealTypeID = $eachDeal['label']; 
                        $dealTypeIDs = array_map('trim', explode(',', $dealTypeID));
                        $dataDeal = pipedriveGetVieldName()['deal'];
                        if (!empty($dataDeal)) {
                            foreach ($dataDeal as $key => $fields) {
                                if ($fields['key'] == "label") {
                                    $options = $fields['options']; // array of available options
                                    $selectedLabels = [];            
                                    foreach ($options as $option) {
                                        if (in_array($option['id'], $dealTypeIDs)) {
                                            $selectedLabels[] = $option['label'];
                                        }
                                    }
                                    $selectedLabelString = implode(', ', $selectedLabels);
                                    echo "<tr><th>Deal Labels</th><td> $selectedLabelString</td></tr>";
                                    break;        
                                }
                            }
                        }
                    }            
                    if (isset($eachDeal['stage_id']) && isset($eachDeal['pipeline_id'])) {
                        $pipelineID = $eachDeal['pipeline_id'];
                        $stageID = $eachDeal['stage_id'];
        
                        $dataStage =  get_option( 'pipedrive_stages ');    
                        //echo $pipelineID.'<pre>aaa'.$stageID, print_r($dataStage), '</pre>';
                        if(isset($dataStage[$pipelineID])){
                            $dataStage = $dataStage[$pipelineID];
                        }
                        if(is_array($dataStage)){
                            foreach ($dataStage as $stage) {
                                if($stageID == $stage['id']){
                                    echo '<tr><th>Stage Name</th><td>'.$stage['name'].'</td></tr>';
                                }
                            }
                        }
                    }
                    $allActivites = pipedrive_api_request('GET', "deals/".$eachDeal['id']."/activities", [], $action);
                    $files = pipedrive_api_request('GET', "deals/".$eachDeal['id']."/files", [], $action);
                    $filesData = [];
                    if(isset($files['data'])){
                        $filesData = $files['data'];
                        $filesOutput = '';
                        $count = 0;
                        foreach ($filesData as $file) {
                            $count++;
                            $fileName = esc_html($file['file_name']);
                            $fileID = esc_attr($file['id']);
                            $downloadURL = getPipedriveFileDownloadLink($fileID);
                            $filesOutput .= "$count. <a href='{$downloadURL}' target='_blank' download>{$fileName}</a><br>";
                        }
                        echo '<tr><th>'.__('Files', 'pgfc').'</th><td>'. $filesOutput.'</td></tr>';       
                    }    
                            
                    
                    if(isset($allActivites['data'])){
                        $allActivites = $allActivites['data'];
                        $actData = alloedProfileData()['activities'];
                        $activitiesNames = '';
                        $count = 0;
                        foreach($allActivites as $actKey => $activity){     
                            $count++;
                            $actvityOrgName = $activity['org_name'];
                            $orgNameHtml = '';
                            if (!empty($actvityOrgName)) {
                                $orgNameHtml = "<span class='activityOrg'>".__('Organization', 'pgfc')." : $actvityOrgName</span>";
                            }
                            $status = $activity['done']?'done':'notDone';
                            $activitiesNames .= "<div class='eachActivity'> <i class='activityStatus $status'></i><span class='activityName'>{$activity['subject']}</span>
                            $orgNameHtml                         
                            </div><br>";                  
                        }   
                        echo '<tr><th>'.__('Deals Activities', 'pgfc').'</th><td>'. $activitiesNames.'</td></tr>';              
                    }
                    echo '</table></div>';
                }
            } 
            else
            {
                echo '<table class="adminTable" border="1">';
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
            echo '</table>';        
            }
        }
        ?>

    </div>
    <?php 
    endforeach; 
    endif;
    if(!is_user_profile_page()){
        echo '
        <p class="submit"><input type="submit" value="Update" class="button formButton" /></p>
        </form>
        ';
    }
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
    $readonly = '';
    if (strpos($name, "[activities]") !== false || strpos($name, "[deals]") !== false) {
        $readonly = 'disabled readonly';
    } 

    switch ($type) {
        case 'varchar': // Handles both single and multi-value varchar fields
        case 'text':
            if ($isMultiValue && is_array($value)) {
                // Multi-value handling (email/phone)
                foreach ($value as $item) {
                    $res .= '<input '.$readonly.' type="text" value="'.esc_attr($item['value']).'" name="'.esc_attr($name).'[]" /><br/>';
                }
                $res .= '<input '.$readonly.' type="text" name="'.esc_attr($name).'[]" placeholder="Add new value" />';
            } else {
                // Standard text input
                $res = '<input '.$readonly.' type="text" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            }
            break;

        case 'int': // Integer Field
        case 'double': // Decimal Field
            $res = '<input '.$readonly.' type="number" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'date': // Date Field
            $res = '<input '.$readonly.' type="date" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'time': // Time Field
            $res = '<input '.$readonly.' type="time" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'daterange': // DateTime Field
            $res = '<input '.$readonly.' type="datetime-local" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'enum': // Single Select Dropdown
            if (!empty($options) && is_array($options)) {
                $res = '<select '.$readonly.' name="'.esc_attr($name).'">';
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
                    $res .= "<label>
                    <input type='hidden' name='".esc_attr($name)."[]' value='0'>
                    <input $readonly type='checkbox' name='".esc_attr($name)."[]' value='$id' $checked> $label</label>
                    <br/>";
                }
            } else {
                $res = 'Options not provided';
            }
            break;

        case 'user': // User Field (Dropdown)
        case 'org': // Organization Field (Dropdown)
        case 'people': // Person Field (Dropdown)
            if (!empty($options) && is_array($options)) {
                $res = '<select '.$readonly.' name="'.esc_attr($name).'">';
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
            $res = '<input '.$readonly.' type="text" value="'.esc_attr($value).'" name="'.esc_attr($name).'" />';
            break;

        case 'phone': // Phone Field (Multiple Values Possible)
            $res = '';
            if (!empty($value) && is_array($value)) {
                foreach ($value as $phone) {
                    $phoneValue = esc_attr($phone['value']);
                    $res .= "<input '.$readonly.' type='text' name='".esc_attr($name)."[]' value='$phoneValue' /><br/>";
                }
            }
            $res .= "<input '.$readonly.' type='text' name='".esc_attr($name)."[]' placeholder='Add new phone' />";
            break;

        default:
            $res = 'Unsupported field type';
            break;
    }

    return $res;
}

function updatePipeDriveData($data){
    $action = 'updatePipeDriveData';

    if (isset($_POST['pipedrive'])) {
        $_POST['pipedrive']['persons'] = $_POST['pipedrive']['persons'];
        $apiData = $_POST['pipedrive'];
        foreach($apiData as $key => $val){
            if(in_array($key, ['deals','activities'])){
                foreach($val as $val2){
                    $id = $val2['id'];
                    $apiRes = pipedrive_api_request('PUT',$key.'/'.$id, $val2, $action);
                }
            }else{
                $id = $val['id'];
                foreach ($val as $fieldKey => $fieldValue) {
                    if (is_array($fieldValue)) {

                        $cleaned = array_filter($fieldValue, fn($v) => $v !== '0');

                        if (count($fieldValue) > 1) {
                        $val[$fieldKey] = empty($cleaned) ? null : implode(',', $cleaned);
                        } else {
                        $val[$fieldKey] = empty($cleaned) ? null : array_values($cleaned)[0];
                        }
                        
                    }
                }
                $apiRes = pipedrive_api_request('PUT',$key.'/'.$id, $val, $action);
            }
        }
            echo '<div class="pgfc-success-message notice notice-success">
               <p>'.__( 'Profile updated successfully!', 'pgfc' ).'</p>
            </div>';
    

    }else{
        return;
    }
}

function getPipedriveFileDownloadLink($fileID) {
    $action = 'getPipedriveFileDownloadLink';
    if (!$fileID) {
        return false;
    }
    $fileData = pipedrive_api_request('GET', "files/$fileID/download", [], $action);
    if (isset($fileData['data']['file_url'])) {
        return $fileData['data']['file_url'];
    }
    return false;
}
