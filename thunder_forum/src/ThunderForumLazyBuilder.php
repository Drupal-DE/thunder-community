<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a service for Thunder Forum #lazy_builder callbacks.
 */
class ThunderForumLazyBuilder implements ThunderForumLazyBuilderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a new ThunderForumLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ThunderForumManagerInterface $forum_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->forumManager = $forum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function renderIcon($entity_type_id, $entity_id) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->load($entity_id);

    // Build forum icon.
    $build = [
      '#theme' => 'thunder_forum_icon__' . $entity_type_id,
      '#entity' => $entity,
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];

    // Add entity to cache metadata.
    if ($entity) {
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheableDependency($entity)
        ->applyTo($build);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderUserPostCount($uid) {
    $statistics = $this->forumManager->getUserStatistics($uid);
    $count = 0;

    if (isset($statistics->sum_count)) {
      $count += $statistics->sum_count;
    }

    $build = [
      '#markup' => $count,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderUserRank($uid, $nid = NULL) {
    $build = [
      '#theme' => 'thunder_forum_user_rank',
      '#context' => [
        'user' => $this->entityTypeManager->getStorage('user')->load($uid),
        'node' => isset($nid) ? $this->entityTypeManager->getStorage('node')->load($nid) : NULL,
      ],
      '#user_is_admin' => FALSE,
      '#user_is_moderator' => FALSE,
      '#statistics' => $this->forumManager->getUserStatistics($uid),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderUserRankName($uid, $nid = NULL) {
    $build = [
      '#theme' => 'thunder_forum_user_rank_name',
      '#context' => [
        'user' => $this->entityTypeManager->getStorage('user')->load($uid),
        'node' => isset($nid) ? $this->entityTypeManager->getStorage('node')->load($nid) : NULL,
      ],
      '#user_is_admin' => FALSE,
      '#user_is_moderator' => FALSE,
      '#statistics' => $this->forumManager->getUserStatistics($uid),
    ];

    return $build;
  }

}
