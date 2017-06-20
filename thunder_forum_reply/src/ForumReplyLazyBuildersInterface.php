<?php

namespace Drupal\thunder_forum_reply;

/**
 * Interface for forum reply #lazy_builder callback services.
 */
interface ForumReplyLazyBuildersInterface {

  /**
   * Lazy builder callback; builds the forum reply form.
   *
   * @param int $nid
   *   The replied forum node ID.
   * @param string $field_name
   *   The forum reply field name.
   *
   * @return array
   *   A renderable array containing the forum reply form.
   */
  public function renderForm($nid, $field_name);

  /**
   * Lazy builder callback; builds a forum reply's links.
   *
   * @param string $reply_entity_id
   *   The forum reply entity ID.
   * @param string $view_mode
   *   The view mode in which the forum reply entity is being viewed.
   * @param string $langcode
   *   The language in which the forum reply entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the forum reply entity is currently being previewed.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum reply links to several
   *   link lists).
   *
   * @return array
   *   A renderable array representing the forum reply links.
   */
  public function renderLinks($reply_entity_id, $view_mode, $langcode, $is_in_preview, $location = NULL);

}
