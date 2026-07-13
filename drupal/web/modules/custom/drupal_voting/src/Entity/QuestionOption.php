<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\drupal_voting\QuestionOptionInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the question option entity class.
 *
 * @ContentEntityType(
 *   id = "drupal_voting_question_option",
 *   label = @Translation("Question Option"),
 *   label_collection = @Translation("Question Options"),
 *   label_singular = @Translation("question option"),
 *   label_plural = @Translation("question options"),
 *   label_count = @PluralTranslation(
 *     singular = "@count question options",
 *     plural = "@count question options",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\drupal_voting\QuestionOptionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\drupal_voting\QuestionOptionAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\drupal_voting\Form\QuestionOptionForm",
 *       "edit" = "Drupal\drupal_voting\Form\QuestionOptionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "drupal_voting_question_option",
 *   translatable = FALSE,
 *   admin_permission = "administer drupal_voting_question_option",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/question-option",
 *     "add-form" = "/question/{question}/options",
 *     "edit-form" = "/question-option/{drupal_voting_question_option}/edit",
 *     "delete-form" = "/question-option/{drupal_voting_question_option}/delete",
 *     "delete-multiple-form" = "/admin/content/question-option/delete-multiple",
 *   },
 * )
 */
final class QuestionOption extends ContentEntityBase implements QuestionOptionInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['question'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question'))
      ->setSetting('target_type', 'drupal_voting_question')
      ->setRequired(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Answer'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Short answer description.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner');

    $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
