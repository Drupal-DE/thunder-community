<?php

namespace Drupal\thunder_forum_reply\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines the ForumReplyLinkLocationCacheContext service.
 *
 * This context caches forum reply links per location.
 *
 * Cache context ID: 'thunder_forum_reply_link_location' (to vary by all forum
 * reply link locations).
 * Calculated cache context ID: 'thunder_forum_reply_link_location:%key',
 * e.g.'thunder_forum_reply_link_location:foo' (to vary by the 'foo' forum reply
 * link location argument).
 */
class ForumReplyLinkLocationCacheContext implements CalculatedCacheContextInterface {

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
    return t('Forum reply links location');
  }

}
