<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\drupal_voting\Entity\Question;

/**
 * Service for handling question operations.
 */
class QuestionService {

    protected $entityTypeManager;
    protected $fileUrlGenerator;
    protected $votingService;


  /**
   * Constructs a QuestionService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
    VotingService $voting_service,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->votingService = $voting_service;
  }

  /**
   * Retrieves all published questions.
   *
   * @return \Drupal\drupal_voting\Entity\Question[]
   *   Array of published Question entities.
   */
  public function getPublishedQuestions(): array {
    $question_storage = $this->entityTypeManager->getStorage('drupal_voting_question');

    $question_ids = $question_storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    return $question_storage->loadMultiple($question_ids);
  }

  /**
   * Retrieves a published question by identifier.
   *
   * @param string $identifier
   *   The question identifier.
   *
   * @return \Drupal\drupal_voting\Entity\Question|null
   *   The Question entity or NULL if not found or not published.
   */
  public function getPublishedQuestionByIdentifier(string $identifier): ?Question {
    $question_storage = $this->entityTypeManager->getStorage('drupal_voting_question');

    $question_ids = $question_storage->getQuery()
      ->condition('identifier', $identifier)
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (empty($question_ids)) {
      return NULL;
    }

    $question = $question_storage->load(reset($question_ids));

    // Only return if published.
    if (!$question || !$question->get('status')->value) {
      return NULL;
    }

    return $question;
  }

  /**
   * Builds API response array for a single question with options.
   *
   * @param \Drupal\drupal_voting\Entity\Question $question
   *   The Question entity.
   *
   * @return array
   *   Question data with options for API response.
   */
  public function buildQuestionResponse(Question $question): array {
    $options = $this->votingService->getQuestionOptions($question);

    $options_data = [];
    foreach ($options as $option) {
      $image_url = NULL;
      if (!$option->get('image')->isEmpty()) {
        $image_file = $option->get('image')->entity;
        if ($image_file) {
          $image_url = $this->fileUrlGenerator->generateAbsoluteString($image_file->getFileUri());
        }
      }

      $options_data[] = [
        'id' => (int) $option->id(),
        'title' => $option->label(),
        'description' => $option->get('description')->value ?? '',
        'image_url' => $image_url,
      ];
    }

    return [
      'identifier' => $question->get('identifier')->value,
      'title' => $question->label(),
      'show_results' => (bool) $question->get('show_results')->value,
      'options' => $options_data,
    ];
  }

  /**
   * Builds API response array for question results.
   *
   * @param \Drupal\drupal_voting\Entity\Question $question
   *   The Question entity.
   *
   * @return array
   *   Results data for API response.
   */
  public function buildResultsResponse(Question $question): array {
    $results = $this->votingService->getResults($question);

    // Order by votes descending.
    usort($results, static fn(array $a, array $b) => $b['votes'] <=> $a['votes']);

    // Calculate total votes.
    $total_votes = array_sum(array_column($results, 'votes'));

    // Format results with percentages.
    $formatted_results = [];
    foreach ($results as $result) {
      $percentage = $total_votes > 0
        ? round(($result['votes'] / $total_votes) * 100)
        : 0;

      $formatted_results[] = [
        'option' => $result['label'],
        'votes' => $result['votes'],
        'percentage' => (int) $percentage,
      ];
    }

    return [
      'identifier' => $question->get('identifier')->value,
      'title' => $question->label(),
      'results' => $formatted_results,
    ];
  }

}
