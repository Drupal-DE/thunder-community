<?php

/**
 * @file
 * Provide views data for thunder_forum.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function thunder_forum_views_data_alter(array &$data) {
  if (isset($data['node_field_data'])) {
    // Forum topic icon.
    $data['node_field_data']['thunder_forum_icon'] = [
      'title' => t('Forum content icon'),
      'help' => t('The forum content icon.'),
      'field' => [
        'field' => 'nid',
        'id' => 'thunder_forum_node_icon',
      ],
    ];
  }

  if (isset($data['taxonomy_term_field_data'])) {
    // Forum icon.
    $data['taxonomy_term_field_data']['thunder_forum_icon'] = [
      'title' => t('Forum icon'),
      'help' => t('The forum icon.'),
      'field' => [
        'field' => 'tid',
        'id' => 'thunder_forum_term_icon',
      ],
    ];

    // Forum statistics.
    $data['taxonomy_term_field_data']['thunder_forum_statistics'] = [
      'title' => t('Forum statistics'),
      'help' => t('Statistics about the forum.'),
      'field' => [
        'field' => 'tid',
        'id' => 'thunder_forum_statistics',
      ],
    ];

    // Last forum post.
    $data['taxonomy_term_field_data']['thunder_forum_last_post'] = [
      'title' => t('Last forum post'),
      'help' => t('Information about the last post in the forum.'),
      'field' => [
        'field' => 'tid',
        'id' => 'thunder_forum_last_post',
      ],
    ];
  }

  if (isset($data['users_field_data'])) {
    // Forum user post count.
    $data['users_field_data']['thunder_forum_user_post_count'] = [
      'title' => t('Forum user post count'),
      'help' => t('The number of forum posts a user has created.'),
      'field' => [
        'field' => 'uid',
        'id' => 'thunder_forum_user_post_count',
      ],
    ];
  }
}
