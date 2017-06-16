<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_forum\ThunderForumManagerInterface;

/**
 * Provides forum access record storage service.
 */
class ForumAccessRecordStorage implements ForumAccessRecordStorageInterface {

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The forum access matrix.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface
   */
  protected $forumAccessMatrix;

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ForumAccessStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface $forum_access_matrix
   *   The forum access matrix.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, ThunderForumManagerInterface $forum_manager, ForumAccessMatrixInterface $forum_access_matrix) {
    $this->database = $database;
    $this->forumAccessMatrix = $forum_access_matrix;
    $this->forumManager = $forum_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordCreate($tid) {
    $record = new ForumAccessRecord($tid, $this);

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordLoad($tid) {
    $records = $this->accessRecordLoadMultiple([$tid]);

    return isset($records[$tid]) ? $records[$tid] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordLoadMultiple(array $tids) {
    $records = [];

    // Load forum access record settings.
    $settings = $this->accessRecordSettingsLoadMultiple($tids);

    // Load forum access record user IDs.
    $users = $this->accessRecordUserIdsLoadMultiple(array_filter($tids, function ($tid) use ($settings) {
      return empty($settings[$tid]['inherit_members']) || empty($settings[$tid]['inherit_moderators']);
    }));

    // Load forum access record permissions.
    $permissions = $this->accessRecordPermissionsLoadMultiple(array_filter($tids, function ($tid) use ($settings) {
      return empty($settings[$tid]['inherit_permissions']);
    }));

    // Build result set.
    foreach ($tids as $tid) {
      // Members.
      $inherit_members = !empty($settings[$tid]['inherit_members']);
      $members = !$inherit_members && !empty($users[$tid][ForumAccessMatrixInterface::ROLE_MEMBER]) ? $users[$tid][ForumAccessMatrixInterface::ROLE_MEMBER] : [];

      // Moderators.
      $inherit_moderators = !empty($settings[$tid]['inherit_moderators']);
      $moderators = !$inherit_moderators && !empty($users[$tid][ForumAccessMatrixInterface::ROLE_MODERATOR]) ? $users[$tid][ForumAccessMatrixInterface::ROLE_MODERATOR] : [];

      // Permissions.
      $inherit_permissions = !empty($settings[$tid]['inherit_permissions']);
      $permissions = !empty($permissions[$tid]) ? $permissions[$tid] : [];

      // Create and populate forum access record.
      $record = $this->accessRecordCreate($tid)
        ->setMemberUserIds($inherit_members, $members)
        ->setModeratorUserIds($inherit_moderators, $moderators)
        ->setPermissions($inherit_permissions, $permissions);

      // Add forum access record to result.
      $records[$tid] = $record;
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordMemberUserIdsLoad($tid) {
    $users = $this->accessRecordUserIdsLoadMultiple([$tid]);

    return isset($users[$tid][ForumAccessMatrixInterface::ROLE_MEMBER]) ? $users[$tid][ForumAccessMatrixInterface::ROLE_MEMBER] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordModeratorUserIdsLoad($tid) {
    $users = $this->accessRecordUserIdsLoadMultiple([$tid]);

    return isset($users[$tid][ForumAccessMatrixInterface::ROLE_MODERATOR]) ? $users[$tid][ForumAccessMatrixInterface::ROLE_MODERATOR] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordPermissionsLoad($tid) {
    $permissions = $this->accessRecordPermissionsLoadMultiple([$tid]);

    return isset($permissions[$tid]) ? $permissions[$tid] : [];
  }

  /**
   * Return forum access record permissions for given taxonomy term IDs.
   *
   * Return value example for the configured permissions of taxonomy term with
   * ID '1' for the 'anonymous' and 'authenticated' forum roles:
   *
   * @code
   * return [
   *   1 => [
   *     'anonymous' => [
   *       'taxonomy_term' => [
   *         'view' => 'view',
   *       ],
   *     ],
   *     'authenticated' => [
   *       'taxonomy_term' => [
   *         'view' => 'view',
   *       ],
   *       'node' => [
   *         'create' => 'create',
   *         'update_own' => 'update_own',
   *       ],
   *       'thunder_forum_reply' => [
   *         'create' => 'create',
   *         'update_own' => 'update_own',
   *       ],
   *     ]
   *   ],
   * ];
   * @endcode
   *
   * @param int[] $tids
   *   The taxonomy term IDs for which the permissions are to be loaded.
   *
   * @return array
   *   A keyed array of permissions.
   */
  public function accessRecordPermissionsLoadMultiple(array $tids) {
    $cache = &drupal_static(static::STATIC_CACHE_PERMISSIONS, []);

    // Add missing entries to cache.
    if (($tids_to_load = array_diff($tids, array_keys($cache)))) {
      $result = $this->database->select('thunder_forum_access_permission', 'tfap')
        ->fields('tfap')
        ->orderBy('tid', 'ASC')
        ->orderBy('role', 'ASC')
        ->orderBy('target_entity_type_id', 'ASC')
        ->orderBy('permission', 'ASC')
        ->condition('tid', $tids_to_load, 'IN')
        ->execute()
        ->fetchAll(\PDO::FETCH_OBJ);

      // Cache results.
      foreach ($result as $row) {
        $cache[$row->tid][$row->role][$row->target_entity_type_id][$row->permission] = $row->permission;
      }
    }

    return array_intersect_key($cache, array_combine($tids, $tids));
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecordSave(ForumAccessRecordInterface $record) {
    $tids_updated = [];

    // Watch changes via database transaction.
    $transaction = $this->database->startTransaction();

    try {
      $results = [];
      // Save settings.
      $results['settings'] = $this->accessRecordSaveSettings($record, $tids_updated);

      // Save members.
      $results['members'] = $this->accessRecordSaveMemberUserIds($record, $tids_updated);

      // Save moderators.
      $results['moderators'] = $this->accessRecordSaveModeratorUserIds($record, $tids_updated);

      // Save permissions.
      $results['permissions'] = $this->accessRecordSavePermissions($record, $tids_updated);

      if (!in_array(FALSE, $results, TRUE)) {
        // Inform other modules about forum access record changes.
        if ($tids_updated) {
          $this->moduleHandler->invokeAll('thunder_forum_access_records_change', [$tids_updated]);
        }

        return TRUE;
      }

      return FALSE;
    }
    catch (\Exception $e) {
      $transaction->rollback();

      // Log error.
      watchdog_exception('forum_access_record_storage', $e);
    }

    return FALSE;
  }

  /**
   * Save given forum access record settings.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save settings for.
   * @param int[] $tids_updated
   *   If provided, it is filled with the IDs of all taxonomy terms that have
   *   been affected by this process.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record settings have
   *   been saved successfully.
   */
  protected function accessRecordSaveSettings(ForumAccessRecordInterface $record, array &$tids_updated = []) {
    // Record has no changed settings?
    if (!$record->hasChangedSettings()) {
      return TRUE;
    }

    // Save new settings.
    $result = $this->database->merge('thunder_forum_access')
      ->key(['tid' => $record->getTermId()])
      ->fields([
        'inherit_members' => (int) $record->inheritsMemberUserIds(),
        'inherit_moderators' => (int) $record->inheritsModeratorUserIds(),
        'inherit_permissions' => (int) $record->inheritsPermissions(),
      ])
      ->execute();

    // Was successful?
    if ($result) {
      // Mark term ID as updated.
      $tids_updated[$record->getTermId()] = $record->getTermId();

      // Clear static settings cache.
      drupal_static_reset(static::STATIC_CACHE_SETTINGS);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Save given forum access record member user IDs.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save member user IDs for.
   * @param int[] $tids_updated
   *   If provided, it is filled with the IDs of all taxonomy terms that have
   *   been affected by this process.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record member user IDs
   *   have been saved successfully.
   */
  protected function accessRecordSaveMemberUserIds(ForumAccessRecordInterface $record, array &$tids_updated = []) {
    // Record has no changed member user IDs?
    if (!$record->hasChangedMemberUserIds()) {
      return TRUE;
    }

    return $this->accessRecordSaveUserIds(ForumAccessMatrixInterface::ROLE_MEMBER, $record, $tids_updated);
  }

  /**
   * Save given forum access record moderator user IDs.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save moderator user IDs for.
   * @param int[] $tids_updated
   *   If provided, it is filled with the IDs of all taxonomy terms that have
   *   been affected by this process.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record moderator user
   *   IDs have been saved successfully.
   */
  protected function accessRecordSaveModeratorUserIds(ForumAccessRecordInterface $record, array &$tids_updated = []) {
    // Record has no changed moderator user IDs?
    if (!$record->hasChangedModeratorUserIds()) {
      return TRUE;
    }

    return $this->accessRecordSaveUserIds(ForumAccessMatrixInterface::ROLE_MODERATOR, $record, $tids_updated);
  }

  /**
   * Save given forum access record permissions.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save permissions for.
   * @param int[] $tids_updated
   *   If provided, it is filled with the IDs of all taxonomy terms that have
   *   been affected by this process.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record permission have
   *   been saved successfully.
   */
  protected function accessRecordSavePermissions(ForumAccessRecordInterface $record, array &$tids_updated = []) {
    // Save updated forum access record permissions (if needed).
    if ($record->hasChangedPermissions()) {
      $permissions = $record->getPermissions();

      // Load a list of all taxonomy term IDs that have to be updated.
      $tids = $this->accessRecordTermIdsAffectedByInheritance([$record->getTermId()], 'inherit_permissions');

      // Delete old permissions.
      $this->database->delete('thunder_forum_access_permission')
        ->condition('tid', $tids, 'IN')
        ->execute();

      // Save new permissions (if any).
      if ($permissions) {
        // Prepare insert query.
        $insert = $this->database->insert('thunder_forum_access_permission')
          ->fields([
            'tid',
            'role',
            'target_entity_type_id',
            'permission',
          ]);

        // Register values to insert.
        foreach ($tids as $tid) {
          foreach ($permissions as $role_name => $target_entity_types) {
            foreach ($target_entity_types as $target_entity_type_id => $perms) {
              foreach ($perms as $permission) {
                $insert->values([
                  'tid' => $tid,
                  'role' => $role_name,
                  'target_entity_type_id' => $target_entity_type_id,
                  'permission' => $permission,
                ]);
              }
            }
          }
        }

        // Insert new permissions.
        $insert->execute();
      }

      // Mark term IDs as updated.
      $tids_updated = $tids_updated += array_combine($tids, $tids);

      // Clear static permissions cache.
      drupal_static_reset(static::STATIC_CACHE_PERMISSIONS);
    }

    return TRUE;
  }

  /**
   * Save given forum access record user IDs.
   *
   * @param string $role
   *   The machine name of the forum role to save the user IDs for.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record to save user IDs for.
   * @param int[] $tids_updated
   *   If provided, it is filled with the IDs of all taxonomy terms that have
   *   been affected by this process.
   *
   * @return bool
   *   Boolean indicating whether the given forum access record user IDs have
   *   been saved successfully.
   */
  protected function accessRecordSaveUserIds($role, ForumAccessRecordInterface $record, array &$tids_updated = []) {
    // Determine role-dependent values.
    switch ($role) {
      case ForumAccessMatrixInterface::ROLE_MEMBER:
        $uids = $record->getMemberUserIds();
        $field_inheritance = 'inherit_members';
        break;

      case ForumAccessMatrixInterface::ROLE_MODERATOR:
        $uids = $record->getModeratorUserIds();
        $field_inheritance = 'inherit_moderators';
        break;

      default:
        throw new \InvalidArgumentException('The passed in role name does not exist.');
    }

    // Load a list of all taxonomy term IDs that have to be updated.
    $tids = $this->accessRecordTermIdsAffectedByInheritance([$record->getTermId()], $field_inheritance);

    // Delete old user IDs.
    $this->database->delete('thunder_forum_access_user')
      ->condition('tid', $tids, 'IN')
      ->condition('role', $role)
      ->execute();

    // Save new user IDs (if any).
    if ($uids) {
      // Prepare insert query.
      $insert = $this->database->insert('thunder_forum_access_user')
        ->fields([
          'tid',
          'uid',
          'role',
        ]);

      // Register values to insert.
      foreach ($tids as $tid) {
        foreach ($uids as $uid) {
          $insert->values([
            'tid' => $tid,
            'uid' => $uid,
            'role' => $role,
          ]);
        }
      }

      // Insert new user IDs.
      $insert->execute();
    }

    // Mark term IDs as updated.
    $tids_updated = $tids_updated += array_combine($tids, $tids);

    // Clear static users cache.
    drupal_static_reset(static::STATIC_CACHE_USERS);

    return TRUE;
  }

  /**
   * Return forum access record settings for given taxonomy term IDs.
   *
   * Return value example for the configured settings of taxonomy terms with
   * IDs '1' and '2':
   *
   * @code
   * return [
   *   1 => [
   *     'inherit_members' => 1,
   *     'inherit_moderators' => 0,
   *     'inherit_permissions' => 1,
   *   ],
   *   2 => [
   *     'inherit_members' => 1,
   *     'inherit_moderators' => 1,
   *     'inherit_permissions' => 1,
   *   ],
   * ];
   * @endcode
   *
   * @param int[] $tids
   *   The taxonomy term IDs for which the settings are to be loaded.
   *
   * @return array
   *   A keyed array of settings.
   */
  protected function accessRecordSettingsLoadMultiple(array $tids) {
    $cache = &drupal_static(static::STATIC_CACHE_SETTINGS, []);

    // Add missing entries to cache.
    if (($tids_to_load = array_diff($tids, array_keys($cache)))) {
      $cache += $this->database->select('thunder_forum_access', 'tfa')
        ->fields('tfa')
        ->orderBy('tid', 'ASC')
        ->condition('tid', $tids_to_load, 'IN')
        ->execute()
        ->fetchAllAssoc('tid', \PDO::FETCH_ASSOC);
    }

    return array_intersect_key($cache, array_combine($tids, $tids));
  }

  /**
   * Return all IDs for taxonomy terms that will be affected by inheritance.
   *
   * @param int[] $tids
   *   An array of taxonomy term IDs.
   * @param string $field_inheritance
   *   The settings table column name of the field that controls whether values
   *   are inherited.
   *
   * @return int[]
   *   The array of all affected taxonomy term IDs (including the passed ones).
   */
  protected function accessRecordTermIdsAffectedByInheritance(array $tids, $field_inheritance) {
    $tids = array_combine($tids, $tids);

    // Query child taxonomy term IDs that inherit the value controlled by the
    // inheritance field.
    $query = $this->database->select('taxonomy_term_hierarchy', 'th');
    $query->innerJoin('thunder_forum_access', 'tfa', 'th.tid = tfa.tid');
    $query->fields('th', [
      'tid',
    ]);
    $query->condition('th.parent', $tids, 'IN');
    $query->condition('tfa.' . $field_inheritance, 1);
    $child_tids = $query->execute()->fetchAllKeyed(0, 0);

    // Child term IDs found -> traverse down the tree.
    if ($child_tids) {
      $tids += $this->accessRecordTermIdsAffectedByInheritance($child_tids, $field_inheritance);
    }

    return $tids;
  }

  /**
   * Return forum access record user IDs for given taxonomy term IDs.
   *
   * Return value example for the configured user ID of taxonomy terms with IDs
   * '1' and '2':
   *
   * @code
   * return [
   *   1 => [
   *     'members' => [
   *       1 => 1,
   *       2 => 2,
   *       3 => 3,
   *     ],
   *   ],
   *   2 => [
   *     'members' => [
   *       4 => 6,
   *       5 => 5,
   *       6 => 6,
   *     ],
   *     'moderators' => [
   *       7 => 7,
   *       8 => 8,
   *       9 => 9,
   *     ],
   *   ],
   * ];
   * @endcode
   *
   * @param int[] $tids
   *   The taxonomy term IDs for which the user IDs are to be loaded.
   *
   * @return array
   *   A keyed array of user IDs grouped by forum role.
   */
  protected function accessRecordUserIdsLoadMultiple(array $tids) {
    $cache = &drupal_static(static::STATIC_CACHE_USERS, []);

    // Add missing entries to cache.
    if (($tids_to_load = array_diff($tids, array_keys($cache)))) {
      $result = $this->database->select('thunder_forum_access_user', 'tfau')
        ->fields('tfau')
        ->orderBy('tid', 'ASC')
        ->orderBy('uid', 'ASC')
        ->condition('tid', $tids_to_load, 'IN')
        ->execute()
        ->fetchAll(\PDO::FETCH_OBJ);

      // Cache results.
      foreach ($result as $row) {
        $cache[$row->tid][$row->role][$row->uid] = $row->uid;
      }
    }

    return array_intersect_key($cache, array_combine($tids, $tids));
  }

  /**
   * {@inheritdoc}
   */
  public function getForumAccessMatrixService() {
    return $this->forumAccessMatrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumManagerService() {
    return $this->forumManager;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(TermInterface $term) {
    if ($this->forumManager->isForumTerm($term)) {
      // Delete access setting record.
      $this->database->delete('thunder_forum_access')
        ->condition('tid', $term->id())
        ->execute();

      // Delete forum user records.
      $this->database->delete('thunder_forum_access_user')
        ->condition('tid', $term->id())
        ->execute();

      // Delete forum permission records.
      $this->database->delete('thunder_forum_access_permission')
        ->condition('tid', $term->id())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termInsert(TermInterface $term) {
    if ($this->forumManager->isForumTerm($term)) {
      // Save new forum access record.
      $this->accessRecordCreate($term->id())
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termPreSave(TermInterface $term) {
    if (!$term->isNew() && $this->forumManager->isForumTerm($term)) {
      // Temporarily save last known parent ID for internal use in
      // ForumAccessStorage::update() method.
      $term->__thunder_forum_access_last_parent = $this->forumManager->getParentId($term->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termUpdate(TermInterface $term) {
    if ($this->forumManager->isForumTerm($term)) {
      $parent_id = $this->forumManager->getParentId($term->id());

      // Parent ID has changed?
      if (!isset($term->__thunder_forum_access_last_parent) || !($term->__thunder_forum_access_last_parent === $parent_id)) {
        // Reset static users cache.
        drupal_static_reset(static::STATIC_CACHE_USERS);

        // Reset static permissions cache.
        drupal_static_reset(static::STATIC_CACHE_PERMISSIONS);

        // Re-save forum access record.
        $this->accessRecordLoad($term->id())
          ->save();
      }

      // Unset temporarily saved last known parent ID value.
      if ($term->__thunder_forum_access_last_parent) {
        unset($term->__thunder_forum_access_last_parent);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(AccountInterface $account) {
    // Load list of affected taxonomy term IDs.
    $tids_affected = $this->database->select('thunder_forum_access_user', 'tfau')
      ->fields('tfau', [
        'tid',
      ])
      ->condition('uid', $account->id())
      ->execute()
      ->fetchCol();

    // Inform other modules about forum access record changes.
    if ($tids_affected) {
      $this->moduleHandler->invokeAll('thunder_forum_access_records_change', [array_combine($tids_affected, $tids_affected)]);
    }

    // Delete forum user records.
    $this->database->delete('thunder_forum_access_user')
      ->condition('uid', $account->id())
      ->execute();

    // Reset static user cache.
    drupal_static_reset(static::STATIC_CACHE_USERS);
  }

}
