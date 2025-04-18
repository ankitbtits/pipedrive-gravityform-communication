<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PGFC_Settings_Page {
    private $pipeDriveApiToken;
    public $pipeDriveIDError = false;
    public $stagesKey;
    public function __construct() {
        $this->stagesKey = 'pipedrive_stages';
        $this->handle_form_submission();
        $this->load_saved_options();
    }

    private function handle_form_submission() {
        if (
            isset($_POST['pgfc_submit']) &&
            isset($_POST['_wpnonce_pgfc_settings']) &&
            wp_verify_nonce(sanitize_text_field($_POST['_wpnonce_pgfc_settings']), 'pgfc-settings-nonce')
        ) {
            $pipeDriveApiToken = isset($_POST['pgfc_option']['pipeDriveApiToken'])
                ? sanitize_text_field($_POST['pgfc_option']['pipeDriveApiToken'])
                : '';
            update_option('pipeDriveApiToken', $pipeDriveApiToken);
            echo '<div class="updated"><p>' . esc_html__('Field saved successfully!', 'pgfc') . '</p></div>';
        }elseif(
            isset($_POST['pgfc_resyncCustomFields']) &&
            isset($_POST['_wpnonce_pgfc_settings']) &&
            wp_verify_nonce(sanitize_text_field($_POST['_wpnonce_pgfc_settings']), 'pgfc-settings-nonce')
        ){
            pipedriveStoreCustomFields();
            echo '<div class="updated"><p>' . esc_html__('Custom fields from pipedrive are synced successfully!', 'pgfc') . '</p></div>';
        }elseif(
            isset($_POST['pgfc_syncStages']) &&
            isset($_POST['_wpnonce_pgfc_settings']) &&
            wp_verify_nonce(sanitize_text_field($_POST['_wpnonce_pgfc_settings']), 'pgfc-settings-nonce')
        ){
            if(!isset($_POST['pipeDriveID']) || empty($_POST['pipeDriveID'])){
                $this->pipeDriveIDError = __('Please enter pipedrive ID to sync its stages', 'pgfc');
            }else{
                $action = 'Syncing stages';
                $syncStages = pipedrive_api_request('GET', 'stages', ['pipeline_id'=>$_POST['pipeDriveID']], $action);
                if(isset($syncStages['data'])){
                    $this->updateStages($_POST['pipeDriveID'], $syncStages['data']);
                }else{
                    $this->pipeDriveIDError = __('Stages could not be synced. Please check API Logs for the error.', 'pgfc');
                }
            }
        }
    }
    private function updateStages($pipeDriveID, $stages){
        if(is_array($stages) && !empty($stages)){
            $curentStages = get_option($this->stagesKey, true);
            if(!is_array($curentStages)){
                $curentStages = [];
            }
            $curentStages[$pipeDriveID] = $stages;
            update_option($this->stagesKey, $curentStages);
        }
    }
    private function load_saved_options() {
        $this->pipeDriveApiToken = get_option('pipeDriveApiToken', '');
        $this->pipedrive_custom_fields_last_updated = get_option('pipedrive_custom_fields_last_updated', '');
    }

    public function render() {
        $tabs = new PGFC_Admin_Tabs();
        $stages = get_option($this->stagesKey, true);
        echo $tabs->render();
        ?>
        <div class="wrap pgfc-settings">
            <form action="#" method="post" autocomplete="off">
                <h1 class="wp-heading-inline"><?php esc_html_e('Gravity Form Pipedrive Sync', 'pgfc'); ?></h1>
                <div class="_CISettingIn">
                    <?php wp_nonce_field('pgfc-settings-nonce', '_wpnonce_pgfc_settings'); ?>
                    <table>
                        <tr>
                            <th><?php esc_html_e('Pipedrive API Token:', 'pgfc'); ?></th>
                            <td><input type="text" name="pgfc_option[pipeDriveApiToken]" value="<?php echo esc_html($this->pipeDriveApiToken); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th></th>
                            <td><input name="pgfc_submit" class="button button-primary" type="submit" value="<?php esc_html_e('Save', 'pgfc'); ?>" /></td>
                        </tr>              
                        <tr>
                            <th><?php esc_html_e('Resync pipedrive all the custom fields:', 'pgfc'); ?></th>
                            <td>
                                <input name="pgfc_resyncCustomFields" class="button button-primary" type="submit" value="<?php esc_html_e('Resync fields', 'pgfc'); ?>" /><br>
                                <?php
                                    if(!empty($this->pipedrive_custom_fields_last_updated)){
                                        echo '<small>'.__('Fields were last synced on:', 'pgfc').' '.$this->pipedrive_custom_fields_last_updated.'</small>';
                                    }else{
                                        echo '<small>'.__('Fields have not been synced yet.', 'pgfc').'</small>';
                                    }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="_CISettingIn">
                    <h3><?php _e('PipeDrive Stages', 'pgfc');?> </h3>
                    <table>
                        <tr>
                            <td>
                                <span>
                                    <?php
                                        if($this->pipeDriveIDError){
                                            echo "<span class='pgfcErrorField'>".$this->pipeDriveIDError."</span><br>";
                                        }
                                    ?>
                                    <input name="pipeDriveID" type="number" class="regular-text" placeholder="Enter Pipedrive ID" />
                                </span>
                                <input name="pgfc_syncStages" class="button button-primary" type="submit" value="<?php esc_html_e('Sync stages', 'pgfc'); ?>" />
                            </td>
                        </tr>
                    </table>
                    <table class="adminTableStyle1">
                        <?php
                            if(is_array($stages) && !empty($stages)){
                                echo '<tr>
                                    <th>' . __('Stage Name', 'pgfc') . '</th>
                                    <th>' . __('Stage ID', 'pgfc') . '</th>
                                    <th>' . __('Pipeline Name', 'pgfc') . '</th>
                                    <th>' . __('Pipeline ID', 'pgfc') . '</th>
                                </tr>';
                                foreach($stages as $pipeDeriveKey => $stage){
                                    foreach($stage as $val){
                                        $stageName = $val['name'];
                                        $stageID = $val['id'];
                                        $pipelineName = $val['pipeline_name'];
                                        $pipelineID = $val['pipeline_id'];
                                        echo "<tr>
                                            <td>$stageName</td>
                                            <td>$stageID</td>
                                            <td>$pipelineName</td>
                                            <td>$pipelineID</td>
                                        </tr>
                                        ";
                                    }
                                }
                            }
                        ?>
                    </table>
                </div>
            </form>
            <div class="_CISettingIn">
                <h3><?php _e('General Info', 'pgfc');?></h3>
                <table>
                    <tr>
                        <th><?php _e('Edit profile Shortcode:', 'pgfc');?></th>
                        <td>
                            <?php 
                            printf(
                                __('Add <b>%s</b> shortcode to display the edit profile form anywhere on the frontend.<br><small>Note: Make sure the shortcode is not appending within any form tag.</small>', 'pgfc'),
                                '[edit_pipedrive_data]'
                            ); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Creat account field', 'pgfc');?></th>
                        <td>
                            <?php 
                                printf(
                                    __('1. Set the field value to <b>%s</b> if you want an account to be created when the checkbox is selected. <br> 2. Creating user accounts requires an email. The email will be taken from either <b>%s</b> field or an email field with <b>%s</b> as its "Admin Field Label". Ensure that at least one of these email fields exists in the form.', 'pgfc'),
                                    'createAccountWP',
                                    'Pipedrive → Persons → Email',
                                    'userEmail'
                                ); 
                                ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('For Privacy Policy', 'pgfc');?></th>
                        <td>
                        <?php _e('In Gravity Forms, either the field label or value must exactly match the corresponding Pipedrive label name.', 'pgfc');?>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Marketing Status', 'pgfc');?></th>
                        <td>
                        <?php _e('In Gravity Forms, either the label or value must match the Pipedrive label name. The option values for marketing status must exactly match those in Pipedrive.', 'pgfc');?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('For Country dropdown', 'pgfc');?></th>
                        <td>
                        <?php _e('The country names in the Gravity Forms dropdown must exactly match those in the Pipedrive country list.', 'pgfc');?>
                            
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Checkbox and Radio Fields:', 'pgfc');?></th>
                        <td>
                            <?php _e('The option values in checkbox and radio fields in Gravity Forms must exactly match the corresponding values in Pipedrive.', 'pgfc');?>
                        </td>
                    </tr>


                </table>
            <div>
        </div>
        <?php
    }
}
$settings_page = new PGFC_Settings_Page();
$settings_page->render();

