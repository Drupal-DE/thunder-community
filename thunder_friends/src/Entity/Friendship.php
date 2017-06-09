<?php

namespace Drupal\thunder_friends\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thunder_friends\FriendshipInterface;

/**
 * Defines the friendship entity.
 *
 * @ContentEntityType(
 *   id = "thunder_friendship",
 *   label = @Translation("Friendship"),
 *   label_singular = @Translation("Friendship"),
 *   label_plural = @Translation("Friendships"),
 *   label_count = @PluralTranslation(
 *     singular = "@count friendship",
 *     plural = "@count friendships"
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\thunder_friends\FriendshipViewsData"
 *   },
 *   base_table = "thunder_friendship",
 *   data_table = "thunder_friendship_field_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "uid" = "initiator",
 *     "fuid" = "friend"
 *   }
 * )
 */
class Friendship extends ContentEntityBase implements FriendshipInterface {

  use StringTranslationTrait;

  /**
   * Status value of a declined friendship request.
   */
  const FRIENDSHIP_DECLINED = 0;

  /**
   * Status value of a pending friendship request.
   */
  const FRIENDSHIP_REQUESTED = 1;

  /**
   * Status value of an accepted friendship request.
   */
  const FRIENDSHIP_APPROVED = 2;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    // User ID.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the friendship initiator.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\thunder_friends\Entity\Friendship::getCurrentUserId');
    // The friend's user ID.
    $fields['fuid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Friend user ID'))
      ->setDescription(t('The user ID of the friend.'))
      ->setSetting('target_type', 'user');

    // Status.
    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Friendship status'))
      ->setDescription(t('Status of the friendship ("requested", "approved", "declined").'))
      ->setDefaultValue(Friendship::FRIENDSHIP_REQUESTED)
      ->setSetting('unsigned', TRUE);

    // Created date.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitiator() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitiatorId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setInitiator(AccountInterface $account) {
    return $this->set('uid', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setInitiatorId($uid) {
    return $this->set('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getFriend() {
    return $this->get('fuid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFriendId() {
    return $this->get('fuid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setFriend(AccountInterface $account) {
    return $this->set('fuid', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setFriendId($uid) {
    return $this->set('fuid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    return $this->set('status', $status);
  }

}
