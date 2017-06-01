<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides forum access record data object interface.
 */
interface ForumAccessRecordInterface {

  /**
   * Return whether the forum inherits its members from its parent.
   *
   * @return bool
   *   Boolean indicating whether the forum inherits its member list from its
   *   parent (this is always FALSE for term ID '0').
   */
  public function inheritsMemberUserIds();

  /**
   * Return whether the forum inherits its moderators from its parent.
   *
   * @return bool
   *   Boolean indicating whether the forum inherits its moderator list from its
   *   parent (this is always FALSE for term ID '0').
   */
  public function inheritsModeratorUserIds();

  /**
   * Return whether the forum inherits its permissions from its parent.
   *
   * @return bool
   *   Boolean indicating whether the forum inherits its permission list from
   *   its parent (this is always FALSE for term ID '0').
   */
  public function inheritsPermissions();

  /**
   * Return forum member user IDs.
   *
   * @return int[]
   *   A user ID array for users registered as members.
   */
  public function getMemberUserIds();

  /**
   * Return forum moderator user IDs.
   *
   * @return int[]
   *   A user ID array for users registered as moderators.
   */
  public function getModeratorUserIds();

  /**
   * Return forum taxonomy term ID of the direct ancestor.
   *
   * @return int
   *   The forum taxonomy term ID of the direct ancestor.
   */
  public function getParentTermId();

  /**
   * Return forum permissions.
   *
   * Return value example for the configured permissions for the 'anonymous' and
   * 'authenticated' forum roles:
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
   *     'thunder_forum_reply' => [
   *       'create' => 'create',
   *       'update_own' => 'update_own',
   *     ],
   *   ],
   * ];
   * @endcode
   *
   * @return array
   *   A keyed array of permissions.
   */
  public function getPermissions();

  /**
   * Return forum taxonomy term ID.
   *
   * @return int
   *   The forum taxonomy term ID.
   */
  public function getTermId();

  /**
   * Has changed member user IDs?
   *
   * @return bool
   *   Boolean indicating whether the list of member user IDs changed in
   *   comparison to the original forum access record.
   */
  public function hasChangedMemberUserIds();

  /**
   * Has changed moderator user IDs?
   *
   * @return bool
   *   Boolean indicating whether the list of moderator user IDs changed in
   *   comparison to the original forum access record.
   */
  public function hasChangedModeratorUserIds();

  /**
   * Has changed permissions?
   *
   * @return bool
   *   Boolean indicating whether the list of permissions changed in comparison
   *   to the original forum access record.
   */
  public function hasChangedPermissions();

  /**
   * Has changed settings?
   *
   * @return bool
   *   Boolean indicating whether the settings changed in comparison to the
   *   original forum access record.
   */
  public function hasChangedSettings();

  /**
   * Forum role has permission?
   *
   * @param string $role
   *   The forum role machine name.
   * @param string $target_entity_type_id
   *   The ID of the entity type the permission is for.
   * @param string $permission
   *   The permission machine name.
   *
   * @return bool
   *   Boolean indicating whether the given role has the permission.
   */
  public function roleHasPermission($role, $target_entity_type_id, $permission);

  /**
   * Save forum access record to storage.
   *
   * @return bool
   *   Boolean indicating whether the forum access record has been saved
   *   successfully.
   */
  public function save();

  /**
   * Set forum member user IDs.
   *
   * @param bool $inherit
   *   Whether to inherit forum member user ID list from parent.
   * @param int[] $uids
   *   A user ID array for users to register as members (this parameter is
   *   ignored if $inherit parameter is set to TRUE).
   *
   * @return static
   */
  public function setMemberUserIds($inherit, array $uids = []);

  /**
   * Set forum moderator user IDs.
   *
   * @param bool $inherit
   *   Whether to inherit forum moderator user ID list from parent.
   * @param int[] $uids
   *   A user ID array for users to register as moderators (this parameter is
   *   ignored if $inherit parameter is set to TRUE).
   *
   * @return static
   */
  public function setModeratorUserIds($inherit, array $uids = []);

  /**
   * Set forum permissions.
   *
   * Parameter example for the configured permissions of the given taxonomy term
   * for the 'anonymous' and 'authenticated' forum roles:
   *
   * @code
   * $permissions = [
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
   *     'thunder_forum_reply' => [
   *       'create' => 'create',
   *       'update_own' => 'update_own',
   *     ],
   *   ],
   * ];
   * @endcode
   *
   * @param bool $inherit
   *   Whether to inherit forum permissions from parent.
   * @param array $permissions
   *   A keyed array of permissions.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   When passed in role(s) or permission(s) do not exist.
   */
  public function setPermissions($inherit, array $permissions = []);

  /**
   * Set forum taxonomy term ID.
   *
   * @param int $tid
   *   The forum taxonomy term ID.
   *
   * @return static
   */
  public function setTermId($tid);

  /**
   * User has permission?
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   * @param string $target_entity_type_id
   *   The ID of the entity type the permission is for.
   * @param string $permission
   *   The permission machine name.
   * @param \Drupal\Core\Entity\EntityInterface|null $target_entity
   *   An optional entity the permission is for.
   *
   * @return bool
   *   Boolean indicating whether the given user has the permission.
   */
  public function userHasPermission(AccountInterface $account, $target_entity_type_id, $permission, EntityInterface $target_entity = NULL);

  /**
   * User is forum administrator?
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account object.
   *
   * @return bool
   *   Whether the given user is an administrator of the specified forum (based
   *   on 'administer forums' permission).
   */
  public function userIsForumAdmin(AccountInterface $account);

  /**
   * Return forum role for given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   *
   * @return string
   *   The forum role machine name.
   *
   * @see \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface::ROLE_ANONYMOUS
   * @see \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface::ROLE_AUTHENTICATED
   * @see \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface::ROLE_MEMBER
   * @see \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface::ROLE_MODERATOR
   */
  public function userRole(AccountInterface $account);

}
