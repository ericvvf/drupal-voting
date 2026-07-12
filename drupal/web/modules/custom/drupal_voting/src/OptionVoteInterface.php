<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an optionvote entity type.
 */
interface OptionVoteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
