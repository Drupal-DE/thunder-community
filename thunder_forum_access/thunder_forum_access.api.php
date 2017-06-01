<?php

/**
 * @file
 * Hooks provided by the Thunder Forum Access module.
 */

use Drupal\Core\Cache\Cache;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * React on forum access record changes.
 *
 * @param int[] $tids
 *   An array of taxonomy term IDs for all affected forum taxonomy terms.
 *
 * @ingroup thunder_forum_access
 */
function hook_thunder_forum_access_records_change(array $tids) {
  $tags = [];

  // Build list of cache tags for affected forum taxonomy terms.
  foreach ($tids as $tid) {
    $tags[] = 'taxonomy_term:' . $tid;
  }

  // Invalidate cache records for affected forum taxonomy terms.
  Cache::invalidateTags($tags);
}

/**
 * @} End of "addtogroup hooks".
 */
