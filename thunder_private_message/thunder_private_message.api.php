<?php

/**
 * @file
 * Hooks specific to the Thunder Private Message module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a private message.
 *
 * @param array &$links
 *   A renderable array representing the private message links.
 * @param \Drupal\message\MessageInterface $entity
 *   The private message being rendered.
 * @param array &$context
 *   Various aspects of the context in which the private message links are going
 *   to be displayed, with the following keys:
 *   - 'view_mode': the view mode in which the private message is being viewed
 *   - 'langcode': the language in which the private message is being viewed
 *   - 'location': an optional links location (e.g. to split up private message
 *     links to several link lists).
 *
 * @see \Drupal\thunder_private_message\PrivateMessageLazyBuilder::renderLinks()
 * @see \Drupal\thunder_private_message\PrivateMessageLazyBuilder::buildLinks()
 */
function hook_thunder_private_message_links_alter(array &$links, \Drupal\message\MessageInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__thunder_private_message__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'thunder-private-message-reply' => [
        'title' => t('Reply'),
        'url' => Url::fromRoute('thunder_private_message.add', [
          'user' => \Drupal::currentUser()->id(),
          'recipient' => $entity->getOwnerId(),
          'message' => $entity->id(),
        ]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
