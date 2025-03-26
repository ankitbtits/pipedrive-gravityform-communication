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
        }
    }

    private function load_saved_options() {
        $this->pipeDriveApiToken = get_option('pipeDriveApiToken', '');
    }

    public function render() {
        $tabs = new PGFC_Admin_Tabs();
        echo $tabs->render();
        ?>
        <div class="wrap pgfc-settings">
            <h1 class="wp-heading-inline"><?php esc_html_e('pgfc', 'pgfc'); ?></h1>
            <div class="_CISettingIn">
                <form action="#" method="post" autocomplete="off">
                    <?php wp_nonce_field('pgfc-settings-nonce', '_wpnonce_pgfc_settings'); ?>
                    <table>
                            <th><?php esc_html_e('Pipedrive API Token:', 'pgfc'); ?></th>
                            <td><input type="password" name="pgfc_option[pipeDriveApiToken]" value="<?php echo esc_html($this->pipeDriveApiToken); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th></th>
                            <td><input name="pgfc_submit" class="button button-primary" type="submit" value="<?php esc_html_e('Save', 'pgfc'); ?>" /></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        <?php
    }
}
$settings_page = new PGFC_Settings_Page();
$settings_page->render();