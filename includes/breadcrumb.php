<?php
/**
 * Generates and displays a breadcrumb navigation bar.
 *
 * @param array $crumbs An associative array where the key is the URL and the value is the text.
 * The last item in the array is considered the active page.
 */
function display_breadcrumbs($crumbs) {
    echo '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $last_key = array_key_last($crumbs);
    foreach ($crumbs as $url => $label) {
        if ($url === $last_key) {
            // Last item is the active page
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($label) . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></li>';
        }
    }
    echo '</ol></nav>';
}
?>