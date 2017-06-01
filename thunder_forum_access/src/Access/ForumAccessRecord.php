<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides forum access record data object.
 */
class ForumAccessRecord implements ForumAccessRecordInterface, CacheableDependencyInterface {

  /**
   * The forum access record storage.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface
   */
  protected $forumAccessRecordStorage;

  /**
   * Whether forum member user IDs are inherited from parent.
   *
   * @var bool
   */
  protected $inheritMemberUserIds = TRUE;

  /**
   * Whether forum moderator user IDs are inherited from parent.
   *
   * @var bool
   */
  protected $inheritModeratorUserIds = TRUE;

  /**
   * Whether forum permissions are inherited from parent.
   *
   * @var bool
   */
  protected $inheritPermissions = TRUE;

  /**
   * The forum permission list.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * The forum taxonomy term ID.
   *
   * @var int
   */
  protected $tid;

  /**
   * The forum user list.
   *
   * @var array
   */
  protected $userIds = [];

  /**
   * Constructs a new ForumAccessRecord object.
   *
   * Object instantiation should be done via the forum access record storage
   * service.
   *
   * @param int $tid
   *   The forum taxonomy term ID.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface $forum_access_record_storage
   *   The forum access record storage.
   *
   * @see \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface::accessRecordCreate()
   */
  public function __construct($tid, ForumAccessRecordStorageInterface $forum_access_record_storage) {
    $this->forumAccessRecordStorage = $forum_access_record_storage;
    $this->setTermId($tid);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];
    $term = $this->getTerm();

    // Add associated forum term to cache contexts.
    if ($term) {
      $contexts = Cache::mergeContexts($contexts, $term->getCacheContexts());
    }

    // Add parents to cache contexts.
    foreach ($this->getParentTerms() as $parent) {
      $contexts = Cache::mergeContexts($contexts, $parent->getCacheContexts());
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $term = $this->getTerm();

    if ($term) {
      return Cache::mergeMaxAges($term->getCacheMaxAge());
    }

    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $term = $this->getTerm();

    // Add cache tag for top-level forum access record.
    $tags = [
      'taxonomy_term:0',
    ];

    // Add associated forum term to cache tags.
    if ($term) {
      $tags = Cache::mergeTags($tags, $term->getCacheTags());
    }

    // Add parents to cache tags.
    foreach ($this->getParentTerms() as $parent) {
      $tags = Cache::mergeTags($tags, $parent->getCacheTags());
    }

    // Add user IDs to cache tags.
    if (($uids = array_merge($this->getMemberUserIds(), $this->getModeratorUserIds()))) {
      $tags_uids = [];

      foreach ($uids as $uid) {
        $tags_uids[] = 'user:' . $uid;
      }

      $tags = Cache::mergeTags($tags, $tags_uids);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function inheritsMemberUserIds() {
    return (bool) $this->tid == 0 ? FALSE : $this->inheritMemberUserIds;
  }

  /**
   * {@inheritdoc}
   */
  public function inheritsModeratorUserIds() {
    return (bool) $this->tid == 0 ? FALSE : $this->inheritModeratorUserIds;
  }

  /**
   * {@inheritdoc}
   */
  public function inheritsPermissions() {
    return (bool) $this->tid == 0 ? FALSE : $this->inheritPermissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberUserIds() {
    if ($this->inheritsMemberUserIds()) {
      return $this->forumAccessRecordStorage->accessRecordMemberUserIdsLoad($this->getParentTermId());
    }

    return isset($this->userIds[ForumAccessMatrixInterface::ROLE_MEMBER]) ? $this->userIds[ForumAccessMatrixInterface::ROLE_MEMBER] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getModeratorUserIds() {
    if ($this->inheritsModeratorUserIds()) {
      return $this->forumAccessRecordStorage->accessRecordModeratorUserIdsLoad($this->getParentTermId());
    }

    return isset($this->userIds[ForumAccessMatrixInterface::ROLE_MODERATOR]) ? $this->userIds[ForumAccessMatrixInterface::ROLE_MODERATOR] : [];
  }

  /**
   * Return parent forums for taxonomy term associated with this record.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Array of parent terms.
   */
  protected function getParentTerms() {
    $term = $this->getTerm();
    $terms = [];

    if ($term) {
      $forum_manager = $this->forumAccessRecordStorage->getForumManagerService();

      /** @var \Drupal\taxonomy\TermInterface[] $parents */
      $terms = $forum_manager->getParents($term->id());
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentTermId() {
    $forum_manager = $this->forumAccessRecordStorage->getForumManagerService();

    return $forum_manager->getParentId($this->getTermId());
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    if ($this->inheritsPermissions()) {
      return $this->forumAccessRecordStorage->accessRecordPermissionsLoad($this->getParentTermId());
    }

    return $this->permissions;
  }

  /**
   * Return associated taxonomy tern.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The taxonomy term object or NULL if there is no entity with the given ID.
   */
  protected function getTerm() {
    return Term::load($this->getTermId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTermId() {
    return $this->tid;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChangedMemberUserIds() {
    return $this->hasChangedUserIds(ForumAccessMatrixInterface::ROLE_MEMBER);
  }

  /**
   * {@inheritdoc}
   */
  public function hasChangedModeratorUserIds() {
    return $this->hasChangedUserIds(ForumAccessMatrixInterface::ROLE_MODERATOR);
  }

  /**
   * {@inheritdoc}
   */
  public function hasChangedPermissions() {
    $original_permissions = $this->forumAccessRecordStorage->accessRecordPermissionsLoad($this->getTermId());
    $current_permissions = $this->getPermissions();

    return !($original_permissions === $current_permissions);
  }

  /**
   * Has changed user IDs?
   *
   * @return bool
   *   Boolean indicating whether the list of user IDs changed in comparison to
   *   the original forum access record.
   */
  protected function hasChangedUserIds($role) {
    switch ($role) {
      case ForumAccessMatrixInterface::ROLE_MEMBER:
        $original_uids = $this->forumAccessRecordStorage->accessRecordMemberUserIdsLoad($this->getTermId());
        $current_uids = $this->getMemberUserIds();
        break;

      case ForumAccessMatrixInterface::ROLE_MODERATOR:
        $original_uids = $this->forumAccessRecordStorage->accessRecordModeratorUserIdsLoad($this->getTermId());
        $current_uids = $this->getModeratorUserIds();
        break;

      default:
        throw new \InvalidArgumentException('The passed in role name does not exist.');
    }

    return !(count($original_uids) === count($current_uids) && count($current_uids) === count(array_intersect($current_uids, $original_uids)));
  }

  /**
   * {@inheritdoc}
   */
  public function hasChangedSettings() {
    $original = $this->forumAccessRecordStorage->accessRecordLoad($this->getTermId());

    // Is a new forum access record?
    if (!$original) {
      return TRUE;
    }

    // Has changed 'Inherit members' setting?
    elseif ($this->inheritsMemberUserIds() !== $original->inheritsMemberUserIds()) {
      return TRUE;
    }

    // Has changed 'Inherit moderators' setting?
    elseif ($this->inheritsModeratorUserIds() !== $original->inheritsModeratorUserIds()) {
      return TRUE;
    }

    // Has changed 'Inherit permissions' setting?
    elseif ($this->inheritsPermissions() !== $original->inheritsPermissions()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function roleHasPermission($role, $target_entity_type_id, $permission) {
    $permissions = $this->getPermissions();

    return isset($permissions[$role][$target_entity_type_id][$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->forumAccessRecordStorage->accessRecordSave($this);
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberUserIds($inherit, array $uids = []) {
    $this->inheritMemberUserIds = $inherit;

    if (!$this->inheritsMemberUserIds()) {
      $this->setUserIds($uids, ForumAccessMatrixInterface::ROLE_MEMBER);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setModeratorUserIds($inherit, array $uids = []) {
    $this->inheritModeratorUserIds = $inherit;

    if (!$this->inheritsModeratorUserIds()) {
      $this->setUserIds($uids, ForumAccessMatrixInterface::ROLE_MODERATOR);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermissions($inherit, array $permissions = []) {
    $this->inheritPermissions = $inherit;

    $forum_access_matrix = $this->forumAccessRecordStorage->getForumAccessMatrixService();

    if (!$this->inheritsPermissions()) {
      // Sort top-level array.
      ksort($permissions);

      foreach ($permissions as $role_name => &$target_entity_types) {
        // Validate role.
        if (!$forum_access_matrix->roleExists($role_name)) {
          throw new \InvalidArgumentException('The passed in role name does not exist.');
        }

        // Sort target entity types.
        ksort($target_entity_types);

        foreach ($target_entity_types as $target_entity_type_id => &$perms) {
          foreach ($perms as $permission) {
            // Validate permission.
            if (!$forum_access_matrix->permissionExists($target_entity_type_id, $permission)) {
              throw new \InvalidArgumentException('The passed in permission does not exist.');
            }

            // Remove invalid permissions for anonymous users.
            elseif ($forum_access_matrix->permissionIsDisabledForRole($target_entity_type_id, $permission, $role_name)) {
              unset($permissions[$role_name][$target_entity_type_id][$permission]);
            }
          }

          // Ensure correct permission structure.
          $perms = array_combine($perms, $perms);

          // Sort permissions.
          ksort($perms);
        }
      }

      $this->permissions = $permissions;
    }

    return $this;
  }

  /**
   * Set forum user IDs for specific role.
   *
   * @param int[] $uids
   *   A user ID array for users to register for the gven role.
   * @param string $role
   *   A forum role machine name.
   *
   * @throws \InvalidArgumentException
   *   When passed in role does not exist.
   */
  protected function setUserIds(array $uids, $role) {
    $forum_access_matrix = $this->forumAccessRecordStorage->getForumAccessMatrixService();

    // Validate role.
    if (!$forum_access_matrix->roleExists($role)) {
      throw new \InvalidArgumentException('The passed in role name does not exist.');
    }

    // Filter empty values (and thus remove forbidden user ID 0).
    $uids = array_filter($uids);

    // Remove duplicate values.
    $uids = array_unique($uids);

    // Sort users.
    sort($uids);

    // Set users (with appropriate role).
    $this->userIds[$role] = array_combine($uids, $uids);
  }

  /**
   * {@inheritdoc}
   */
  public function setTermId($tid) {
    $this->tid = $tid;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasPermission(AccountInterface $account, $target_entity_type_id, $permission, EntityInterface $target_entity = NULL) {
    // Always grant access for forum administrators.
    if ($this->userIsForumAdmin($account)) {
      return TRUE;
    }

    // Determine forum role and load appropriate permissions.
    $role = $this->userRole($account);
    $permissions = $this->getPermissions();
    $permissions = isset($permissions[$role][$target_entity_type_id]) ? $permissions[$role][$target_entity_type_id] : [];

    // Anonymous user.
    if ($role === ForumAccessMatrixInterface::ROLE_ANONYMOUS) {
      return isset($permissions[$permission]);
    }

    // Has permission?
    if (isset($permissions[$permission])) {
      return TRUE;
    }

    // Has '*_own' permission (if target entity is given and user is owner)?
    elseif (isset($target_entity) && $target_entity instanceof EntityOwnerInterface && $target_entity->getOwnerId() === $account->id() && in_array($permission, ['update', 'delete'], TRUE)) {
      return isset($permissions[$permission . '_own']);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumAdmin(AccountInterface $account) {
    return $account->hasPermission('administer forums');
  }

  /**
   * {@inheritdoc}
   */
  public function userRole(AccountInterface $account) {
    // Anonymous user.
    if ($account->isAnonymous()) {
      return ForumAccessMatrixInterface::ROLE_ANONYMOUS;
    }

    // Moderator.
    elseif (array_key_exists($account->id(), $this->getModeratorUserIds())) {
      return ForumAccessMatrixInterface::ROLE_MODERATOR;
    }

    // Member.
    elseif (array_key_exists($account->id(), $this->getMemberUserIds())) {
      return ForumAccessMatrixInterface::ROLE_MEMBER;
    }

    // Authenticated user.
    return ForumAccessMatrixInterface::ROLE_AUTHENTICATED;
  }

}
