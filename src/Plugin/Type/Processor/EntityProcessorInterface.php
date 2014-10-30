<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface.
 */

namespace Drupal\feeds\Plugin\Type\Processor;

use Drupal\feeds\Plugin\Type\AdvancedFormPluginInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;

/**
 * Interface for Feeds entity processor plugins.
 */
interface EntityProcessorInterface extends ProcessorInterface, AdvancedFormPluginInterface, ClearableInterface, LockableInterface {

}
