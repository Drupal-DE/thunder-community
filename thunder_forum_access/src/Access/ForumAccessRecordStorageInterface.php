<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides forum access record storage interface.
 */
interface ForumAccessRecordStorageInterface {

  /**
   * Static cache name: Forum permissions.
   */
  const STATIC_CACHE_PERMISSIONS = 'ForumAccessRecordStorageInterface::permissions';

  /**
   * Static cache name: Forum access settings.
   */
  const STATIC_CACHE_SETTINGS = 'ForumAccessRecordStorageInterface::settings';

  /**
   * Static cache name: Forum users.
   */
  const STATIC_CACHE_USERS = 'ForumAccessRecordStorageInterface::users';

  /**
   * Return new forum access record for given taxonomy term ID.
   *
   * @param int $tid
   *   The taxonomy term ID for which the record is to be created.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface
   *   The forum access record on success.
   */
  public function accessRecordCreate($tid);

  /**
   * Return forum access record for given taxonomy term ID.
   *
   * @param int $tid
   *   The taxonomy term ID for which the record is to be loaded.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface|null
   *   The forum access record on success, otherwise NULL.
   */
  public function accessRecordLoad($tid);

  /**
   * Return multiple forum access records for given taxonomy term IDs.
   *
   * @param int[] $tids
   *   The taxonomy term IDs for which the records are to be loaded.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface[]
   *   The forum access records.
   */
  public function accessRecordLoadMultiple(array $tids);

  /**
   * Return forum access record member user IDs for given taxonomy term ID.
   *
   * @param int $tid
   *   The taxonomy term ID for which the member user IDs are to be loaded.
   *
   * @return int[]
   *   An array containing forum access record member user IDs.
   */
  public function accessRecordMemberUserIdsLoad($tid);

  /**
   * Return forum access record moderator user IDs for given taxonomy term ID.
   *
   * @param int $tid
   *   The taxonomy term ID for which the moderator user IDs are to be loaded.
   *
   * @return int[]
   *   An array containing forum access record moderator user IDs.
   */
  public function accessRecordModeratorUserIdsLoad($tid);

  /**
   * Return forum access record permissions for given taxonomy term ID.
   *
   * Return value example for the configured permissions of the given taxonomy
   * term for the 'anonymous' and 'authenticated' forum roles:
   *
   * @code
   * return [
   *   'anonymous' => [
   *     'taxonomy_term' => [
   *       'view' => 'view',
   *     ],
   *   ],
   *   'authenticated' => [
   *     'taxonomy_term' => [
   *       'view' => 'view',
   *     ],
   *     'node' => [
   *       'create' => 'create',
   *       'update_own' => 'update_own',
   *     ],
   *     'comment' => [
   *       'create' => 'create',
   *       'update_own' => 'update_own',
   *     ],
   *   ],
   * ];
   * @endcode
   *
   * @param int $tid
   *   The taxonomy term ID for which the permissions are to be loaded.
   *
   * @return array
   *   A keyed array of permissions (if any).
   */
  public function accessRecordPermissionsLoad($tid);

  /**
   * Save given forum access record.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record has been saved
   *   successfully.
   */
  public function accessRecordSave(ForumAccessRecordInterface $record);

  /**
   * Return forum access matrix service.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface
   *   The forum access matrix service object.
   */
  public function getForumAccessMatrixService();

  /**
   * Return forum manager service.
   *
   * @return \Drupal\thunder_forum\ThunderForumManagerInterface
   *   The forum manager service object.
   */
  public function getForumManagerService();

  /**
   * Deletes the forum access record for given taxonomy term.
   *
   * This has to be called in hook_taxonomy_term_delete() implementations.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term for which the record is to be deleted.
   */
  public function termDelete(TermInterface $term);

  /**
   * Inserts the forum access record for given taxonomy term.
   *
   * This has to be called in hook_taxonomy_term_insert() implementations.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term for which the record is to be inserted.
   */
  public function termInsert(TermInterface $term);

  /**
   * Performs forum access-related pre-save operations on given taxonomy term.
   *
   * This has to be called in hook_taxonomy_term_presave() implementations.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term for which the pre-save operation should be performed.
   */
  public function termPreSave(TermInterface $term);

  /**
   * Updates the forum access record for given taxonomy term.
   *
   * This has to be called in hook_taxonomy_term_update() implementations.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term for which the record is to be updated.
   */
  public function termUpdate(TermInterface $term);

  /**
   * Deletes the forum access record for a given user.
   *
   * This has to be called in hook_user_delete() implementations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which the record is to be deleted.
   */
  public function userDelete(AccountInterface $account);

}
