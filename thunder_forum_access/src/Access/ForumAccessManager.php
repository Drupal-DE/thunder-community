<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;

/**
 * Provides forum access manager service.
 */
class ForumAccessManager implements ForumAccessManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The forum access record storage.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface
   */
  protected $forumAccessRecordStorage;

  /**
   * Constructs a new ForumAccessManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface $forum_access_record_storage
   *   The forum access record storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ForumAccessRecordStorageInterface $forum_access_record_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->forumAccessRecordStorage = $forum_access_record_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumAccessRecord(TermInterface $term) {
    $record = $this->forumAccessRecordStorage
      ->accessRecordLoad($term->id());

    if (!$record) {
      throw new \Exception('Forum access record not available.');
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumMembers(TermInterface $term) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($term)->getMemberUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getForumModerators(TermInterface $term) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($term)->getModeratorUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function forumIsLocked(TermInterface $term) {
    // TODO: Implement forumIsLocked() method.
  }

  /**
   * {@inheritdoc}
   */
  public function forumIsPrivate(TermInterface $term) {
    // TODO: Implement forumIsPrivate() method.
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumMember(TermInterface $term, AccountInterface $account) {
    $members = $this->getForumAccessRecord($term)->getMemberUserIds();

    return isset($members[$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumModerator(TermInterface $term, AccountInterface $account) {
    $moderators = $this->getForumAccessRecord($term)->getModeratorUserIds();

    return isset($moderators[$account->id()]);
  }

}
