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
   * Constructs a new ThunderForumLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
      ]
    ];

    // Add entity to cache metadata.
    if ($entity) {
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheableDependency($entity)
        ->applyTo($build);
    }

    return $build;
  }

}
