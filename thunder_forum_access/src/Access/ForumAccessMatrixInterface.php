<?php

namespace Drupal\thunder_forum_access\Access;

/**
 * Provides forum access matrix interface.
 */
interface ForumAccessMatrixInterface {

  /**
   * Forum permission machine name: Create.
   */
  const PERMISSION_CREATE = 'create';

  /**
   * Forum permission machine name: Delete.
   */
  const PERMISSION_DELETE = 'delete';

  /**
   * Forum permission machine name: Delete own.
   */
  const PERMISSION_DELETE_OWN = 'delete_own';

  /**
   * Forum permission machine name: Update.
   */
  const PERMISSION_UPDATE = 'update';

  /**
   * Forum permission machine name: Update own.
   */
  const PERMISSION_UPDATE_OWN = 'update_own';

  /**
   * Forum permission machine name: View.
   */
  const PERMISSION_VIEW = 'view';

  /**
   * Forum role machine name: Anonymous user.
   *
   * @var string
   */
  const ROLE_ANONYMOUS = 'anonymous';

  /**
   * Forum role: Authenticated user.
   *
   * @var string
   */
  const ROLE_AUTHENTICATED = 'authenticated';

  /**
   * Forum role: Member.
   *
   * @var string
   */
  const ROLE_MEMBER = 'member';

  /**
   * Forum role: Moderator.
   *
   * @var string
   */
  const ROLE_MODERATOR = 'moderator';

  /**
   * Return list of predefined forum permissions.
   *
   * @return array
   *   A keyed array of predefined forum permission information. The key is the
   *   ID of the entity type the permission is for, the value is a keyed array
   *   with the following items:
   *     - description: A brief description for the entity type.
   *     - label: The title to use for the entity type.
   *     - permissions: A keyed array of permissions for the specific target
   *       entity type. The key is the machine name, the value is a keyed array
   *       with the following items:
   *         - description: A brief description for the permission.
   *         - label: The human-readable permission label.
   */
  public function getPermissions();

  /**
   * Return list of predefined forum roles.
   *
   * @return array
   *   A keyed array of predefined forum roles. The key is the machine name, the
   *   value is a keyed array with the following items:
   *     - description: A brief description for the role.
   *     - label: The human-readable role label.
   */
  public function getRoles();

  /**
   * Return whether forum permission exists.
   *
   * @param string $target_entity_type_id
   *   An ID for the entity type the permission is for.
   * @param string $permission
   *   A forum permission machine name.
   *
   * @return bool
   *   Boolean indicating whether the given forum permission exists.
   */
  public function permissionExists($target_entity_type_id, $permission);

  /**
   * Return whether forum role exists.
   *
   * @param string $role
   *   A forum role machine name.
   *
   * @return bool
   *   Boolean indicating whether the given forum role exists.
   */
  public function roleExists($role);

}
