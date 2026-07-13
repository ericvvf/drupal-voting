<?php

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides a list controller for the question entity type.
 */
final class QuestionListBuilder extends EntityListBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new QuestionListBuilder object.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
  ) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Question Title');
    
    // Only admins can view these columns
    if (!$this->isVoter()) {
      $header['identifier'] = $this->t('Identifier');
      $header['status'] = $this->t('Published');
      $header['show_results'] = $this->t('Show results');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\drupal_voting\QuestionInterface $entity */
    $row['label'] = $entity->label();

    // Only admins can view these columns
    if (!$this->isVoter()) {
      $row['identifier'] = $entity->getIdentifier();
      $row['status'] = $entity->isPublished()
        ? $this->t('Yes')
        : $this->t('No');
      $row['show_results'] = $entity->shouldShowResults()
        ? $this->t('Yes')
        : $this->t('No');
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity): array {
    // Check if user is a voter (has vote permission but not admin permission).
    $is_voter = $this->currentUser->hasPermission('vote on questions')
      && !$this->currentUser->hasPermission('administer drupal_voting_question');

    if ($is_voter) {
      // For voters, only show the Vote operation.
      $operations = [];
      $operations['vote'] = [
        'title' => $this->t('Vote'),
        'weight' => 0,
        'url' => Url::fromRoute(
          'drupal_voting.vote',
          [
            'question' => $entity->id(),
          ],
        ),
      ];
      return $operations;
    }

    // For administrators, show all operations.
    $operations = parent::getOperations($entity);

    $operations['manage_options'] = [
      'title' => $this->t('Manage Answer Options'),
      'weight' => 20,
      'url' => Url::fromRoute(
        'entity.drupal_voting_question.options',
        [
          'question' => $entity->id(),
        ],
      ),
    ];

    return $operations;
  }
  
  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    if ($this->isVoter()) {
      $build['#attached']['library'][] = 'drupal_voting/voting_questions';
      $build['table']['#attributes']['class'][] = 'drupal_voting-voter-list';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();

    if ($this->isVoter()) {
      $query->condition('status', 1);
    }

    return $query;
  }

  protected function isVoter(): bool {
    return $this->currentUser->hasPermission('vote on questions')
      && !$this->currentUser->hasPermission('administer drupal_voting_question');
  }

}
