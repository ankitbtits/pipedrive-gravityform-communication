<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PGFC_Settings_Page {
    private $pipeDriveApiToken;

    public function __construct() {
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
        }
    }

    private function load_saved_options() {
        $this->pipeDriveApiToken = get_option('pipeDriveApiToken', '');
        $this->pipedrive_custom_fields_last_updated = get_option('pipedrive_custom_fields_last_updated', '');
    }

    public function render() {
        $tabs = new PGFC_Admin_Tabs();
        echo $tabs->render();
        ?>
        <div class="wrap pgfc-settings">
            <h1 class="wp-heading-inline"><?php esc_html_e('Gravity Form Pipedrive Sync', 'pgfc'); ?></h1>
            <div class="_CISettingIn">
                <form action="#" method="post" autocomplete="off">
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
                </form>
            </div>

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