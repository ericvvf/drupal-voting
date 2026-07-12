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
 *       "add" = "Drupal\drupal_voting\Form\OptionVoteForm",
 *       "edit" = "Drupal\drupal_voting\Form\OptionVoteForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\drupal_voting\Routing\OptionVoteHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "drupal_voting_optionvote",
 *   admin_permission = "administer drupal_voting_optionvote",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/optionvote",
 *     "add-form" = "/optionvote/add",
 *     "canonical" = "/optionvote/{drupal_voting_optionvote}",
 *     "edit-form" = "/optionvote/{drupal_voting_optionvote}",
 *     "delete-form" = "/optionvote/{drupal_voting_optionvote}/delete",
 *     "delete-multiple-form" = "/admin/content/optionvote/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.drupal_voting_optionvote.settings",
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the optionvote was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the optionvote was last edited.'));

    return $fields;
  }

}
