<?php

namespace Drupal\thunder_forum;

/**
 * Interface for Thunder Forum #lazy_builder callback services.
 */
interface ThunderForumLazyBuilderInterface {

  /**
   * Lazy builder callback; render icon.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only forum entities are supported:
   *     - Forum taxonomy terms.
   *     - Forum topic nodes.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return array
   *   A renderable array containing the icon.
   */
  public function renderIcon($entity_type_id, $entity_id);

  /**
   * Lazy builder callback; render post count for user.
   *
   * @param int $uid
   *   The user ID to render the post count for.
   * @param string|null $theme_suggestion_suffix
   *   An optional prefix added to the theme call to allow suggestions based on
   *   context.
   *
   * @return array
   *   A renderable array containing the user's post count.
   */
  public function renderUserPostCount($uid, $theme_suggestion_suffix = NULL);

  /**
   * Lazy builder callback; render user rank.
   *
   * @param int $uid
   *   The user ID to render the rank for.
   * @param int|null $nid
   *   An optional node ID for the context the user rank is displayed in.
   *
   * @return array
   *   A renderable array containing the user's rank.
   */
  public function renderUserRank($uid, $nid = NULL);

  /**
   * Lazy builder callback; render user rank name.
   *
   * @param int $uid
   *   The user ID to render the rank name for.
   * @param int|null $nid
   *   An optional node ID for the context the user rank name is displayed in.
   *
   * @return array
   *   A renderable array containing the user's rank name.
   */
  public function renderUserRankName($uid, $nid = NULL);

}
