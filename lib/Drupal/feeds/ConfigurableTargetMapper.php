<?php

/**
 * @file
 * Contains \Drupal\feeds\ConfigurableTargetMapper.
 */

class ConfigurableTargetMapper extends TargetMapperBase {

  /**
   * Constructs a ConfigurableTargetMapper object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   */
  public function __construct(FeedInterface $feed, array $configuration) {
    $this->feed = $feed;

    // Calling setConfiguration() ensures the configuration is clean and
    // defaults are set.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    if ($key) {
      if (isset($this->configuration[$key])) {
        return $this->configuration[$key];
      }

      return;
    }

    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $defaults = $this->getDefaultConfiguration();
    $this->configuration = array_intersect_key($configuration, $defaults) + $defaults;
  }

  /**
   * Returns the default configuration for a plugin.
   *
   * @return array
   *   Array where keys are the variable names of the configuration elements and
   *   values are their default values. Any configuration that needs to be saved
   *   must have its keys declared here.
   *
   * @see \Drupal\feeds\Plugin\Type\ConfigurablePluginBase::setConfiguration()
   */
  abstract protected function getDefaultConfiguration();

}
