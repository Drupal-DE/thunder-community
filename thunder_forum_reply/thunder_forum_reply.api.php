<?php

/**
 * @file
 * Hooks specific to the Thunder Forum Reply module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a forum reply.
 *
 * @param array &$links
 *   A renderable array representing the forum reply links.
 * @param \Drupal\thunder_forum_reply\ForumReplyInterface $entity
 *   The forum reply being rendered.
 * @param array &$context
 *   Various aspects of the context in which the forum reply links are going to
 *   be displayed, with the following keys:
 *   - 'view_mode': the view mode in which the forum reply is being viewed
 *   - 'langcode': the language in which the forum reply is being viewed
 *   - 'location': an optional links location (e.g. to split up forum reply
 *     links to several link lists).
 *
 * @see \Drupal\thunder_forum_reply\ForumReplyLazyBuilders::renderLinks()
 * @see \Drupal\thunder_forum_reply\ForumReplyLazyBuilders::buildLinks()
 */
function hook_thunder_forum_reply_links_alter(array &$links, \Drupal\thunder_forum_reply\ForumReplyInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__thunder_forum_reply__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'thunder-forum-reply-quote' => [
        'title' => t('Quote'),
        'url' => Url::fromRoute('thunder_forum_reply.quote', [
          'node' => $entity->getRepliedNode()->id(),
          'pfrid' => $entity->id(),
          'field_name' => 'forum_replies',
        ]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
