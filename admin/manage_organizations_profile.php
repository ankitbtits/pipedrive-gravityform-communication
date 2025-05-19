<?php
function showOrganizations($userID) {
    $action = 'showOrganization';
    $message = __('Please search organizations.', PGFC_TEXT_DOMAIN);
    if(isset($_GET['user_id']) && !empty($_GET['user_id']))
    {
        $userID = $_GET['user_id'];
    }
    $personID = get_user_meta($userID, 'pipedrive_person_id', true);
    if (!$personID) {
        return;
    }

    // Get search query & pagination parameters
    $searchTerm = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0; // Pagination
    $limit = 20; // Change limit as needed

    // API parameters
    $params = ['limit' => $limit, 'start' => $start];
    $isSearch = !empty($searchTerm);

    if ($isSearch) {
        $message = __('No organizations found.', PGFC_TEXT_DOMAIN);
        // Search API uses "next_start" for pagination
        $params['term'] = $searchTerm;
        $params['fields'] = 'name';
        $response = pipedrive_api_request('GET', 'organizations/search', $params, $action);
        $organizationsData = array_column($response['data']['items'] ?? [], 'item'); // Extract organizations
        
        
    } else {
        // Normal API uses "start" for pagination
    //     $response = pipedrive_api_request('GET', 'organizations', $params, $action);
    //    $organizationsData = $response['data'] ?? [];
            
    }

    // Get pagination data
    $pagination = $response['additional_data']['pagination'] ?? [];
    $nextStart = $pagination['more_items_in_collection'] ? $pagination['next_start'] : null;
    $prevStart = max(0, $start - $limit);

    // Display search form
    echo '<form method="GET" action="">
        ';
     
            echo ' <div  class="_backBtn">
                <a onclick="window.history.back();" class="button" style="text-decoration:none;">'.__('< Torna alla pagina precedente', PGFC_TEXT_DOMAIN).'</a>
            </div>';
           
           
            echo '<div class="frontorgmang"><div class="form-wrap-front">
                <input type="hidden" name="page" value="manage_organizations">
                <input type="hidden" name="page-name" value="manage_organizations">
                '.
                ((isset($_GET['user_id']) && !empty($_GET['user_id']))?
                '<input type="hidden" name="user_id" value="'.$_GET['user_id'].'">'
                :'')
                .'

                <input type="text" name="search" value="' . esc_attr($searchTerm) . '" placeholder="' . __('Search Organizations', PGFC_TEXT_DOMAIN) . '">
                <button type="submit">' . __('Search', PGFC_TEXT_DOMAIN) . '</button>
            </div>
            <div class="form-wrap-right">
                <div class="innerRight">
                    <a href="'.wp_logout_url(home_url()).'" class="button front-logout">
                          '.__('Logout', PGFC_TEXT_DOMAIN).'
                    </a>  
                </div>
            </div></div>
          </form>';

    if (!empty($organizationsData)):
                $profile_url = admin_url('profile.php'); // You can also use: admin_url("user-edit.php?user_id=$user_id")
                echo '<h3>' . __('Organizations', PGFC_TEXT_DOMAIN) . '</h3>
              <table class="adminTable adminTableFront" border="1" style="width:1000px;">
                <thead>
                    <th style="width:25px"></th>
                    <th style="width:25%">' . __('Name', PGFC_TEXT_DOMAIN) . '</th>
                    <th style="width:60%">' . __('Address', PGFC_TEXT_DOMAIN) . '</th>
                    <th style="width:15%">' . __('Action', PGFC_TEXT_DOMAIN) . '<br><small>' . __('Check if user exists in the organization or not.', PGFC_TEXT_DOMAIN) . '</small></th>
                </thead>';
        $serNo = 0;
        if(isset($_GET['start']) && !empty($_GET['start'])){
            $serNo = (int) $_GET['start'];
        }
        foreach ($organizationsData as $key => $org):
            $serNo++;
            $name = $org['name'];
            $name = $org['name'];
            $orgID = $org['id'];
            $address = $org['address'] ?? 'N/A';
            ?>
            <tr>
                <td><?php echo $serNo; ?></td>
                <td><?php echo esc_html($name); ?></td>
                <td><?php echo esc_html($address); ?></td>
                <td>
                    <a href="javascript:;" class="modify-organization" data-org-id="<?php echo $orgID;?>" data-person-id="<?php echo $personID;?>" ><?php echo __('Check status', PGFC_TEXT_DOMAIN); ?></a>
                </td>
            </tr>
            <?php
        endforeach;

        echo '</table>';

        // Pagination Links
        echo '<div class="pagination paginationfront">';
        if ($start > 0) {
            echo '<a href="?page=manage_organizations&page-name=manage_organizations&search=' . urlencode($searchTerm) . '&start=' . $prevStart . '">« Previous</a>';
        }
        if (!empty($nextStart)) {
            echo '<a href="?page=manage_organizations&page-name=manage_organizations&search=' . urlencode($searchTerm) . '&start=' . $nextStart . '">Next »</a>';
        }
        echo '</div>';

    else:
        echo "<p>".$message."</p>";
    endif;
}

function register_hidden_organizations_page() {
    add_menu_page(
        __('Manage Organizations', PGFC_TEXT_DOMAIN), // Page title
        __('Manage Organizations', PGFC_TEXT_DOMAIN), // Menu title (won't be shown)
        'read',                     // Capability
        'manage_organizations',     // Menu slug
        'render_organizations_page',// Callback function
        'dashicons-building',       // Icon (not needed since it's hidden)
        99                          // Position
    );
}
add_action('admin_menu', 'register_hidden_organizations_page');
// Remove the menu page so it doesn't show in WP Admin
function hide_manage_organizations_menu() {
    remove_menu_page('manage_organizations');
}
add_action('admin_menu', 'hide_manage_organizations_menu', 999);

function render_organizations_page() {
    echo '<div class="wrap">';
    echo '<h1>' . __('Manage Organizations', PGFC_TEXT_DOMAIN) . '</h1>';
        showOrganizations(get_current_user_id()); // Show organizations table with search
    echo '</div>';
}


add_action('wp_ajax_nopriv_modifyOrganization', 'modifyOrganization');
add_action('wp_ajax_modifyOrganization', 'modifyOrganization');

function modifyOrganization() {
    $action = 'modifyOrganization';
    // Validate input
    if (!isset($_POST['org_id'], $_POST['person_id'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
    }

    $orgID = sanitize_text_field($_POST['org_id']);
    $personID = sanitize_text_field($_POST['person_id']);

    // Fetch all persons in the given organization
    $endpoint = "organizations/{$orgID}/persons";
    $response = pipedrive_api_request('GET', $endpoint, [], $action);

    if (!$response || empty($response['data'])) {
        wp_send_json_success([
            'status' => 'not_in_org', 
            'message' => __('User is not in this organization.', PGFC_TEXT_DOMAIN)
        ]);
    }

    // Check if the person is already associated with this organization
    $personExists = false;
    foreach ($response['data'] as $person) {
        if ($person['id'] == $personID) {
            $personExists = true;
            break;
        }
    }

    if ($personExists) {
        wp_send_json_success([
            'status'  => 'exists',
            'message' => __('User is already in this organization.', PGFC_TEXT_DOMAIN),
        ]);
    } else {
        wp_send_json_success([
            'status'  => 'not_in_org',
            'message' => __('User is not in this organization.', PGFC_TEXT_DOMAIN),
        ]);
    }
}

add_action('wp_ajax_nopriv_modifyPersonOrganization', 'modifyPersonOrganization');
add_action('wp_ajax_modifyPersonOrganization', 'modifyPersonOrganization');

function modifyPersonOrganization() {
    $action = 'modifyPersonOrganization';
    // Validate input
    if (!isset($_POST['org_id'], $_POST['person_id'], $_POST['modify_action'])) {
        wp_send_json_error([
            'message' => __('Invalid request.', PGFC_TEXT_DOMAIN),
        ]);
    }

    $orgID = sanitize_text_field($_POST['org_id']);
    $personID = sanitize_text_field($_POST['person_id']);
    $modifyAction = sanitize_text_field($_POST['modify_action']);

    if (!in_array($modifyAction, ['add', 'remove'])) {
        wp_send_json_error([
            'message' => __('Invalid action specified.', PGFC_TEXT_DOMAIN),
        ]);
    }

    // **Fetch all persons in this organization**
    $orgPersonsEndpoint = "organizations/{$orgID}/persons";
    $orgPersonsResponse = pipedrive_api_request('GET', $orgPersonsEndpoint, [], $action);

    $orgPersons = $orgPersonsResponse['data'] ?? [];

    // Check if the person is already in the organization
    $isPersonInOrg = false;
    foreach ($orgPersons as $person) {
        if ($person['id'] == $personID) {
            $isPersonInOrg = true;
            break;
        }
    }

    if ($modifyAction === 'add') {
        if ($isPersonInOrg) {
            wp_send_json_success([
                'message' => __('User is already in this organization.', PGFC_TEXT_DOMAIN),
            ]);
        }

        // **Associate person with the new organization**
        $updateData = ['org_id' => $orgID];
        $updateResponse = pipedrive_api_request('PUT', "persons/{$personID}", $updateData, $action);

        if (!$updateResponse || empty($updateResponse['data'])) {
            wp_send_json_error([
                'message' => __('Failed to add user to organization.', PGFC_TEXT_DOMAIN),
            ]);
        }
        
        wp_send_json_success([
            'message' => __('User successfully added to the organization.', PGFC_TEXT_DOMAIN),
            'org_id' => $orgID,
        ]);
    }

    if ($modifyAction === 'remove') {
        if (!$isPersonInOrg) {
            wp_send_json_success([
                'message' => __('User is not part of this organization.', PGFC_TEXT_DOMAIN),
            ]);
        }
    
        // **Remove person from the organization (set org_id to NULL)**
        $updateData = ['org_id' => null];
        $updateResponse = pipedrive_api_request('PUT', "persons/{$personID}", $updateData, $action);
    
        if (!$updateResponse || empty($updateResponse['data'])) {
            wp_send_json_error([
                'message' => __('Failed to remove user from organization.', PGFC_TEXT_DOMAIN),
            ]);
        }
    
        wp_send_json_success([
            'message' => __('User successfully removed from the organization.', PGFC_TEXT_DOMAIN),
            'org_id' => null,
        ]);
    }
    
}