<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermViewBuilder;

/**
 * Custom view builder overrides for forum terms.
 */
class ThunderForumTermViewBuilder extends TermViewBuilder {

  /**
   * Build the default links for a forum term.
   *
   * No default links are added so far, but modules may add links via
   * hook_thunder_forum_term_links_alter().
   *
   * @param \Drupal\taxonomy\TermInterface $entity
   *   The forum term object.
   * @param string $view_mode
   *   A view mode identifier.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum term links to several
   *   link lists).
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(TermInterface $entity, $view_mode, $location = NULL) {
    $links = [];

    return [
      '#theme' => 'links__thunder_forum_term' . (!empty($location) ? '__' . $location : ''),
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * Lazy builder callback; builds a forum term's links.
   *
   * @param string $taxonomy_term_entity_id
   *   The forum term entity ID.
   * @param string $view_mode
   *   The view mode in which the forum term entity is being viewed.
   * @param string $langcode
   *   The language in which the forum term entity is being viewed.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum term links to several
   *   link lists).
   *
   * @return array
   *   A renderable array representing the forum term links.
   */
  public static function renderLinks($taxonomy_term_entity_id, $view_mode, $langcode, $location = NULL) {
    $links = [
      '#theme' => 'links__thunder_forum_term' . (!empty($location) ? '__' . $location : ''),
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    $entity = Term::load($taxonomy_term_entity_id)->getTranslation($langcode);
    $links['thunder_forum'] = static::buildLinks($entity, $view_mode, $location);

    // Set up cache metadata.
    CacheableMetadata::createFromRenderArray($links)
      ->addCacheTags($entity->getCacheTags())
      ->addCacheContexts($entity->getCacheContexts())
      ->addCacheContexts(['thunder_forum_term_link_location' . (!empty($location) ? ':' . $location : '')])
      ->mergeCacheMaxAge($entity->getCacheMaxAge())
      ->applyTo($links);

    // Allow other modules to alter the term links.
    $hook_context = [
      'view_mode' => $view_mode,
      'langcode' => $langcode,
      'location' => $location,
    ];

    \Drupal::moduleHandler()->alter('thunder_forum_term_links', $links, $entity, $hook_context);

    return $links;
  }

}
