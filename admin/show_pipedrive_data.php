<?php

// displaying pipedrive data on profile page
function pipedriveDataShowProfile($user) {     
    $manage_url = admin_url('admin.php?page=manage_organizations&user_id='.$user->ID);
    echo '<p><a href="' . esc_url($manage_url) . '" class="button button-primary">Manage Organizations</a></p>';  
    echo showPipedriveData($user->ID);
}
add_action('show_user_profile', 'pipedriveDataShowProfile');
add_action('edit_user_profile', 'pipedriveDataShowProfile');
function updatePipedriveDataProfile($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    updatePipeDriveData($_POST);
}
add_action('personal_options_update', 'updatePipedriveDataProfile');
add_action('edit_user_profile_update', 'updatePipedriveDataProfile');
// displaying pipedrive data on profile page


add_shortcode('edit_pipedrive_data', 'editPipeDriveData');

function editPipeDriveData() {
    if (!is_user_logged_in()) {
        return custom_login_form();
    }

    $user_id = get_current_user_id();
    $res = '';

    if (isset($_POST['pipedrive'])) {
        $res .= updatePipeDriveData($_POST);
    }

    // Capture the echoed output instead of modifying showPipedriveData
    ob_start();
    showPipedriveData($user_id);
    $res .= ob_get_clean();

    return $res;
}


