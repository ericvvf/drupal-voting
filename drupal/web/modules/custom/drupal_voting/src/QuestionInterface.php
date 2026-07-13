<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a question entity type.
 */
interface QuestionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

    /**
     * Returns the Identifier.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the Description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns the Question publishing status.
     *
     * @return bool
     */
    public function isPublished(): bool;

    /**
     * Returns if should show the results or not.
     *
     * @return bool
     */
    public function shouldShowResults(): bool;

    /**
     * Returns the published and unpublished answer options.
     *
     * @return \Drupal\drupal_voting\QuestionOptionInterface[]
     */
    public function getOptions(): array;
}
