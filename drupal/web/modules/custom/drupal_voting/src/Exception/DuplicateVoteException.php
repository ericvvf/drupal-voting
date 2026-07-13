<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Exception;

/**
 * Thrown when a user tries to vote twice on the same question.
 */
final class DuplicateVoteException extends \RuntimeException {

}