<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures Drupal Voting settings.
 */
final class VotingSettingsForm extends ConfigFormBase {

  /**
   * Configuration name.
   */
  private const CONFIG_NAME = 'drupal_voting.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupal_voting_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
  ): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['voting_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable voting globally'),
      '#description' => $this->t(
        'When disabled, users cannot access the voting flow through the Drupal interface or the external API.'
      ),
      '#default_value' => $config->get('voting_enabled') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $this->configFactory()
      ->getEditable(self::CONFIG_NAME)
      ->set(
        'voting_enabled',
        (bool) $form_state->getValue('voting_enabled'),
      )
      ->save();

    parent::submitForm($form, $form_state);
  }

}