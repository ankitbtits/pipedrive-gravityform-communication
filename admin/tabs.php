<?php
class PGFC_Admin_Tabs {
    private $items;

    public function __construct() {
        $this->items = [
            [
                'label' => 'All pgfcs',
                'page'  => 'pgfc',
                'tab'   => ''
            ],
            [
                'label' => 'Settings',
                'page'  => 'pgfc',
                'tab'   => 'settings'
            ],
            [
                'label' => 'API Logs',
                'page'  => 'pgfc',
                'tab'   => 'api-logs'
            ],
        ];
    }

    public function render() {
        $res = '<div class="pgfc_admin_tabs"><ul>';

        foreach ($this->items as $item) {
            $active = '';

            // Check if the 'page' parameter matches
            if (isset($_GET['page']) && $_GET['page'] === $item['page']) {
                // Check if the 'tab' parameter matches or if it's absent when it should be
                if ((!isset($_GET['tab']) && $item['tab'] === '') ||
                    (isset($_GET['tab']) && $_GET['tab'] === $item['tab'])) {
                    $active = 'class="active"';
                }
            }

            // Escape dynamic data
            $escaped_label = esc_html($item['label']);
            $escaped_page  = esc_attr($item['page']);
            $escaped_tab   = esc_attr($item['tab']);

            // Construct the URL
            $url = admin_url('admin.php?page=' . $escaped_page);
            if ($escaped_tab !== '') {
                $url .= '&tab=' . $escaped_tab;
            }

            $res .= '
            <li>
                <a href="' . esc_url($url) . '" ' . $active . '>' . $escaped_label . '</a>
            </li>';
        }

        $res .= '</ul></div>';

        return $res;
    }
}

?>
