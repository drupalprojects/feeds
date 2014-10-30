<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Processor\UserProcessor.
 */

namespace Drupal\feeds\Feeds\Processor;

/**
 * Defines a user processor.
 *
 * Creates users from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:user",
 *   title = @Translation("User"),
 *   description = @Translation("Creates users from feed items."),
 *   entity_type = "user",
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class UserProcessor extends EntityProcessorBase {

}
