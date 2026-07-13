<?php

declare(strict_types=1);

namespace Drupal\drupal_voting\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\drupal_voting\OptionVoteInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the optionvote entity class.
 *
 * @ContentEntityType(
 *   id = "drupal_voting_optionvote",
 *   label = @Translation("OptionVote"),
 *   label_collection = @Translation("OptionVotes"),
 *   label_singular = @Translation("optionvote"),
 *   label_plural = @Translation("optionvotes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count optionvotes",
 *     plural = "@count optionvotes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\drupal_voting\OptionVoteListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\drupal_voting\OptionVoteAccessControlHandler",
 *     "form" = {
 *      },
 *     "route_provider" = {
 *     },
 *   },
 *   base_table = "drupal_voting_optionvote",
 *   translatable = FALSE,
 *   admin_permission = "administer drupal_voting_optionvote",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *   },
 * )
 */
final class OptionVote extends ContentEntityBase implements OptionVoteInterface {

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

    $fields['question_option'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question option'))
      ->setSetting('target_type', 'drupal_voting_question_option')
      ->setRequired(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
