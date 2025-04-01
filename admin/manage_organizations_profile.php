<?php
function showOrganizations($userID) {
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
        // Search API uses "next_start" for pagination
        $params['term'] = $searchTerm;
        $params['fields'] = 'name';
        $response = pipedrive_api_request('GET', 'organizations/search', $params);
        $organizationsData = array_column($response['data']['items'] ?? [], 'item'); // Extract organizations
    } else {
        // Normal API uses "start" for pagination
        $response = pipedrive_api_request('GET', 'organizations', $params);
        $organizationsData = $response['data'] ?? [];
    }

    // Get pagination data
    $pagination = $response['additional_data']['pagination'] ?? [];
    $nextStart = $pagination['more_items_in_collection'] ? $pagination['next_start'] : null;
    $prevStart = max(0, $start - $limit);

    // Display search form
    echo '<form method="GET" action="">
            <input type="hidden" name="page" value="manage_organizations">
            '.
            ((isset($_GET['user_id']) && !empty($_GET['user_id']))?
            '<input type="hidden" name="user_id" value="'.$_GET['user_id'].'">'
            :'')
            .'
            
            <input type="text" name="search" value="' . esc_attr($searchTerm) . '" placeholder="Search Organizations">
            <button type="submit">Search</button>
          </form>';

    if (!empty($organizationsData)):
        echo '<h3>Organizations</h3>
              <table class="adminTable" border="1" style="width:1000px;">
                <thead>
                    <th style="width:25px"></th>
                    <th style="width:25%">Name</th>
                    <th style="width:60%">Address</th>
                    <th style="width:15%">Action<br><small>Check if user exist in the organization or not.</small></th>
                </thead>';

        foreach ($organizationsData as $key => $org):
            $name = $org['name'];
            $name = $org['name'];
            $orgID = $org['id'];
            $address = $org['address'] ?? 'N/A';
            ?>
            <tr>
                <td><?php echo $key + 1; ?></td>
                <td><?php echo esc_html($name); ?></td>
                <td><?php echo esc_html($address); ?></td>
                <td>
                    <a href="javascript:;" class="modify-organization" data-org-id="<?php echo $orgID;?>" data-person-id="<?php echo $personID;?>" >Check status</a>
                </td>
            </tr>
            <?php
        endforeach;

        echo '</table>';

        // Pagination Links
        echo '<div class="pagination">';
        if ($start > 0) {
            echo '<a href="?page=manage_organizations&search=' . urlencode($searchTerm) . '&start=' . $prevStart . '">« Previous</a>';
        }
        if (!empty($nextStart)) {
            echo '<a href="?page=manage_organizations&search=' . urlencode($searchTerm) . '&start=' . $nextStart . '">Next »</a>';
        }
        echo '</div>';

    else:
        echo "<p>No organizations found.</p>";
    endif;
}

function register_hidden_organizations_page() {
    add_menu_page(
        'Manage Organizations',     // Page title
        'Manage Organizations',     // Menu title (Won't be shown)
        'read',           // Capability
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
    echo '<h1>Manage Organizations</h1>';
        showOrganizations(get_current_user_id()); // Show organizations table with search
    echo '</div>';
}


add_action('wp_ajax_nopriv_modifyOrganization', 'modifyOrganization');
add_action('wp_ajax_modifyOrganization', 'modifyOrganization');

function modifyOrganization() {
    // Validate input
    if (!isset($_POST['org_id'], $_POST['person_id'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
    }

    $orgID = sanitize_text_field($_POST['org_id']);
    $personID = sanitize_text_field($_POST['person_id']);

    // Fetch all persons in the given organization
    $endpoint = "organizations/{$orgID}/persons";
    $response = pipedrive_api_request('GET', $endpoint);

    if (!$response || empty($response['data'])) {
        wp_send_json_success(['status' => 'not_in_org', 'message' => 'User is not in this organization.']);
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
            'message' => 'User is already in this organization.',
        ]);
    } else {
        wp_send_json_success([
            'status'  => 'not_in_org',
            'message' => 'User is not in this organization.',
        ]);
    }
}

add_action('wp_ajax_nopriv_modifyPersonOrganization', 'modifyPersonOrganization');
add_action('wp_ajax_modifyPersonOrganization', 'modifyPersonOrganization');

function modifyPersonOrganization() {
    // Validate input
    if (!isset($_POST['org_id'], $_POST['person_id'], $_POST['modify_action'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
    }

    $orgID = sanitize_text_field($_POST['org_id']);
    $personID = sanitize_text_field($_POST['person_id']);
    $modifyAction = sanitize_text_field($_POST['modify_action']);

    if (!in_array($modifyAction, ['add', 'remove'])) {
        wp_send_json_error(['message' => 'Invalid action specified.']);
    }

    // **Fetch all persons in this organization**
    $orgPersonsEndpoint = "organizations/{$orgID}/persons";
    $orgPersonsResponse = pipedrive_api_request('GET', $orgPersonsEndpoint);

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
            wp_send_json_success(['message' => 'User is already in this organization.']);
        }

        // **Associate person with the new organization**
        $updateData = ['org_id' => $orgID];
        $updateResponse = pipedrive_api_request('PUT', "persons/{$personID}", $updateData);

        if (!$updateResponse || empty($updateResponse['data'])) {
            wp_send_json_error(['message' => 'Failed to add user to organization.']);
        }

        wp_send_json_success([
            'message' => 'User successfully added to the organization.',
            'org_id' => $orgID
        ]);
    }

    if ($modifyAction === 'remove') {
        if (!$isPersonInOrg) {
            wp_send_json_success(['message' => 'User is not part of this organization.']);
        }

        // **Remove person from the organization (set org_id to NULL)**
        $updateData = ['org_id' => null];
        $updateResponse = pipedrive_api_request('PUT', "persons/{$personID}", $updateData);

        if (!$updateResponse || empty($updateResponse['data'])) {
            wp_send_json_error(['message' => 'Failed to remove user from organization.']);
        }

        wp_send_json_success([
            'message' => 'User successfully removed from the organization.',
            'org_id' => null
        ]);
    }
}