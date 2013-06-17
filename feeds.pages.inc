<?php

use Drupal\Core\Template\Attribute;
use Drupal\feeds\Plugin\Core\Entity\Importer;

/**
 * @file
 * Menu callbacks, form callbacks and helpers.
 */

/**
 * Render a page of available importers.
 */
function feeds_page() {
  $rows = array();
  if ($importers = feeds_importer_load_all()) {
    foreach ($importers as $importer) {
      if ($importer->disabled) {
        continue;
      }
      if (!(user_access('import ' . $importer->id() . ' feeds') || user_access('administer feeds'))) {
        continue;
      }
      $link = 'feed/add/' . $importer->id();
      $title = $importer->label();
      $rows[] = array(
        l($title, $link),
        check_plain($importer->description),
      );
    }
  }
  if (empty($rows)) {
    drupal_set_message(t('There are no importers, go to <a href="@importers">Feed importers</a> to create one or enable an existing one.', array('@importers' => url('admin/structure/feeds'))));
  }
  $header = array(
    t('Import'),
    t('Description'),
  );
  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Render a feeds import form on node/id/import pages.
 */
function feeds_import_tab_form($form, &$form_state, $feed) {
  $form_state['feed'] = $feed;
  $form['#redirect'] = 'feed/' . $feed->id();
  $form['source_status'] = array(
    '#type' => 'fieldset',
    '#title' => t('Status'),
    '#tree' => TRUE,
    '#value' => feeds_feed_status($feed),
  );
  $form = confirm_form($form, t('Import all content from source?'), 'feed/' . $feed->id(), '', t('Import'), t('Cancel'), 'confirm feeds update');
  $progress = $feed->progressImporting();
  if ($progress !== FEEDS_BATCH_COMPLETE) {
    $form['actions']['submit']['#disabled'] = TRUE;
    $form['actions']['submit']['#value'] =
      t('Importing (@progress %)', array('@progress' => number_format(100 * $progress, 0)));
  }
  return $form;
}

/**
 * Submit handler for feeds_import_tab_form().
 */
function feeds_import_tab_form_submit($form, &$form_state) {
  $form_state['redirect'] = $form['#redirect'];
  $form_state['feed']->startImport();
}

/**
 * Render a feeds delete form.
 *
 * Used on both node pages and configuration pages.
 * Therefore $node may be missing.
 */
function feeds_delete_tab_form($form, &$form_state, $feed) {
  $form['#redirect'] = 'feed/' . $feed->id();
  // Form cannot pass on source object.
  $form_state['feed'] = $feed;
  $form['source_status'] = array(
    '#type' => 'fieldset',
    '#title' => t('Status'),
    '#tree' => TRUE,
    '#value' => feeds_feed_status($feed),
  );
  $form = confirm_form($form, t('Delete all items from source?'), $form['#redirect'], '', t('Delete'), t('Cancel'), 'confirm feeds update');
  $progress = $feed->progressClearing();
  if ($progress !== FEEDS_BATCH_COMPLETE) {
    $form['actions']['submit']['#disabled'] = TRUE;
    $form['actions']['submit']['#value'] =
      t('Deleting (@progress %)', array('@progress' => number_format(100 * $progress, 0)));
  }
  return $form;
}

/**
 * Submit handler for feeds_delete_tab_form().
 */
function feeds_delete_tab_form_submit($form, &$form_state) {
  $form_state['redirect'] = $form['#redirect'];
  $form_state['feed']->startClear();
}

/**
 * Render a feeds unlock form.
 *
 * Used on both node pages and configuration pages.
 * Therefore $node may be missing.
 */
function feeds_unlock_tab_form($form, &$form_state, $feed) {
  $form['#redirect'] = 'feed/' . $feed->id();
  $form_state['feed'] = $feed;
  // Form cannot pass on source object.
  $form['source_status'] = array(
    '#type' => 'fieldset',
    '#title' => t('Status'),
    '#tree' => TRUE,
    '#value' => feeds_feed_status($feed),
  );
  $form = confirm_form($form, t('Unlock this importer?'), $form['#redirect'], '', t('Delete'), t('Cancel'), 'confirm feeds update');
  if ($feed->progressImporting() == FEEDS_BATCH_COMPLETE && $feed->progressClearing() == FEEDS_BATCH_COMPLETE) {
    $form['source_locked'] = array(
      '#type' => 'markup',
      '#title' => t('Not Locked'),
      '#tree' => TRUE,
      '#markup' => t('This importer is not locked, therefore it cannot be unlocked.'),
    );
    $form['actions']['submit']['#disabled'] = TRUE;
    $form['actions']['submit']['#value'] = t('Unlock (disabled)');
  }
  else {
    $form['actions']['submit']['#value'] = t('Unlock');
  }
  return $form;
}

/**
 * Form submit handler. Resets all feeds state.
 */
function feeds_unlock_tab_form_submit($form, &$form_state) {
  drupal_set_message(t('Import Unlocked'));
  $form_state['redirect'] = $form['#redirect'];
  $fid = empty($form['#fid']) ? 0 : $form['#fid'];
  $importer_id = $form['#importer_id'];

  //Is there a more API-friendly way to set the state?
  db_update('feeds_feed')
    ->condition('fid', $fid)
    ->fields(array('state' => FALSE))
    ->execute();
}

/**
 * Handle a fetcher callback.
 */
function feeds_fetcher_callback($importer, $fid = 0) {
  if ($importer instanceof Importer) {
    try {
      return $importer->fetcher->request($fid);
    }
    catch (Exception $e) {
      // Do nothing.
    }
  }
  drupal_access_denied();
}

/**
 * Renders a status display for a source.
 */
function feeds_feed_status($feed) {
  $progress_importing = $feed->progressImporting();
  $v = array();
  if ($progress_importing != FEEDS_BATCH_COMPLETE) {
    $v['progress_importing'] = $progress_importing;
  }
  $progress_clearing = $feed->progressClearing();
  if ($progress_clearing != FEEDS_BATCH_COMPLETE) {
    $v['progress_clearing'] = $progress_clearing;
  }
  $v['imported'] = $feed->imported->value;
  $v['count'] = $feed->itemCount();
  if (!empty($v)) {
    return theme('feeds_feed_status', $v);
  }
}

/**
 * Themes a status display for a source.
 */
function theme_feeds_feed_status($v) {
  $output = '<div class="info-box feeds-source-status">';
  $items = array();
  if ($v['progress_importing']) {
    $progress = number_format(100.0 * $v['progress_importing'], 0);
    $items[] = t('Importing - @progress % complete.', array('@progress' => $progress));
  }
  if ($v['progress_clearing']) {
    $progress = number_format(100.0 * $v['progress_clearing'], 0);
    $items[] = t('Deleting items - @progress % complete.', array('@progress' => $progress));
  }
  if (!count($items)) {
    if ($v['count']) {
      if ($v['imported']) {
        $items[] = t('Last import: @ago ago.', array('@ago' => format_interval(REQUEST_TIME - $v['imported'], 1)));
      }
      $items[] = t('@count imported items total.', array('@count' => $v['count']));
    }
    else {
      $items[] = t('No imported items.');
    }
  }
  $output .= theme('item_list', array('items' => $items));
  $output .= '</div>';
  return $output;
}

/**
 * Theme upload widget.
 */
function theme_feeds_upload($variables) {
  $element = $variables['element'];
  drupal_add_css(drupal_get_path('module', 'feeds') . '/feeds.css');
  _form_set_attributes($element, array('form-file'));
  $description = '';
  if (!empty($element['#file_info'])) {
    $file = $element['#file_info'];
    $wrapper = file_stream_wrapper_get_instance_by_uri($file->getFileUri());
    $description .= '<div class="file-info">';
    $description .= '<div class="file-name">';
    if ($wrapper) {
      $description .= l($file->getFilename(), $wrapper->getExternalUrl());
    }
    else {
      $description .= t('URI scheme %scheme not available.', array('%scheme' =>  file_uri_scheme($file->getFileUri())));
    }
    $description .= '</div>';
    $description .= '<div class="file-size">';
    $description .= format_size($file->getSize());
    $description .= '</div>';
    $description .= '<div class="file-mime">';
    $description .= check_plain($file->getMimeType());
    $description .= '</div>';
    $description .= '</div>';
  }
  $description .= '<div class="file-upload">';
  $description .= '<input type="file" name="' . $element['#name'] . '"' . ($element['#attributes'] ? ' ' . new Attribute($element['#attributes']) : '') . ' id="' . $element['#id'] . '" size="' . $element['#size'] . "\" />\n";
  $description .= '</div>';
  $element['#description'] = $description;

  // For some reason not unsetting #title leads to printing the title twice.
  unset($element['#title']);
  return theme('form_element', $element);
}

/**
 * Page callback: Provides the feed submission form.
 *
 * @param Importer $importer
 *   The feeds_importer entity.
 *
 * @return array
 *   A feed submission form.
 *
 * @see feeds_menu()
 */
function feeds_feed_add_page($importer) {
  drupal_set_title(t('Create @name', array('@name' => $importer->label())), PASS_THROUGH);

  $feed = entity_create('feeds_feed', array(
    'uid' => $GLOBALS['user']->uid,
    'importer' => $importer->id(),
    'status' => 1,
    'create' => REQUEST_TIME,
  ));

  return entity_get_form($feed);
}

/**
 * Page callback: Form constructor for feed deletion confirmation form.
 *
 * @param FeedInterface $feed
 *   A feed object.
 *
 * @return array
 *   A form array.
 *
 * @see feeds_feed_delete_confirm_submit()
 * @see feed_menu()
 */
function feeds_feed_delete_confirm($form, &$form_state, $feed) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form_state['feed'] = $feed;
  return confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $feed->label())),
    'feed/' . $feed->id(),
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for feeds_feed_delete_confirm().
 */
function feeds_feed_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    $feed = $form_state['feed'];
    $feed->delete();
    watchdog('content', '@type: deleted %title.', array('@type' => $feed->type, '%title' => $feed->label()));
    drupal_set_message(t('@type %title has been deleted.', array('@type' => $feed->getImporter()->label(), '%title' => $feed->label())));
  }

  $form_state['redirect'] = '<front>';
}

function feeds_feed_page_view() {
  return t('asdfasdf');
}
