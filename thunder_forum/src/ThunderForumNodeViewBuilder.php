<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeViewBuilder;

/**
 * Custom view builder overrides for forum nodes.
 */
class ThunderForumNodeViewBuilder extends NodeViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected static function buildLinks(NodeInterface $entity, $view_mode, $location = NULL) {
    $links = parent::buildLinks($entity, $view_mode);

    // Make theme location aware (if given).
    $links['#theme'] .= '__forum' . (!empty($location) ? '__' . $location : '');

    return $links;
  }

  /**
   * Lazy builder callback; builds a forum node's links.
   *
   * This is a verbatim copy of \Drupal\node\NodeViewBuilder::renderLinks() with
   * modifications to allow splitting up forum node links to different
   * locations.
   *
   * @param string $node_entity_id
   *   The forum node entity ID.
   * @param string $view_mode
   *   The view mode in which the forum node entity is being viewed.
   * @param string $langcode
   *   The language in which the forum node entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the forum node is currently being previewed.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum node links to several
   *   link lists).
   *
   * @return array
   *   A renderable array representing the forum node links.
   */
  public static function renderLinks($node_entity_id, $view_mode, $langcode, $is_in_preview, $location = NULL) {
    $links = [
      '#theme' => 'links__node__forum' . (!empty($location) ? '__' . $location : ''),
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      $entity = Node::load($node_entity_id)->getTranslation($langcode);
      $links['node'] = static::buildLinks($entity, $view_mode, $location);

      // Set up cache metadata.
      CacheableMetadata::createFromRenderArray($links)
        ->addCacheTags($entity->getCacheTags())
        ->addCacheContexts($entity->getCacheContexts())
        ->addCacheContexts(['user.permissions'])
        ->addCacheContexts(['user.roles'])
        ->addCacheContexts(['thunder_forum_node_link_location' . (!empty($location) ? ':' . $location : '')])
        ->mergeCacheMaxAge($entity->getCacheMaxAge())
        ->applyTo($links);

      // Allow other modules to alter the node links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
        'location' => $location,
      ];

      \Drupal::moduleHandler()->alter('node_links', $links, $entity, $hook_context);
    }

    return $links;
  }

}
