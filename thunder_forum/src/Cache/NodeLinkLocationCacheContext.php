<?php

namespace Drupal\thunder_forum\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines the NodeLinkLocationCacheContext service.
 *
 * This context caches node links per location.
 *
 * Cache context ID: 'thunder_forum_node_link_location' (to vary by all forum
 * node link locations).
 * Calculated cache context ID: 'thunder_forum_node_link_location:%key',
 * e.g.'thunder_forum_node_link_location:foo' (to vary by the 'foo' forum node
 * link location argument).
 */
class NodeLinkLocationCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    return $parameter ? $parameter : '--everywhere--';
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Node links location');
  }

}
