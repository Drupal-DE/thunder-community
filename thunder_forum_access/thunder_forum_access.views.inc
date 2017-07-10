<?php

/**
 * @file
 * Provide views data for thunder_forum_access.module.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data().
 */
function thunder_forum_access_views_data_alter(array &$data) {
  foreach (Index::loadMultiple() as $index) {
    $key = "search_api_index_{$index->id()}";

    if (empty($data[$key])) {
      continue;
    }

    $data[$key]['thunder_forum_access_search_api_forum_access'] = [
      'title' => t('Forum access'),
      'help' => t('Special handler to limit search results based on forum term access'),
      'filter' => [
        'id' => 'thunder_forum_access_search_api_forum_access',
        'vocabulary' => \Drupal::config('forum.settings')->get('vocabulary'),
      ],
    ];
  }

  return $data;
}
