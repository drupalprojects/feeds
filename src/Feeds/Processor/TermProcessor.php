<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Processor\TermProcessor.
 */

namespace Drupal\feeds\Feeds\Processor;

/**
 * Defines a term processor.
 *
 * Creates taxonomy terms from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:taxonomy_term",
 *   title = @Translation("Term"),
 *   description = @Translation("Creates taxonomy terms from feed items."),
 *   entity_type = "taxonomy_term",
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class TermProcessor extends EntityProcessorBase {

}
