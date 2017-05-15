<?php

namespace Drupal\thunder_forum;

use Drupal\forum\ForumManagerInterface;

/**
 * Provides thunder forum manager interface.
 */
interface ThunderForumManagerInterface extends ForumManagerInterface {

  /**
   * Utility method to fetch the direct ancestor forum for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent for.
   *
   * @return \Drupal\taxonomy\TermInterface[]|null
   *   The parent forum taxonomy term on success, otherwise NULL.
   */
  public function getParent($tid);

  /**
   * Utility method to fetch the direct ancestor forum ID for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent ID for.
   *
   * @return int
   *   The parent forum taxonomy term ID on success, otherwise '0'.
   */
  public function getParentId($tid);

}
