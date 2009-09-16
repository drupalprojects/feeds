<?php
// $Id$

class FeedsNodeProcessor extends FeedsProcessor {

  function process($feed) {
    foreach ($feed->items as $item) {
      if (feeds_unique('FeedsNodeProcessor', $item)) {
        $node = feeds_map('FeedsNodeProcessor', $item);
        node_save($node);
      }
    }
  }

  function getMappingTargets() {
    $config = $this->getConfiguration();
    $default = array('title', 'body', 'status');
    $default += module_invoke('feedapi_mapper', 'list', 'FeedsNodeProcessor', $config->content_type);
    return $default;
  }

  function map() {
    
  }

  function unique() {
    
  }
}