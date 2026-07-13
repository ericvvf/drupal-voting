<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_voting\QuestionService;
use Drupal\drupal_voting\VotingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller for the Drupal Voting module.
 */
final class ApiController extends ControllerBase {

  /**
   * Constructs an ApiController object.
   */
  public function __construct(
    protected QuestionService $questionService,
    protected VotingService $votingService,
    protected $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drupal_voting.question_service'),
      $container->get('drupal_voting.voting_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * GET /api/v1/questions - Returns all published questions.
   */
  public function listQuestions(): JsonResponse {
    $questions = $this->questionService->getPublishedQuestions();
    $data = [];
    foreach ($questions as $question) {
      $data[] = [
        'identifier' => $question->get('identifier')->value,
        'title' => $question->label(),
      ];
    }
    return new JsonResponse($data);
  }

  /**
   * GET /api/v1/questions/{identifier} - Returns a single published question.
   */
  public function getQuestion(string $identifier): JsonResponse {
    $question = $this->questionService->getPublishedQuestionByIdentifier($identifier);

    if (!$question) {
      return new JsonResponse(
        ['error' => 'Question not found'],
        404
      );
    }

    $data = $this->questionService->buildQuestionResponse($question);
    return new JsonResponse($data);
  }

  /**
   * POST /api/v1/questions/{identifier}/vote - Registers a vote.
   * 
   * The test documentation makes no mention of an authentication mechanism. 
   * Therefore, we only require that a system user's email address be provided 
   * in the body request so that we can register the vote.
   */
  public function vote(string $identifier, Request $request): JsonResponse {
    $question = $this->questionService->getPublishedQuestionByIdentifier($identifier);

    if (!$question) {
      return new JsonResponse(
        ['error' => 'Question not found'],
        404
      );
    }

    $data = json_decode($request->getContent(), TRUE);

    // Validate email.
    if (empty($data['email'])) {
      return new JsonResponse(
        ['error' => 'Email is required'],
        400
      );
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse(
        ['error' => 'Invalid email address'],
        400
      );
    }

    // Validate option_id.
    if (empty($data['option_id'])) {
      return new JsonResponse(
        ['error' => 'Option ID is required'],
        400
      );
    }

    // Load user by email.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = $user_storage->loadByProperties(['mail' => $data['email']]);

    if (empty($users)) {
      return new JsonResponse(
        ['error' => 'User not found'],
        404
      );
    }

    $user = reset($users);

    // Load the option.
    $option_storage = $this->entityTypeManager->getStorage('drupal_voting_question_option');
    $option = $option_storage->load($data['option_id']);

    if (!$option) {
      return new JsonResponse(
        ['error' => 'Invalid option'],
        400
      );
    }

    // Verify option belongs to the question.
    if ((int) $option->get('question')->target_id !== (int) $question->id()) {
      return new JsonResponse(
        ['error' => 'Option does not belong to this question'],
        400
      );
    }

    // Check if user already voted.
    if ($this->votingService->hasUserVoted($question, $user)) {
      return new JsonResponse(
        ['error' => 'User has already voted on this question'],
        409
      );
    }

    // Record the vote.
    try {
      $this->votingService->recordVote($option, $user);

      return new JsonResponse(
        [
          'success' => TRUE,
          'message' => 'Vote successfully recorded.',
        ],
        201
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(
        ['error' => 'Failed to record vote'],
        500
      );
    }
  }

  /**
   * GET /api/v1/questions/{identifier}/results - Returns voting results.
   */
  public function results(string $identifier): JsonResponse {
    $question = $this->questionService->getPublishedQuestionByIdentifier($identifier);

    if (!$question) {
      return new JsonResponse(
        ['error' => 'Question not found'],
        404
      );
    }

    // Check if results should be shown.
    if (!$question->get('show_results')->value) {
      return new JsonResponse(
        ['error' => 'Results are not available for this question'],
        403
      );
    }

    $data = $this->questionService->buildResultsResponse($question);
    return new JsonResponse($data);
  }

}
