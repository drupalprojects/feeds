<?php

/**
 * @file
 * Theme functions for feeds.
 */

use Drupal\Core\Template\Attribute;

/**
 * Theme function for feeds_mapping_form().
 */
// function theme_feeds_mapping_form($variables) {
  // $form = $variables['form'];

  // // Build the actual mapping table.
  // $header = array(
  //   t('Source'),
  //   t('Target'),
  //   t('Target configuration'),
  //   '&nbsp;',
  //   t('Weight'),
  // );


  // $build = array(
  //   '#theme' => 'table',
  //   '#header' => $header,
  //   '#rows' => array(),
  //   '#empty' => t('This should never happen.'),
  // );

  // $rows = array();
  // foreach ($form['sources'] as $delta => &$source) {
  //   // Some parsers do not define source options.
  //   $build['#rows'][] = array(
  //     'data' => array(
  //       drupal_render($source),
  //       drupal_render($form['targets'][$delta]),
  //     ),
  //   );
  // }

  // return $build;
// }

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
