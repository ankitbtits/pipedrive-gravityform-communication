<?php

// displaying pipedrive data on profile page
function pipedriveDataShowProfile($user) {     
    $manage_url = admin_url('admin.php?page=manage_organizations');
    echo '<p><a href="' . esc_url($manage_url) . '" class="button button-primary">Manage Organizations</a></p>';  
    showPipedriveData($user->ID);
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
