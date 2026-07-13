<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_voting\Entity\Question;
use Drupal\drupal_voting\VotingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\Core\Url;

/**
 * Form for voting on a question.
 */
class VoteForm extends FormBase {

  /**
   * The voting service.
   *
   * @var \Drupal\drupal_voting\VotingService
   */
  protected $votingService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs an ApiAccessTokenForm object.
   *
   * @param \Drupal\drupal_voting\VotingService $voting_service
   *   The voting service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    VotingService $voting_service,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
    RendererInterface $renderer
  ) {
    $this->votingService = $voting_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drupal_voting.voting_service'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupal_voting_vote_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,FormStateInterface $form_state,?Question $question = NULL,): array {
    
    if (!$question) {
      throw new \InvalidArgumentException('The question is required.');
    }

    $form_state->set('question', $question);

    $options = $this->votingService->getQuestionOptions($question);

    if (!$options) {
      $form['empty'] = [
        '#type' => 'item',
        '#markup' => $this->t(
          'This question has no available answer options.'
        ),
      ];

      return $form;
    }

    $form['option'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose your answer'),
      '#attributes' => [
        'class' => ['drupal-voting-options'],
      ],
    ];

    foreach ($options as $option) {
      $option_id = (string) $option->id();

      $description = NULL;

      if ($option->hasField('description') && !$option->get('description')->isEmpty()) {
        $description = (string) $option->get('description')->value;
      }

      $image = NULL;

      if ($option->hasField('image') && !$option->get('image')->isEmpty()) {
        $file = $option->get('image')->entity;

        if ($file instanceof FileInterface) {
          $image = [
            'url' => $this->fileUrlGenerator
              ->generateString($file->getFileUri()),
            'alt' => $option->label(),
          ];
        }
      }

      $form['option'][$option_id] = [
        '#type' => 'radio',

        // All options share the same submitted field name.
        '#parents' => ['selected_option'],

        // Value returned when this radio is selected.
        '#return_value' => $option_id,

        // Drupal still requires a title for accessibility.
        '#title' => $option->label(),
        '#title_display' => 'invisible',

        // Data consumed by the custom Twig wrapper.
        '#option_title' => $option->label(),
        '#option_description' => $description,
        '#option_image' => $image,

        // Replace the standard form-element wrapper.
        '#theme_wrappers' => [
          'drupal_voting_vote_option',
        ],

        '#attributes' => [
          'class' => ['drupal-voting-option__radio'],
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save your vote'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to Questions'),
      '#url' => Url::fromRoute('drupal_voting.question_list'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    $form['#attached']['library'][] = 'drupal_voting/vote_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state,): void {
    parent::validateForm($form, $form_state);

    $selected_option = $form_state->getValue('selected_option');

    if ($selected_option === NULL || $selected_option === '') {
      $form_state->setErrorByName(
        'selected_option',
        $this->t('Please select an answer.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $question = $form_state->get('question');
    $option_id = $form_state->getValue('selected_option');

    // Load the option.
    $option_storage = $this->entityTypeManager->getStorage('drupal_voting_question_option');
    $option = $option_storage->load($option_id);

    if (!$option) {
      $this->messenger()->addError($this->t('Invalid option selected.'));
      return;
    }

    try {
      // Record the vote.
      $this->votingService->recordVote($option);

      $this->messenger()->addStatus($this->t('Your vote has been recorded. Thank you!'));

      // Redirect based on show_results setting.
      if ($question->shouldShowResults()) {
        $form_state->setRedirect('drupal_voting.results', [
          'question' => $question->id(),
        ]);
      }
      else {
        $form_state->setRedirect('entity.drupal_voting_question.collection');
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while recording your vote. Please try again.'));
      $this->getLogger('drupal_voting')->error('Error recording vote: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
}
