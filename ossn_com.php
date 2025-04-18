<?php
/**
 * GroupSlugRouter Component
 * Author: Eric Redegeld
 * Friendly URLs for groups via slug metadata (no DB schema changes)
 */

define('__GROUPSLUGROUTER__', ossn_route()->com . 'GroupSlugRouter/');
require_once __GROUPSLUGROUTER__ . 'helpers/slug.php';

/**
 * Init the component
 */
function com_GroupSlugRouter_init() {
    ossn_register_page('g', 'groupslugrouter_vanity_handler');
    ossn_register_page('slugdebug', 'groupslugrouter_debug_slug');

    // Hook after a group is added
    ossn_register_callback('group', 'add', 'groupslugrouter_on_group_added');
}
ossn_register_callback('ossn', 'init', 'com_GroupSlugRouter_init');

/**
 * Called after a group is added
 */
function groupslugrouter_on_group_added($event, $type, $params) {
    if (!isset($params['group_guid'])) {
        error_log("[SLUG] ❌ group:add callback zonder group_guid");
        return;
    }

    $group = ossn_get_group_by_guid($params['group_guid']);
    if (!$group) {
        error_log("[SLUG] ❌ groep niet gevonden bij group:add callback");
        return;
    }

    groupslugrouter_generate_slug($group);
}

/**
 * Handles vanity URLs like /g/my-slug
 */
function groupslugrouter_vanity_handler($pages) {
    if (empty($pages[0])) {
        ossn_error_page();
        return;
    }

    $slug = $pages[0];
    error_log("[SLUG] 🌐 Opgevraagd: {$slug}");

    $group = groupslugrouter_get_group_by_slug($slug);
    if ($group) {
        redirect("group/{$group->owner_guid}");
    } else {
        ossn_error_page();
    }
}

/**
 * Admin debug tool to test slugs
 */
function groupslugrouter_debug_slug($pages) {
    if (!ossn_isAdminLoggedin()) {
        ossn_error_page();
        return;
    }

    $output = '<div class="ossn-page-contents">';
    $output .= '<h2>Slug Debug Tool</h2>';
    $output .= '<form method="GET"><input name="s" value="' . htmlentities($_GET['s'] ?? '') . '" />';
    $output .= '<button type="submit">Zoek</button></form>';

    if (isset($_GET['s'])) {
        $group = groupslugrouter_get_group_by_slug($_GET['s']);
        if ($group) {
            $output .= "<p>✅ Gevonden: <a href='" . ossn_site_url("group/{$group->guid}") . "'>group/{$group->guid}</a></p>";
        } else {
            $output .= "<p>❌ Niet gevonden</p>";
        }
    }

    $output .= '</div>';
    echo ossn_view_page('Slug Debug', $output);
}
