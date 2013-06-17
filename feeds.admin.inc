<?php

/**
 * Page callback: Presents an administrative feeds listing.
 *
 * @see feeds_menu()
 * @see feeds_multiple_delete_confirm()
 */
function feeds_admin($type = 'new') {
  $edit = $_POST;

  if (isset($edit['operation']) && ($edit['operation'] == 'delete') && isset($edit['feedss']) && $edit['feedss']) {
    return drupal_get_form('feeds_multiple_delete_confirm');
  }
  else {
    return drupal_get_form('feeds_admin_overview', $type);
  }
}
