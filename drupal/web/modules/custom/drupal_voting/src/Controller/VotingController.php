<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\drupal_voting\Entity\Question;
use Drupal\drupal_voting\VotingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drupal_voting\Form\VoteForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for voting operations.
 */
final class VotingController extends ControllerBase {

  /**
   * Constructs a VotingController object.
   */
  public function __construct(
    protected VotingService $votingService,
    protected $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drupal_voting.voting_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Access callback for voting page.
   */
  public function voteAccess(Question $question, AccountInterface $account): AccessResult {
    
    // Checking if the voting is globally enabled
    if (!$this->votingService->isVotingEnabled()) {
      return AccessResult::forbidden('Voting is globally disabled.')
        ->addCacheTags(['config:drupal_voting.settings']);
    }
    
    // Allow administrators.
    if ($account->hasPermission('administer drupal_voting_question')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Allow voters.
    if ($account->hasPermission('vote on questions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * Title callback for voting page.
   */
  public function voteTitle(Question $question): string {
    return $question->label();
  }

  /**
   * Displays voting results.
   */
  public function results(Question $question): array {
    $results = $this->votingService->getResults($question);
    
    // Ordering by votes count
    usort($results, static fn(array $a, array $b) => $b['votes'] <=> $a['votes']);

    return [
      '#theme' => 'drupal_voting_results',
      '#question_title' => $question->label(),
      '#back_url' => Url::fromRoute('drupal_voting.question_list')->toString(),
      '#results' => $results,
      '#attached' => [
        'library' => [
          'drupal_voting/voting_results',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Access callback for results page.
   */
  public function resultsAccess(Question $question, AccountInterface $account): AccessResult {
    
     // Checking if the voting is globally enabled
    if (!$this->votingService->isVotingEnabled()) {
      return AccessResult::forbidden('Voting is globally disabled.')
        ->addCacheTags(['config:drupal_voting.settings']);
    }

    // Allow administrators.
    if ($account->hasPermission('administer drupal_voting_question')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Allow voters.
    if ($account->hasPermission('vote on questions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  public function list(): array {
    /** @var \Drupal\drupal_voting\QuestionListBuilder $list_builder */
    $list_builder = $this->entityTypeManager
      ->getListBuilder('drupal_voting_question');

    return $list_builder->render();
  }

  /**
   * Displays the voting form or redirects users who already voted.
   */
  public function vote(Question $question): array|RedirectResponse {
    if ($this->votingService->hasUserVoted($question)) {
      $this->messenger()->addWarning(
        $this->t('You have already voted on this question.')
      );

      if ($question->shouldShowResults()) {
        return $this->redirect(
          'drupal_voting.results',
          [
            'question' => $question->id(),
          ],
        );
      }

      return $this->redirect('drupal_voting.question_list');
    }

    return $this->formBuilder()->getForm(VoteForm::class, $question,);
  }

  /**
   * Title callback for results page.
   */
  public function resultsTitle(Question $question): TranslatableMarkup {
    return $this->t('Results: @question', [
      '@question' => $question->label(),
    ]);
  }

}
