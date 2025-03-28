<?php
function showOrganizations($userID) {
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
            <input type="text" name="search" value="' . esc_attr($searchTerm) . '" placeholder="Search Organizations">
            <button type="submit">Search</button>
          </form>';

    if (!empty($organizationsData)):
        echo '<h3>Organizations</h3>
              <table class="adminTable" border="1" style="width:1000px;">
                <thead>
                    <th style="width:50px"></th>
                    <th>Name</th>
                    <th style="width:100%">Address</th>
                    <th style="width:150px">Action</th>
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
                <td><a href="javascript:;" class="modify-organization" data-org-id="<?php echo $orgID;?>" data-person-id="<?php echo $personID;?>" >Modify</a></td>
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

add_action('wp_ajax_nopriv_modifyOrganization', 'modifyOrganization');
add_action('wp_ajax_modifyOrganization', 'modifyOrganization');
function modifyOrganization(){
    echo 'ajax working';
}


function register_hidden_organizations_page() {
    add_menu_page(
        'Manage Organizations',     // Page title
        'Manage Organizations',     // Menu title (Won't be shown)
        'manage_options',           // Capability
        'manage_organizations',     // Menu slug
        'render_organizations_page',// Callback function
        'dashicons-building',       // Icon (not needed since it's hidden)
        99                          // Position
    );

    // Hide it from the menu
    remove_menu_page('manage-organizations');
}
add_action('admin_menu', 'register_hidden_organizations_page');

function render_organizations_page() {
    echo '<div class="wrap">';
    echo '<h1>Manage Organizations</h1>';
    showOrganizations(get_current_user_id()); // Show organizations table with search
    echo '</div>';
}
