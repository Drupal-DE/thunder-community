<?php

/**
 * @file
 * Provide views data for thunder_forum_reply_history.module.
 */

/**
 * Implements hook_views_data().
 */
function thunder_forum_reply_history_views_data() {
  $data['thunder_forum_reply_history']['table']['group'] = t('Forum reply');

  // Explain how this table joins to others.
  $data['thunder_forum_reply_history']['table']['join'] = [
    // Directly links to forum reply table.
    'thunder_forum_reply_field_data' => [
      'table' => 'thunder_forum_reply_history',
      'left_field' => 'frid',
      'field' => 'frid',
      'extra' => [
        ['field' => 'uid', 'value' => '***CURRENT_USER***', 'numeric' => TRUE],
      ],
    ],
  ];

  $data['thunder_forum_reply_history']['timestamp'] = [
    'title' => t('Is new forum reply'),
    'field' => [
      'id' => 'thunder_forum_reply_history_timestamp',
      'help' => t('Show a marker if the forum reply is new or updated.'),
    ],
    'filter' => [
      'help' => t('Show only forum replies that are new or updated.'),
      'id' => 'thunder_forum_reply_history_timestamp',
    ],
  ];

  return $data;
}
