<?php

namespace Drupal\thunder_forum_reply;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for building forum reply links on a replied forum node.
 *
 * Forum reply links include 'log in to post new reply', 'add new reply' etc.
 */
interface ForumReplyLinkBuilderInterface {

  /**
   * Builds links for the given forum node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Forum node for which the links are being built.
   * @param array $context
   *   Array of context passed from the entity view builder.
   *
   * @return array
   *   Array of entity links.
   */
  public function buildRepliedNodeLinks(NodeInterface $node, array &$context);

}
