<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
  public function getForumAccessRecord($tid) {
    $record = $this->forumAccessRecordStorage
      ->accessRecordLoad($tid);

    if (!$record) {
      throw new \Exception('Forum access record not available.');
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumMembers($tid) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($tid)->getMemberUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getForumModerators($tid) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($tid)->getModeratorUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumAdmin(AccountInterface $account) {
    return $this->getForumAccessRecord(0)->userIsForumAdmin($account);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumMember($tid, AccountInterface $account) {
    $members = $this->getForumAccessRecord($tid)->getMemberUserIds();

    return isset($members[$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumModerator($tid, AccountInterface $account) {
    $moderators = $this->getForumAccessRecord($tid)->getModeratorUserIds();

    return isset($moderators[$account->id()]);
  }

}
