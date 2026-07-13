<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_voting\Entity\Question;

/**
 * Form controller for the Question Option entity.
 */
final class QuestionOptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Question $drupal_voting_question = NULL, ): array {

    $form = parent::buildForm($form, $form_state);

    // Creating a new option.
    if ($this->entity->isNew() && $drupal_voting_question) {
      $this->entity->set('question', $drupal_voting_question->id());
    }

    // The question is defined by the route and should not be editable.
    if (isset($form['question'])) {
      $form['question']['#access'] = FALSE;
    }

    if ($this->entity->isNew()) {
      $form['actions']['submit']['#value'] = $this->t('Save');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {

    $is_new = $this->entity->isNew();

    $result = parent::save($form, $form_state);

    if ($is_new) {
      $this->messenger()->addStatus(
        $this->t('Answer option created successfully.')
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t('Answer option updated successfully.')
      );
    }

    $form_state->setRedirect(
      'entity.drupal_voting_question.options',
      [
        'drupal_voting_question' => $this->entity->get('question')->target_id,
      ]
    );

    return $result;
  }

}