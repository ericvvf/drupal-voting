<?php

namespace Drupal\drupal_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\drupal_voting\Entity\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Question Answer Options pages.
 */
final class QuestionOptionController extends ControllerBase {


  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Displays the answer options for a question.
   */
  public function listing(Question $drupal_voting_question): array {

    /** @var \Drupal\drupal_voting\QuestionOptionListBuilder $list_builder */
    $list_builder = $this->entityTypeManager
      ->getListBuilder('drupal_voting_question_option');

    $list_builder->setQuestion($drupal_voting_question);

    return [

      'actions' => [
        '#type' => 'actions',

        'add' => [
          '#type' => 'link',
          '#title' => $this->t('Add Answer Option'),
          '#url' => Url::fromRoute(
            'entity.drupal_voting_question_option.add_form',
            [
              'drupal_voting_question' => $drupal_voting_question->id(),
            ],
          ),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'back' => [
          '#type' => 'link',
          '#title' => $this->t('Back to Questions'),
          '#url' => Url::fromRoute(
            'entity.drupal_voting_question.collection',
          ),
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
        ],
      ],
      'options' => $list_builder->render(),
    ];
  }

  /**
   * Builds the Page title.
   */
  public function title(Question $drupal_voting_question): string {
    return $this->t('Answer Options: @question', [
      '@question' => $drupal_voting_question->label(),
    ]);
  }

}