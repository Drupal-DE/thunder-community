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
   *   A renderable array containing icon.
   */
  public function renderIcon($entity_type_id, $entity_id);

}
