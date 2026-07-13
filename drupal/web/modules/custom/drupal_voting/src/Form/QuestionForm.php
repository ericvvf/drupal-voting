<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the question entity edit forms.
 */
final class QuestionForm extends ContentEntityForm {


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    if (isset($form['identifier']['widget'][0]['value'])) {
      $identifier = &$form['identifier']['widget'][0]['value'];
      $identifier['#type'] = 'machine_name';

      $identifier['#machine_name'] = [
        'exists' => [
          $this,
          'identifierExists',
        ],
        'source' => [
          'label',
          'widget',
          0,
          'value',
        ],
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ];

    }

    if ($this->entity->isNew()) {
      $form['actions']['submit']['#value'] = $this->t('Save and Configure Answer Options');
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
        $this->t('Question created successfully.')
      );

      $form_state->setRedirect(
        'entity.drupal_voting_question.options',
        [
          'drupal_voting_question' => $this->entity->id(),
        ]
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t('Question updated successfully.')
      );

      $form_state->setRedirect('entity.drupal_voting_question.collection');
    }

    return $result;
  }

  /**
   * Checks whether a Question identifier already exists.
   */
  public function identifierExists(string $identifier): bool {
    $query = $this->entityTypeManager
      ->getStorage('drupal_voting_question')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('identifier', $identifier);

    if (!$this->entity->isNew()) {
      $query->condition('id', $this->entity->id(), '<>');
    }

    return (bool) $query->range(0, 1)->count()->execute();
  }

}
