<?php

declare(strict_types=1);

namespace Drupal\drupal_voting;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\drupal_voting\Entity\Question;
use Drupal\drupal_voting\Entity\QuestionOption;
use Drupal\Core\Database\Connection;

/**
 * Service for handling voting operations.
 */
class VotingService {
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The DB connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;


  /**
   * Constructs a VotingService object.
   * 
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *  The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Database\Connection $db
   *  The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    Connection $db,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->database = $db;
  }

  /**
   * Checks if a user has already voted on a question.
   *
   * @param \Drupal\drupal_voting\Entity\Question $question
   *   The question entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account. Defaults to current user.
   *
   * @return bool
   *   TRUE if the user has already voted, FALSE otherwise.
   */
  public function hasUserVoted(Question $question, ?AccountInterface $account = NULL): bool {

    if (!$account) {
      $account = $this->currentUser;
    }

    $query = $this->database->select('drupal_voting_optionvote', 'vote');

    $query->innerJoin(
      'drupal_voting_question_option',
      'question_option',
      'question_option.id = vote.question_option'
    );

    $query->addField('vote', 'id');

    $query->condition(
      'question_option.question',
      $question->id()
    );

    $query->condition(
      'vote.uid',
      $account->id()
    );

    $query->range(0, 1);

    return $query->execute()->fetchField() !== FALSE;

  }

  /**
   * Records a vote for a question option.
   *
   * @param \Drupal\drupal_voting\Entity\QuestionOption $option
   *   The question option entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account. Defaults to current user.
   *
   * @return \Drupal\drupal_voting\Entity\OptionVote
   *   The created vote entity.
   *
   * @throws \Exception
   *   If the user has already voted on this question.
   */
  public function recordVote(QuestionOption $option, ?AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->currentUser;
    }

    // Get the question from the option.
    $question = $option->get('question')->entity;

    // Check if user has already voted.
    if ($this->hasUserVoted($question, $account)) {
      throw new \Exception('User has already voted on this question.');
    }
    $transaction = $this->database->startTransaction();

    try {
      $vote_storage = $this->entityTypeManager->getStorage('drupal_voting_optionvote');
      $vote = $vote_storage->create([
        'question_option' => $option->id(),
        'uid' => $account->id(),
      ]);
      $vote->save();
      unset($transaction);
      return $vote;
    }
    catch (\Exception $e) {

      if (isset($transaction)) {
        $transaction->rollBack();
      }

      throw $e;
    }
  }

  /**
   * Gets voting results for a question.
   *
   * @param \Drupal\drupal_voting\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   Array of results with keys:
   *   - option_id: The option ID
   *   - label: The option label
   *   - votes: Number of votes
   *   - percentage: Percentage of total votes
   */
  public function getResults(Question $question): array {
    $option_storage = $this->entityTypeManager->getStorage('drupal_voting_question_option');
    $vote_storage = $this->entityTypeManager->getStorage('drupal_voting_optionvote');

    // Get all options for this question.
    $option_ids = $option_storage->getQuery()
      ->condition('question', $question->id())
      ->accessCheck(FALSE)
      ->execute();

    if (empty($option_ids)) {
      return [];
    }

    $options = $option_storage->loadMultiple($option_ids);

    // Count votes for each option.
    $results = [];
    $total_votes = 0;

    foreach ($options as $option) {
      $vote_count = $vote_storage->getQuery()
        ->condition('question_option', $option->id())
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $results[$option->id()] = [
        'option_id' => $option->id(),
        'label' => $option->label(),
        'votes' => $vote_count,
        'percentage' => 0,
      ];

      $total_votes += $vote_count;
    }

    // Calculate percentages.
    if ($total_votes > 0) {
      foreach ($results as $option_id => &$result) {
        $result['percentage'] = round(($result['votes'] / $total_votes) * 100, 1);
      }
    }

    return $results;
  }

  /**
   * Gets published options for a question.
   *
   * @param \Drupal\drupal_voting\Entity\Question $question
   *   The question entity.
   *
   * @return \Drupal\drupal_voting\Entity\QuestionOption[]
   *   Array of published question option entities.
   */
  public function getQuestionOptions(Question $question): array {
    $option_storage = $this->entityTypeManager->getStorage('drupal_voting_question_option');

    $option_ids = $option_storage->getQuery()
      ->condition('question', $question->id())
      ->accessCheck(TRUE)
      ->execute();

    return $option_storage->loadMultiple($option_ids);
  }

  /**
   * Returns vote totals indexed by question option ID.
   *
   * @param int[] $option_ids
   *   The question option IDs.
   *
   * @return int[]
   *   Vote totals indexed by option ID.
   */
  public function getVoteCountsByOptionIds(array $option_ids): array {
    if ($option_ids === []) {
      return [];
    }

    $vote_counts = array_fill_keys($option_ids, 0);

    $query = $this->database->select('drupal_voting_optionvote','vote',);

    $query->addField('vote', 'question_option');
    $query->addExpression('COUNT(vote.id)', 'vote_count');

    $query->condition('vote.question_option',$option_ids,'IN',);

    $query->groupBy('vote.question_option');

    foreach ($query->execute() as $row) {
      $vote_counts[(int) $row->question_option] = (int) $row->vote_count;
    }

    return $vote_counts;
  }

}
