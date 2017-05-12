<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides forum access matrix service.
 */
class ForumAccessMatrix implements ForumAccessMatrixInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return [
      'taxonomy_term' => [
        'label' => $this->t('Forum'),
        'description' => $this->t('Forums are threaded discussion boards.'),
        'permissions' => [
          static::PERMISSION_VIEW => [
            'label' => $this->t('Access forum'),
            'description' => $this->t('Allow users to access the forum and all its subforums/contained content (if not configured differently on another level).'),
          ],
        ],
      ],
      'node' => [
        'label' => $this->t('Topic'),
        'description' => $this->t('Topics are threaded discussions that are posted into a forum.'),
        'permissions' => [
          static::PERMISSION_CREATE => [
            'label' => $this->t('Create topics'),
            'description' => $this->t('Allow users to create topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_UPDATE => [
            'label' => $this->t('Edit topics'),
            'description' => $this->t('Allow users to edit topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_UPDATE_OWN => [
            'label' => $this->t('Edit own topics'),
            'description' => $this->t('Allow users to edit own topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_DELETE => [
            'label' => $this->t('Delete topics'),
            'description' => $this->t('Allow users to delete topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_DELETE_OWN => [
            'label' => $this->t('Delete own topics'),
            'description' => $this->t('Allow users to delete own topics in this forum and its subforums (if not configured differently on another level).'),
          ],
        ],
      ],
      'comment' => [
        'label' => $this->t('Reply'),
        'description' => $this->t('Replies are the answers to a topic.'),
        'permissions' => [
          static::PERMISSION_CREATE => [
            'label' => $this->t('Create replies'),
            'description' => $this->t('Allow users to create replies to topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_UPDATE => [
            'label' => $this->t('Edit replies'),
            'description' => $this->t('Allow users to edit replies to topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_UPDATE_OWN => [
            'label' => $this->t('Edit own replies'),
            'description' => $this->t('Allow users to edit own replies to topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_DELETE => [
            'label' => $this->t('Delete replies'),
            'description' => $this->t('Allow users to delete replies to topics in this forum and its subforums (if not configured differently on another level).'),
          ],
          static::PERMISSION_DELETE_OWN => [
            'label' => $this->t('Delete own replies'),
            'description' => $this->t('Allow users to delete own replies to topics in this forum and its subforums (if not configured differently on another level).'),
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return [
      // Anonymous user.
      static::ROLE_ANONYMOUS => [
        'description' => $this->t('Users that are not logged in.'),
        'label' => $this->t('Anonymous user'),
      ],

      // Authenticated user.
      static::ROLE_AUTHENTICATED => [
        'description' => $this->t('Users that are registered and logged in.'),
        'label' => $this->t('Authenticated user'),
      ],

      // Member.
      static::ROLE_MEMBER => [
        'description' => $this->t('Users that are registered as a forum member.'),
        'label' => $this->t('Member'),
      ],

      // Moderator.
      static::ROLE_MODERATOR => [
        'description' => $this->t('Users that are registered as a forum moderator.'),
        'label' => $this->t('Moderator'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function permissionExists($target_entity_type_id, $permission) {
    $permissions = $this->getPermissions();

    return isset($permissions[$target_entity_type_id]['permissions'][$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function roleExists($role) {
    return in_array($role, [
      static::ROLE_ANONYMOUS,
      static::ROLE_AUTHENTICATED,
      static::ROLE_MEMBER,
      static::ROLE_MODERATOR,
    ], TRUE);
  }

}
