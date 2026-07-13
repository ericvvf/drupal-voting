<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\drupal_voting\Entity\Question;

/**
 * Provides a list controller for the question option entity type.
 */
final class QuestionOptionListBuilder extends EntityListBuilder {

  protected ?Question $question = NULL;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {

    $header['label'] = $this->t('Label');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\drupal_voting\QuestionOptionInterface $entity */

    $row['label'] = $entity
      ->toLink($entity->label(), 'edit-form')
      ->toString();
    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {

    $query = $this->getStorage()
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('id'));

    if ($this->question) {
      $query->condition('question', $this->question->id());
    }

    return $query->execute();
  }

  public function setQuestion(Question $question): void {
    $this->question = $question;
  }

}
