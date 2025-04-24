<?php

// displaying pipedrive data on profile page
function pipedriveDataShowProfile($user) {     
    $personID = get_user_meta($user->ID, 'pipedrive_person_id', true);
    if (!$personID) {
        return;
    }
    $manage_url = admin_url('admin.php?page=manage_organizations&user_id='.$user->ID);    
    echo '<p><a href="' . esc_url($manage_url) . '" class="button button-primary">' . __('Manage Organizations', 'pgfc') . '</a></p>';  
    echo showPipedriveData($user->ID);
}
add_action('show_user_profile', 'pipedriveDataShowProfile');
add_action('edit_user_profile', 'pipedriveDataShowProfile');
function updatePipedriveDataProfile($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    global $pipedrive_profile_updated;
    $pipedrive_profile_updated = true;
    updatePipeDriveData($_POST);

}
add_action('personal_options_update', 'updatePipedriveDataProfile');
add_action('edit_user_profile_update', 'updatePipedriveDataProfile');
// displaying pipedrive data on profile page

add_filter('gettext', 'custom_profile_updated_message', 20, 3);
function custom_profile_updated_message($translated_text, $text, $domain) {
    if (is_admin() && $text === 'Profile updated.') {
        // You can also add a check to make sure you're on the profile page:
        if (isset($_GET['user_id']) || strpos($_SERVER['REQUEST_URI'], 'profile.php') !== false) {
            $translated_text = __('Profile updated successfully', 'pgfc');
        }
    }
    return $translated_text;
}



add_shortcode('edit_pipedrive_data', 'editPipeDriveData');

function editPipeDriveData() {
    if (!is_user_logged_in()) {
        return custom_login_form();
    }
    $res = '';
    $user_id = get_current_user_id();
    if(isset($_GET['page-name']) && $_GET['page-name'] == 'manage_organizations'){
        ob_start();
        showOrganizations($user_id);
        $res .= ob_get_clean();
    }else{
        $existing_query = $_SERVER['QUERY_STRING'] ?? ''; // Get existing query string
        $new_query = empty($existing_query) ? 'page-name=manage_organizations' : $existing_query . '&page-name=manage_organizations';
        $manage_url = esc_url('?' . $new_query);
        $res .= '<div class="manageProfileBtnFront">
        
            <div class="form-wrap-left">
                <a href="' . $manage_url . '" class="button button-primary">' . __('Manage Organizations', 'pgfc') . '</a>
            </div>
            <div class="form-wrap-right">
                <div class="innerRight">
                    <a href="'.wp_logout_url(home_url()).'" class="button front-logout">
                        '.__('Logout', 'pgfc').'
                    </a>  
                </div>
            </div>
        </div>';
        if (isset($_POST['pipedrive'])) {
            $res .= updatePipeDriveData($_POST);
        }
        // Capture the echoed output instead of modifying showPipedriveData
        ob_start();
        showPipedriveData($user_id);
        $res .= ob_get_clean();
    }
    return $res;
}


