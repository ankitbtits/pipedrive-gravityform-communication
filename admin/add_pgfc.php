<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
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
        $pgfcID = wp_insert_post(array(
            'post_title'   => $formName . ' (' . $formID . ')' ,
            'post_type'    => 'pgfc', 
            'post_status'  => 'publish',
            'meta_input'   => [
                'form_id' => $formID,
                'mapping'   => $mapping,
                'pgfc_createddate'=>gmdate('Y-m-d h:i:s'),
            ]
        ));
        if ($pgfcID) {
            echo '<div class="updated"><p>' . esc_html__('Pipedrive communication created successfully!', PGFC_TEXT_DOMAIN) . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Error creating the pipedrive communication.', PGFC_TEXT_DOMAIN) . '</p></div>';
        }
    }elseif(isset($_POST['_wpnonce_add_pgfc'])){
        echo '<div class="error"><p>' . esc_html__('Security check failed.', PGFC_TEXT_DOMAIN) . '</p></div>';
    }       
}
?>

<div class="pgfc-addpgfc">
    <form action="" method="post">
        <?php wp_nonce_field('add-pgfc-nonce-action', '_wpnonce_add_pgfc'); ?>
        <div class="pgfcFormInn">    
            <?php
                if(!empty(getGravityForms('forms'))){
                    ?>
                        <select name="form_id" required class="gravityForms onChangeFun" id="gravityForms" data-slug="gravityFormsFields">
                            <option value=""><?php _e('Select Form', PGFC_TEXT_DOMAIN);?></option>
                            <?php
                                foreach(getGravityForms('forms') as $form){
                                    echo '<option value="'.$form['id'].'">'.$form['name'].'</option>';
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
                    <tr class="pgfcItem">
                        <td class="gravityFormsFields"><?php _e('Please select the form to display it\'s fields',PGFC_TEXT_DOMAIN);?></td>
                        <td>
                            <?php
                                if(!empty(getPipeDriveAPIEndPoint())){
                                    ?>
                                        <select required name="mapping[0][apiLabel]" class="pipeDriveAPISelect onChangeFun" data-slug="pipeDriveAPIAttributes" id="pipeDriveAPI">
                                            <option value=""><?php _e('Select API', PGFC_TEXT_DOMAIN);?></option>
                                            <?php
                                                foreach(getPipeDriveAPIEndPoint() as $apiName){
                                                    echo '<option value="'.$apiName['singular_end_point'] .'">'.$apiName['label'].'</option>';
                                                }
                                            ?>
                                        </select>

                                    <?php
                                }
                            ?>
                        </td>
                        <td class="pipeDriveAPIAttributes">
                            <!-- <input type="text" name="mapping[0][apiAttribute]" placeholder="Enter APIs Attribute name/key" /> -->
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="pgfc_addpgfcItemCon">
                <a href="javascript:;" class="pgfc_addItem"><?php esc_html_e('Add Item/Service', PGFC_TEXT_DOMAIN); ?></a>
                <a href="javascript:;" class="pgfc_removepgfcItem" style="display:none"><?php esc_html_e('Remove Item/Service', PGFC_TEXT_DOMAIN); ?></a>
            </div>
        </div>

        <div class="pgfcFormInn">
            <input type="submit" value="<?php esc_attr_e('Publish', PGFC_TEXT_DOMAIN); ?>" name="create_communication" class="button is-primary">
        </div>
    </form>
</div>

<div id="dynamicAllFields"  style="display:none;">
    <?php
        if(!empty(getGravityForms('fields'))){
            foreach(getGravityForms('fields') as $formID => $form){
                if(!empty($form)){
            ?>
                <select required name="mapping[0][field]" id="gravityFormsFields_<?php echo $formID;?>" style="display:block;">
                    <option value=""><?php _e('Select Field', PGFC_TEXT_DOMAIN);?></option>
                    <?php
                        foreach($form as $field){
                            echo '<option value="'.$field->id.'">'.$field['label'].'</option>';
                            if(allowSubFieldsType($field['type']) && !empty($field['inputs']) && is_array($field['inputs'])){
                                foreach($field['inputs'] as $input){
                                    $isHidden = $input['isHidden'] ?? false;
                                    if(!$isHidden){
                                        echo '<option value="'.$input['id'].'">'.$input['label'].'('.$field['label'].')</option>';
                                    }
                                }
                            }
                        }
                    ?>
                </select>

            <?php
                }else{
                    _e('Selected form does not have any field. Please change form or add fields.', PGFC_TEXT_DOMAIN);
                }
            }
        }

        if(!empty(pipedriveGetVieldName())){
            foreach(pipedriveGetVieldName() as $key => $fields){
        ?>
                <select required name="mapping[0][apiAttribute]" class="apiAttributeSelect" id="pipeDriveAPIAttributes_<?php echo $key;?>">
                     <option value=""><?php _e('Select Attribute', PGFC_TEXT_DOMAIN);?></option>
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