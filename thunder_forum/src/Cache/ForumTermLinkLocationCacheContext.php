<?php

namespace Drupal\thunder_forum\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines the ForumTermLinkLocationCacheContext service.
 *
 * This context caches forum term links per location.
 *
 * Cache context ID: 'thunder_forum_term_link_location' (to vary by all forum
 * term link locations).
 * Calculated cache context ID: 'thunder_forum_term_link_location:%key',
 * e.g.'thunder_forum_term_link_location:foo' (to vary by the 'foo' forum term
 * link location argument).
 */
class ForumTermLinkLocationCacheContext implements CalculatedCacheContextInterface {

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
    return t('Forum term links location');
  }

}
