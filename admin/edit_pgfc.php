<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$pgrcID = '';
if(!isset($_GET['pgfc_id']) || empty($_GET['pgfc_id'])){
    echo __('No pgfc found', 'pgfc');
    return;
}else{
    $pgrcID = sanitize_text_field( $_GET['pgfc_id'] );
}
if ( isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_communication']) 
&& isset($_POST['_wpnonce_add_pgfc']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_add_pgfc'])), 'add-pgfc-nonce-action') ) {
    $formID   = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : "";
    $form = GFAPI::get_form($formID);
    $formName = 'Form not found';
    if ($form && !is_wp_error($form)) {
        $formName = esc_html($form['title']);
    } 
    $mapping  = isset($_POST['mapping'])? sanitize_array_recursive($_POST['mapping']) : [];
    $pgfc_id = false;
    if (isset($_POST['_wpnonce_add_pgfc']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_add_pgfc'])), 'add-pgfc-nonce-action')){          
        $pgfcID = wp_update_post(array(
            'ID'           => $pgrcID,
            'post_title'   => $formName . ' (' . $formID . ')' ,
            'post_type'    => 'pgfc', 
            'post_status'  => 'publish',
            'meta_input'   => [
                'form_id' => $formID,
                'mapping'   => $mapping,
            ]
        ));
        if ($pgfcID) {
            echo '<div class="updated"><p>' . esc_html__('Pipedrive communication created successfully!', 'pgfc') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Error creating the pipedrive communication.', 'pgfc') . '</p></div>';
        }
    }elseif(isset($_POST['_wpnonce_add_pgfc'])){
        echo '<div class="error"><p>' . esc_html__('Security check failed.', 'pgfc') . '</p></div>';
    }       
}
$formID = get_post_meta($pgrcID, 'form_id', true);
$mapping = get_post_meta($pgrcID, 'mapping', true);
?>

<div class="pgfc-addpgfc">
    <form action="" method="post">
        <?php wp_nonce_field('add-pgfc-nonce-action', '_wpnonce_add_pgfc'); ?>
        <div class="pgfcFormInn">    
            <?php
                if(!empty(getGravityForms('forms'))){
                    ?>
                        <select name="form_id" required class="gravityForms onChangeFun" id="gravityForms" data-slug="gravityFormsFields">
                            <option value=""><?php _e('Select Form', 'pgfc');?></option>
                            <?php
                                foreach(getGravityForms('forms') as $form){
                                    echo '<option value="'.$form['id'].'"
                                    '.(((int)$formID == (int)$form['id'])?'selected':'').'
                                    >'.$form['name'].'</option>';
                                }
                            ?>
                        </select>

                    <?php
                }
            ?>
        </div>
        <div class="pgfcFormInn pgfc_itemsCon">
            <table>
                <tbody>
                    <?php
                        if(!empty($mapping) && is_array($mapping)):
                        foreach($mapping as $index => $map):
                            $fieldID = $map['field'];
                            $apiLabel = $map['apiLabel'];
                            $apiAttribute = $map['apiAttribute'];
                    ?>
                    <tr class="pgfcItem">
                        <td>
                            <a href="javascript:;" class="removeMapping">
                                <svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" viewBox="0 0 8 8" fill="none"><path d="M7.25 0.75L0.75 7.25M0.75 0.75L7.25 7.25" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </td>
                        <td class="gravityFormsFields">
                            <?php
                                if(!empty(getGravityForms('fields'))){
                                    foreach(getGravityForms('fields') as $savedFormID => $form){
                                        if(!empty($form) && (int) $formID ==  $savedFormID){
                                    ?>
                                        <select required name="mapping[<?php echo $index;?>][field]" id="gravityFormsFields_<?php echo $savedFormID;?>">
                                            <option value=""><?php _e('Select Field', 'pgfc');?></option>
                                            <?php
                                                foreach($form as $field){
                                                    echo '<option value="'.$field->id.'"
                                                        '.(( $fieldID == $field->id )?'selected':'').'
                                                        >'.$field['label'].'</option>';
                                                    if(allowSubFieldsType($field['type']) && isset($field['inputs']) && !empty($field['inputs']) && is_array($field['inputs'])){
                                                        foreach($field['inputs'] as $input){

                                                            if(!isset($input['isHidden']) || (isset($input['isHidden']) && !$input['isHidden'])){
                                                                echo '<option value="'.$input['id'].'"
                                                                
                                                                '.(( $fieldID == $input['id'])?'selected':'').'

                                                                >'.
                                                                $input['label'].'('.$field['label'].')</option>';
                                                            }
                                                        }
                                                    }
                                                }
                                            ?>
                                        </select>
                        
                                    <?php
                                        }
                                    }
                                }
                            ?>
                        </td>
                        <td>
                            <?php
                                if(!empty(getPipeDriveAPIEndPoint())){
                                    ?>
                                        <select required name="mapping[<?php echo $index;?>][apiLabel]" class="pipeDriveAPISelect onChangeFun" data-slug="pipeDriveAPIAttributes" id="pipeDriveAPI">
                                            <option value=""><?php _e('Select API', 'pgfc');?></option>
                                            <?php
                                                foreach(getPipeDriveAPIEndPoint() as $apiName){
                                                    echo '<option value="'.$apiName['singular_end_point'] .'"
                                                    '.(($apiName['singular_end_point'] == $apiLabel)?'selected':'').'
                                                    >'.$apiName['label'].'</option>';
                                                }
                                            ?>
                                        </select>
                                        <?php
                                        
                                            if($apiLabel == 'activity' && isset($map['apiLabelIndex'])){
                                                echo '<input type="number" name="mapping['.$index.'][apiLabelIndex]" required class="apiActivityIndex" value="'.$map['apiLabelIndex'].'" />';
                                            }
                                        ?>
                                    <?php
                                }
                            ?>
                        </td>
                        <td class="pipeDriveAPIAttributes">   
                           <?php
                                if(!empty(pipedriveGetVieldName())){
                                    $fields = pipedriveGetVieldName()[$apiLabel];
                                ?>
                                        <select required name="mapping[<?php echo $index;?>][apiAttribute]" class="apiAttributeSelect">
                                             <option value=""><?php _e('Select Attribute', 'pgfc');?></option>
                                             <?php
                                                 foreach($fields as $field){
                                                    $fieldKey = $field['key'];
                                                    $fieldName = $field['name'];
                                                    echo '<option value="'.$fieldKey.'" 
                                                    '.(($fieldKey == $apiAttribute)?'selected':'').'
                                                    >'.$fieldName.'</option>';
                                                 }
                                             ?>
                                         </select>
                        
                                     <?php
                                }
                            ?>  
                            <!-- <input type="text" name="mapping[<?php echo $index;?>][apiAttribute]" value="<?php echo $apiAttribute;?>" placeholder="Enter APIs Attribute name/key" /> -->
                        </td>
                    </tr>
                    <?php endforeach;
                    endif;?>
                </tbody>
            </table>
            <?php
                //echo '<pre>', print_r(getGravityForms('fields')[4]), '</pre>';
            ?>
            <div class="pgfc_addpgfcItemCon">
                <a href="javascript:;" class="pgfc_addItem"><?php esc_html_e('Add Item/Service', 'pgfc'); ?></a>
                <a href="javascript:;" class="pgfc_removepgfcItem"                 
                style="
                <?php if(count($mapping) <= 1){echo 'display:none';}?>                
                "><?php esc_html_e('Remove Item/Service', 'pgfc'); ?></a>
            </div>
        </div>

        <div class="pgfcFormInn">
            <input type="submit" value="<?php esc_attr_e('Publish', 'pgfc'); ?>" name="create_communication" class="button is-primary">
        </div>
    </form>
</div>
<div id="dynamicAllFields" style="display:none;" >
    <?php
        if(!empty(getGravityForms('fields'))){
            foreach(getGravityForms('fields') as $formID => $form){
                if(!empty($form)){
            ?>
                <select required name="mapping[0][field]" id="gravityFormsFields_<?php echo $formID;?>">
                    <option value=""><?php _e('Select Field', 'pgfc');?></option>
                    <?php
                        foreach($form as $field){
                            echo '<option value="'.$field->id.'">'.$field['label'].'</option>';
                            if(allowSubFieldsType($field['type']) && !empty($field['inputs']) && is_array($field['inputs'])){
                                foreach($field['inputs'] as $input){
                                    if(!$input['isHidden']){
                                        echo '<option value="'.$input['id'].'">'.$input['label'].'('.$field['label'].')</option>';
                                    }
                                }
                            }
                        }
                    ?>
                </select>

            <?php
                }
            }
        } 
        
        if(!empty(pipedriveGetVieldName())){
            foreach(pipedriveGetVieldName() as $key => $fields){
        ?>
                <select required name="mapping[0][apiAttribute]" class="apiAttributeSelect" id="pipeDriveAPIAttributes_<?php echo $key;?>">
                     <option value=""><?php _e('Select Attribute', 'pgfc');?></option>
                     <?php
                         foreach($fields as $field){
                            $fieldKey = $field['key'];
                            $fieldName = $field['name'];
                            echo '<option value="'.$fieldKey.'">'.$fieldName.'</option>';
                         }
                     ?>
                 </select>

             <?php
            }
        }
    ?>
</div>