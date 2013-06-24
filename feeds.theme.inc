<?php

/**
 * @file
 * Theme functions for feeds.
 */

/**
 * Theme feeds_plugin_form().
 */
function theme_feeds_plugin_form($variables) {
  $form = $variables['form'];
  $output = '';

  foreach (element_children($form['plugin_key']) as $key) {

    // Assemble container, render form elements.
    $container = array(
      'title' => $form['plugin_key'][$key]['#title'],
      'body' => isset($form['plugin_key'][$key]['#description']) ? $form['plugin_key'][$key]['#description'] : '',
    );
    $form['plugin_key'][$key]['#title'] = t('Select');
    $form['plugin_key'][$key]['#attributes']['class'] = array('feeds-radio-link');
    unset($form['plugin_key'][$key]['#description']);
    $container['actions'] = array(drupal_render($form['plugin_key'][$key]));

    $output .= theme('feeds_container', array('container' => $container));
  }

  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme feeds_edit_page().
 */
function theme_feeds_edit_page($variables) {
  $config_info = $variables['info'];
  $active_container = $variables['active'];
  drupal_add_css(drupal_get_path('module', 'feeds') . '/feeds.ui.css');

  // Outer wrapper.
  $output = '<div class="feeds-settings clear-block">';

  // Build left bar.
  $output .= '<div class="left-bar">';
  foreach ($config_info as $info) {
    $output .= theme('feeds_container', array('container' => $info));
  }
  $output .= '</div>';

  // Build configuration space.
  $output .= '<div class="configuration">';
  $output .= '<div class="configuration-squeeze">';
  $output .= theme('feeds_container', array('container' => $active_container));
  $output .= '</div>';
  $output .= '</div>';

  $output .= '</div>'; // ''<div class="feeds-settings">';

  return $output;
}

/**
 * Render a simple container. A container can have a title, a description and
 * one or more actions. Recursive.
 *
 * @todo Replace with theme_fieldset or a wrapper to theme_fieldset?
 *
 * @param $variables
 *   An array containing an array at 'container'.
 *   A 'container' array may contain one or more of the following keys:
 *   array(
 *     'title' => 'the title',
 *     'body' => 'the body of the container, may also be an array of more
 *                containers or a renderable array.',
 *     'class' => array('the class of the container.'),
 *     'id' => 'the id of the container',
 *   );
 */
function theme_feeds_container($variables) {
  $container = $variables['container'];

  $class = array_merge(array('feeds-container'), empty($container['class']) ? array('plain') : $container['class']);
  $id = empty($container['id']) ? '': ' id="' . $container['id'] . '"';
  $output = '<div class="' . implode(' ', $class) . '"' . $id . '>';

  if (isset($container['actions']) && count($container['actions'])) {
    $output .= '<ul class="container-actions">';
    foreach ($container['actions'] as $action) {
      $output .= '<li>' . $action . '</li>';
    }
    $output .= '</ul>';
  }

  if (!empty($container['title'])) {
    $output .= '<h4 class="feeds-container-title">';
    $output .= $container['title'];
    $output .= '</h4>';
  }

  if (!empty($container['body'])) {
    $output .= '<div class="feeds-container-body">';
    if (is_array($container['body'])) {
      if (isset($container['body']['#type'])) {
        $output .= drupal_render($container['body']);
      }
      else {
        foreach ($container['body'] as $c) {
          $output .= theme('feeds_container', array('container' => $c));
        }
      }
    }
    else {
      $output .= $container['body'];
    }
    $output .= '</div>';
  }

  $output .= '</div>';
  return $output;
}

/**
 * Theme function for feeds_mapping_form().
 */
function theme_feeds_mapping_form($variables) {
  $form = $variables['form'];

  // Build the actual mapping table.
  $header = array(
    t('Source'),
    t('Target'),
    t('Target configuration'),
    '&nbsp;',
    t('Weight'),
  );
  $rows = array();
  if (is_array($form['#mappings'])) {
    foreach ($form['#mappings'] as $i => $mapping) {
      // Some parsers do not define source options.
      $source = isset($form['source']['#options'][$mapping['source']]) ? $form['source']['#options'][$mapping['source']] : $mapping['source'];
      $target = isset($form['target']['#options'][$mapping['target']]) ? check_plain($form['target']['#options'][$mapping['target']]) : '<em>' . t('Missing') . '</em>';
      $rows[] = array(
        'data' => array(
          check_plain($source),
          $target,
          drupal_render($form['config'][$i]),
          drupal_render($form['remove_flags'][$i]),
          drupal_render($form['mapping_weight'][$i]),
        ),
        'class' => array('draggable', 'tabledrag-leaf'),
      );
    }
  }
  if (!count($rows)) {
    $rows[] = array(
      array(
        'colspan' => 5,
        'data' => t('No mappings defined.'),
      ),
    );
  }
  $rows[] = array(
    drupal_render($form['source']),
    drupal_render($form['target']),
    '',
    drupal_render($form['add']),
    '',
  );
  $output = '<div class="help feeds-admin-ui">' . drupal_render($form['help']) . '</div>';
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'feeds-mapping-overview')));

  // Build the help table that explains available sources.
  $legend = '';
  $rows = array();
  foreach (element_children($form['legendset']['legend']['sources']) as $k) {
    $rows[] = array(
      check_plain(drupal_render($form['legendset']['legend']['sources'][$k]['name'])),
      check_plain(drupal_render($form['legendset']['legend']['sources'][$k]['description'])),
    );
  }
  if (count($rows)) {
    $legend .= '<h4>' . t('Sources') . '</h4>';
    $legend .= theme('table', array('header' => array(t('Name'), t('Description')), 'rows' => $rows));
  }

  // Build the help table that explains available targets.
  $rows = array();
  foreach (element_children($form['legendset']['legend']['targets']) as $k) {
    $rows[] = array(
      check_plain(drupal_render($form['legendset']['legend']['targets'][$k]['name'])),
      check_plain(drupal_render($form['legendset']['legend']['targets'][$k]['description'])),
    );
  }
  $legend .= '<h4>' . t('Targets') . '</h4>';
  $legend .= theme('table', array('header' => array(t('Name'), t('Description')), 'rows' => $rows));

  // Stick tables into collapsible fieldset.
  $form['legendset']['legend'] = array(
    '#markup' => '<div>' . $legend . '</div>',
  );

  $output .= drupal_render($form['legendset']);
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('feeds-mapping-overview', 'order', 'sibling', 'feeds-mapping-weight');
  return $output;
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
