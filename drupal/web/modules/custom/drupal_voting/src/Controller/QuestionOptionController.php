<?php

namespace Drupal\drupal_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_voting\Entity\Question;

class QuestionOptionController extends ControllerBase {

  public function listing(Question $drupal_voting_question): array {

    return [
      '#markup' => '<h2>TODO - List Answer Options</h2>',
    ];

  }

}