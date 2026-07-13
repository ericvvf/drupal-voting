<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\drupal_voting\Entity\Question;
use Drupal\drupal_voting\VotingService;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the question option entity type.
 */
final class QuestionOptionListBuilder extends EntityListBuilder {

  /**
   * The Question object.
   *
   * @var \Drupal\drupal_voting\Entity\Question Question
   */
  protected ?Question $question = NULL;

  /**
   * Vote counts indexed by question option ID.
   *
   * @var int[]
   */
  private array $voteCounts = [];

  protected $votingService;


  /**
   * Constructs a new QuestionListBuilder object.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    VotingService $voting_service,
  ) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->votingService = $voting_service;
  }

   /**
    * {@inheritdoc}
    */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('drupal_voting.voting_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {

    $header['label'] = $this->t('Label');
    $header['votes'] = $this->t('Votes');
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

    $row['label'] = $entity->toLink($entity->label(), 'edit-form')->toString();
    $row['votes'] = $this->voteCounts[(int) $entity->id()] ?? 0;
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

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    if ($this->question) {
      $option_ids = $this->getStorage()
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('question', $this->question->id())
        ->execute();

      $this->voteCounts = $this->votingService->getVoteCountsByOptionIds(array_map('intval', $option_ids));
    }

    return parent::render();
  }

}
