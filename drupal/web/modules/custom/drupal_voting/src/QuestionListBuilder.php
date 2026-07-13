<?php

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for the question entity type.
 */
final class QuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Question Title');
    $header['identifier'] = $this->t('Identifier');
    $header['status'] = $this->t('Published');
    $header['show_results'] = $this->t('Show results');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\drupal_voting\QuestionInterface $entity */
    $row['label'] = $entity
      ->toLink($entity->label(), 'edit-form')
      ->toString();

    $row['identifier'] = $entity->get('identifier')->value;
    $row['status'] = $entity->get('status')->value
      ? $this->t('Yes')
      : $this->t('No');

    $row['show_results'] = $entity->get('show_results')->value
      ? $this->t('Yes')
      : $this->t('No');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity): array {
    $operations = parent::getOperations($entity);

    $operations['manage_options'] = [
      'title' => $this->t('Manage Answer Options'),
      'weight' => 20,
      'url' => Url::fromRoute(
        'entity.drupal_voting_question.options',
        [
          'drupal_voting_question' => $entity->id(),
        ],
      ),
    ];

    return $operations;
  }

}
