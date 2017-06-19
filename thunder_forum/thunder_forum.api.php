<?php

/**
 * @file
 * Hooks specific to the Thunder Forum module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a forum term.
 *
 * @param array &$links
 *   A renderable array representing the forum term links.
 * @param \Drupal\taxonomy\TermInterface $entity
 *   The forum term being rendered.
 * @param array &$context
 *   Various aspects of the context in which the forum term links are going to
 *   be displayed, with the following keys:
 *   - 'view_mode': the view mode in which the forum term is being viewed
 *   - 'langcode': the language in which the forum term is being viewed
 *   - 'location': an optional links location (e.g. to split up forum term links
 *     to several link lists).
 *
 * @see \Drupal\thunder_forum\ThunderForumTermViewBuilder::renderLinks()
 * @see \Drupal\thunder_forum\ThunderForumTermViewBuilder::buildLinks()
 */
function hook_thunder_forum_term_links_alter(array &$links, \Drupal\taxonomy\TermInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__thunder_forum_term__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'thunder-forum-term-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('thunder_forum_term_test.report', ['taxonomy_term' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("forum/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
