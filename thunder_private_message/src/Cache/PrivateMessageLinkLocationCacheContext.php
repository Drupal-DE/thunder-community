<?php

namespace Drupal\thunder_private_message\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines the PrivateMessageLinkLocationCacheContext service.
 *
 * This context caches private message links per location.
 *
 * Cache context ID: 'thunder_private_message_link_location' (to vary by all
 * private message link locations).
 * Calculated cache context ID: 'thunder_private_message_link_location:%key',
 * e.g.'thunder_private_message_link_location:foo' (to vary by the 'foo' private
 * message link location argument).
 */
class PrivateMessageLinkLocationCacheContext implements CalculatedCacheContextInterface {

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
    return t('Private message links location');
  }

}
