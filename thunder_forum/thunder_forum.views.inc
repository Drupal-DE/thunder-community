<?php

/**
 * @file
 * Provide views data for thunder_forum.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function thunder_forum_views_data_alter(array &$data) {
  if (isset($data['taxonomy_term_field_data'])) {
    // Forum statistics.
    $data['taxonomy_term_field_data']['thunder_forum_statistics'] = [
      'title' => t('Forum statistics'),
      'help' => t('Statistics about the forum.'), // TODO
      'field' => [
        'field' => 'tid',
        'id' => 'thunder_forum_statistics',
      ],
    ];
  }
}
