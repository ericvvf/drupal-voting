<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\drupal_voting\VotingService;

/**
 * Checks whether voting is globally enabled.
 */
final class VotingEnabledAccessCheck {

  /**
   * Constructs the access checker.
   */
  public function __construct(private readonly VotingService $votingService,) {
    
  }

  /**
   * Checks access to the voting flow.
   */
  public function access(): AccessResultInterface {
    if ($this->votingService->isVotingEnabled()) {
      return AccessResult::allowed()
        ->addCacheTags(['config:drupal_voting.settings']);
    }

    return AccessResult::forbidden('Voting is globally disabled.')
      ->addCacheTags(['config:drupal_voting.settings']);
  }

}