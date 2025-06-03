<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PGFC_Settings_Page {
    private $pipeDriveApiToken;
    public $pipeDriveIDError = false;
    public $stagesKey;
    public $contact_support_page;
    public $login_page;
    public $pipedrive_custom_fields_last_updated;
    
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
            $contact_support_page   = isset($_POST['pgfc_option']['contact_support_page'])? sanitize_text_field($_POST['pgfc_option']['contact_support_page']): '';
            $login_page             = isset($_POST['pgfc_option']['login_page'])? sanitize_text_field($_POST['pgfc_option']['login_page']): '';

           // echo '<pre>'.print_r( $_POST['pgfc_option'] , true).'</pre>';
            update_option('pipeDriveApiToken', $pipeDriveApiToken);
            update_option('contact_support_page', $contact_support_page);
            update_option('login_page', $login_page);




            echo '<div class="updated"><p>' . esc_html__('Field saved successfully!', PGFC_TEXT_DOMAIN) . '</p></div>';
        }elseif(
            isset($_POST['pgfc_resyncCustomFields']) &&
            isset($_POST['_wpnonce_pgfc_settings']) &&
            wp_verify_nonce(sanitize_text_field($_POST['_wpnonce_pgfc_settings']), 'pgfc-settings-nonce')
        ){
            pipedriveStoreCustomFields();
            echo '<div class="updated"><p>' . esc_html__('Custom fields from pipedrive are synced successfully!', PGFC_TEXT_DOMAIN) . '</p></div>';
        }elseif(
            isset($_POST['pgfc_syncStages']) &&
            isset($_POST['_wpnonce_pgfc_settings']) &&
            wp_verify_nonce(sanitize_text_field($_POST['_wpnonce_pgfc_settings']), 'pgfc-settings-nonce')
        ){
            if(!isset($_POST['pipeDriveID']) || empty($_POST['pipeDriveID'])){
                $this->pipeDriveIDError = __('Please enter pipedrive ID to sync its stages', PGFC_TEXT_DOMAIN);
            }else{
                $action = 'Syncing stages';
                $syncStages = pipedrive_api_request('GET', 'stages', ['pipeline_id'=>$_POST['pipeDriveID']], $action);
                if(isset($syncStages['data'])){
                    $this->updateStages($_POST['pipeDriveID'], $syncStages['data']);
                }else{
                    $this->pipeDriveIDError = __('Stages could not be synced. Please check API Logs for the error.', PGFC_TEXT_DOMAIN);
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
        $this->contact_support_page = get_option('contact_support_page', '');
        $this->login_page = get_option('login_page', '');
        $this->pipedrive_custom_fields_last_updated = get_option('pipedrive_custom_fields_last_updated', '');
    }

    public function render() {
        $tabs = new PGFC_Admin_Tabs();
        $stages = get_option($this->stagesKey, true);
        echo $tabs->render();
        ?>
        <div class="wrap pgfc-settings">
            <form action="#" method="post" autocomplete="off">
                <h1 class="wp-heading-inline"><?php esc_html_e('Gravity Form Pipedrive Sync', PGFC_TEXT_DOMAIN); ?></h1>
                <div class="_CISettingIn">
                    <?php wp_nonce_field('pgfc-settings-nonce', '_wpnonce_pgfc_settings'); ?>
                    <table>
                        <tr>
                            <th><?php esc_html_e('Pipedrive API Token:', PGFC_TEXT_DOMAIN); ?></th>
                            <td><input type="text" name="pgfc_option[pipeDriveApiToken]" value="<?php echo esc_html($this->pipeDriveApiToken); ?>" class="regular-text" /></td>
                        </tr>
             
                        
                        <tr>
                            <th><?php esc_html_e('Contact Support Page:', PGFC_TEXT_DOMAIN); ?></th>
                            <td>
                                <?php
                                    // echo '<pre>****'.print_r($this->contact_support_page , true).'</pre>';
                                    wp_dropdown_pages(array(
                                        'name'              => 'pgfc_option[contact_support_page]',
                                        'selected'          => isset($this->contact_support_page) ? $this->contact_support_page : '',
                                        'show_option_none'  => __('-- Select a page --', PGFC_TEXT_DOMAIN),
                                        'option_none_value' => '',
                                    ));
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Dashboard Page:', PGFC_TEXT_DOMAIN); ?></th>
                            <td>
                                <?php
                                    wp_dropdown_pages(array(
                                        'name'              => 'pgfc_option[login_page]',
                                        'selected'          => isset($this->login_page) ? $this->login_page : '',
                                        'show_option_none'  => __('-- Select a page --', PGFC_TEXT_DOMAIN),
                                        'option_none_value' => '',
                                    ));
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td><input name="pgfc_submit" class="button button-primary" type="submit" value="<?php esc_html_e('Save', PGFC_TEXT_DOMAIN); ?>" /></td>
                        </tr>   
                        <tr>
                            <th><?php esc_html_e('Resync pipedrive all the custom fields:', PGFC_TEXT_DOMAIN); ?></th>
                            <td>
                                <input name="pgfc_resyncCustomFields" class="button button-primary" type="submit" value="<?php esc_html_e('Resync fields', PGFC_TEXT_DOMAIN); ?>" /><br>
                                <?php
                                    if(!empty($this->pipedrive_custom_fields_last_updated)){
                                        echo '<small>'.__('Fields were last synced on:', PGFC_TEXT_DOMAIN).' '.$this->pipedrive_custom_fields_last_updated.'</small>';
                                    }else{
                                        echo '<small>'.__('Fields have not been synced yet.', PGFC_TEXT_DOMAIN).'</small>';
                                    }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="_CISettingIn">
                    <h3><?php _e('PipeDrive Stages', PGFC_TEXT_DOMAIN);?> </h3>
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
                                <input name="pgfc_syncStages" class="button button-primary" type="submit" value="<?php esc_html_e('Sync stages', PGFC_TEXT_DOMAIN); ?>" />
                            </td>
                        </tr>
                    </table>
                    <table class="adminTableStyle1">
                        <?php
                            if(is_array($stages) && !empty($stages)){
                                echo '<tr>
                                    <th>' . __('Stage Name', PGFC_TEXT_DOMAIN) . '</th>
                                    <th>' . __('Stage ID', PGFC_TEXT_DOMAIN) . '</th>
                                    <th>' . __('Pipeline Name', PGFC_TEXT_DOMAIN) . '</th>
                                    <th>' . __('Pipeline ID', PGFC_TEXT_DOMAIN) . '</th>
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
                <h3><?php _e('General Info', PGFC_TEXT_DOMAIN);?></h3>
                <table>
                    <tr>
                        <th><?php _e('Creat account field', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                            <?php 
                                printf(
                                    __('1. Set the field value to <b>%s</b> if you want an account to be created when the checkbox is selected. <br> 2. Creating user accounts requires an email. The email will be taken from either <b>%s</b> field or an email field with <b>%s</b> as its "Field Class". Ensure that at least one of these email fields exists in the form.', PGFC_TEXT_DOMAIN),
                                    'createAccountWP',
                                    'Pipedrive → Persons → Email',
                                    'userEmail'
                                ); 
                                ?>
                        </td>

                    </tr>
                    <tr>
                        <th><?php _e('For Privacy Policy', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                        <?php _e('In Gravity Forms, either the field label or value must exactly match the corresponding Pipedrive label name.', PGFC_TEXT_DOMAIN);?>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Marketing Status', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                        <?php _e('In Gravity Forms, either the label or value must match the Pipedrive label name. The option values for marketing status must exactly match those in Pipedrive.', PGFC_TEXT_DOMAIN);?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('For Country dropdown', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                        <?php _e('The country names in the Gravity Forms dropdown must exactly match those in the Pipedrive country list.', PGFC_TEXT_DOMAIN);?>
                            
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Checkbox and Radio Fields:', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                            <?php _e('The option values in checkbox and radio fields in Gravity Forms must exactly match the corresponding values in Pipedrive.', PGFC_TEXT_DOMAIN);?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Edit profile Shortcode:', PGFC_TEXT_DOMAIN);?></th>
                        <td>
                            <?php 
                            printf(
                                __('Add <b>%s</b> shortcode to display the edit profile form anywhere on the frontend.<br><small>Note: Make sure the shortcode is not appending within any form tag.</small>', PGFC_TEXT_DOMAIN),
                                '[edit_pipedrive_data]'
                            ); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Organization Fields Note:', PGFC_TEXT_DOMAIN); ?></th>
                        <td>
                            <?php 
                            _e('To display organization options to the user, please ensure that the organization field has the class "organizationName".', PGFC_TEXT_DOMAIN);
                            ?>
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

