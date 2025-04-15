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
                $syncStages = pipedrive_api_request('GET', 'stages', ['pipeline_id'=>$_POST['pipeDriveID']]);
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
                    <h3>PipeDrive Stages </h3>
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
                                    <th>Stage Name</th>
                                    <th>Stage ID</th>
                                    <th>Pipeline name</th>
                                    <th>Pipeline ID</th>
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
                <h3>General Info</h3>
                <table>
                    <tr>
                        <th>Edit profile Shortcode:</th>
                        <td>
                            Add <b>[edit_pipedrive_data]</b> shortcode to display the edit profile form anywhere on frontend.<br>
                            <small>Note: Make sure the shortcode is not appending within any form tag.</small>    
                        </td>
                    </tr>
                    <tr>
                        <th>Creat account field</th>
                        <td>
                            1. Set the field value to <b>"createAccountWP"</b> if you want an account to be created when the checkbox is selected. <br>
                            2. Creating user accounts requires an email. The email will be taken from either <b>Pipedrive → Persons → Email</b> field or an email field with <b>"userEmail"</b> as its value. Ensure that at least one of these email fields exists in the form.
                        </td>
                    </tr>
                    <tr>
                        <th>For Privacy Policy</th>
                        <td>
                        In Gravity Forms, either the field label or value must exactly match the corresponding Pipedrive label name.
                        </td>
                    </tr>
                    <tr>
                        <th>Marketing Status</th>
                        <td>
                        In Gravity Forms, either the label or value must match the Pipedrive label name. The option values for marketing status must exactly match those in Pipedrive.
                        </td>
                    </tr>
                    <tr>
                        <th>For Country dropdown</th>
                        <td>
                            The country names in the Gravity Forms dropdown must exactly match those in the Pipedrive country list.
                        </td>
                    </tr>
                    <tr>
                        <th>Checkbox and Radio Fields:</th>
                        <td>
                            The option values in checkbox and radio fields in Gravity Forms must exactly match the corresponding values in Pipedrive.
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

