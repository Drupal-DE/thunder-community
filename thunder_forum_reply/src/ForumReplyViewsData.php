<?php

namespace Drupal\thunder_forum_reply;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the forum reply entity type.
 */
class ForumReplyViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['thunder_forum_reply_field_data']['table']['base']['help'] = $this->t('Forum replies are responses to forum nodes.');
    $data['thunder_forum_reply_field_data']['table']['base']['access query tag'] = 'thunder_forum_reply_access';
    $data['thunder_forum_reply_field_data']['table']['wizard_id'] = 'thunder_forum_reply';

    // Title.
    $data['thunder_forum_reply_field_data']['title']['title'] = $this->t('Title');
    $data['thunder_forum_reply_field_data']['title']['help'] = $this->t('The title of the forum reply.');

    // Created date.
    $data['thunder_forum_reply_field_data']['created']['title'] = $this->t('Post date');
    $data['thunder_forum_reply_field_data']['created']['help'] = $this->t('Date and time of when the forum reply was created.');

    // Created date (CCYYMMDD).
    $data['thunder_forum_reply_field_data']['created_fulldata'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    // Created date (YYYYMM).
    $data['thunder_forum_reply_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    // Created date (YYYY).
    $data['thunder_forum_reply_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    // Created date (MM).
    $data['thunder_forum_reply_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    // Created date (DD).
    $data['thunder_forum_reply_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    // Created date (WW).
    $data['thunder_forum_reply_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    // Changed date.
    $data['thunder_forum_reply_field_data']['changed']['title'] = $this->t('Updated date');
    $data['thunder_forum_reply_field_data']['changed']['help'] = $this->t('Date and time of when the forum reply was last updated.');

    // Changed date (CCYYMMDD).
    $data['thunder_forum_reply_field_data']['changed_fulldata'] = [
      'title' => $this->t('Changed date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    // Changed date (YYYYMM).
    $data['thunder_forum_reply_field_data']['changed_year_month'] = [
      'title' => $this->t('Changed year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    // Changed date (YYYY).
    $data['thunder_forum_reply_field_data']['changed_year'] = [
      'title' => $this->t('Changed year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    // Changed date (MM).
    $data['thunder_forum_reply_field_data']['changed_month'] = [
      'title' => $this->t('Changed month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    // Changed date (DD).
    $data['thunder_forum_reply_field_data']['changed_day'] = [
      'title' => $this->t('Changed day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    // Changed date (WW).
    $data['thunder_forum_reply_field_data']['changed_week'] = [
      'title' => $this->t('Changed week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    // Status.
    $data['thunder_forum_reply_field_data']['status']['title'] = $this->t('Publishing status');
    $data['thunder_forum_reply_field_data']['status']['help'] = $this->t('Whether the forum reply is published.');
    $data['thunder_forum_reply_field_data']['status']['filter']['label'] = $this->t('Publishing status');
    $data['thunder_forum_reply_field_data']['status']['filter']['type'] = 'yes-no';

    // Author.
    $data['thunder_forum_reply_field_data']['uid']['title'] = $this->t('Author uid');
    $data['thunder_forum_reply_field_data']['uid']['help'] = $this->t('If you need more fields than the uid add the forum reply: author relationship');
    $data['thunder_forum_reply_field_data']['uid']['relationship']['title'] = $this->t('Author');
    $data['thunder_forum_reply_field_data']['uid']['relationship']['help'] = $this->t("The User ID of the forum reply's author.");
    $data['thunder_forum_reply_field_data']['uid']['relationship']['label'] = $this->t('author');

    // Parent reply.
    $data['thunder_forum_reply_field_data']['pfrid']['title'] = $this->t('Parent forum reply ID');
    $data['thunder_forum_reply_field_data']['pfrid']['relationship']['title'] = $this->t('Parent forum reply');
    $data['thunder_forum_reply_field_data']['pfrid']['relationship']['help'] = $this->t('The parent forum reply');
    $data['thunder_forum_reply_field_data']['pfrid']['relationship']['label'] = $this->t('parent');

    // Define the base group of this table. Fields that don't have a group
    // defined will go into this field by default.
    $data['thunder_forum_reply_node_statistics']['table']['group'] = $this->t('Forum reply statistics');

    // Last forum reply timestamp.
    $data['thunder_forum_reply_node_statistics']['last_reply_timestamp'] = [
      'title' => $this->t('Last forum reply time'),
      'help' => $this->t('Date and time of when the last forum reply was posted.'),
      'field' => [
        'id' => 'thunder_forum_reply_last_timestamp',
      ],
      'sort' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
    ];

    // Forum reply count.
    $data['thunder_forum_reply_node_statistics']['reply_count'] = [
      'title' => $this->t('Forum reply count'),
      'help' => $this->t('The number of forum replies an entity has.'),
      'field' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
    ];

    // Last updated date.
    $data['thunder_forum_reply_node_statistics']['last_updated'] = [
      'title' => $this->t('Updated/replied date'),
      'help' => $this->t('The most recent of last forum reply posted or entity updated time.'),
      'field' => [
        'id' => 'thunder_forum_reply_statistics_last_updated',
        'no group by' => TRUE,
      ],
      'sort' => [
        'id' => 'thunder_forum_reply_statistics_last_updated',
        'no group by' => TRUE,
      ],
      'filter' => [
        'id' => 'thunder_forum_reply_statistics_last_updated',
      ],
    ];

    // Last forum reply ID.
    $data['thunder_forum_reply_node_statistics']['frid'] = [
      'title' => $this->t('Last forum reply ID'),
      'help' => $this->t('Display the last forum reply of an entity'),
      'relationship' => [
        'title' => $this->t('Last forum reply'),
        'help' => $this->t('The last forum reply of an entity.'),
        'group' => $this->t('Forum reply'),
        'base' => 'thunder_forum_reply',
        'base field' => 'frid',
        'id' => 'standard',
        'label' => $this->t('Last forum reply'),
      ],
    ];

    // Last forum reply user ID.
    $data['thunder_forum_reply_node_statistics']['last_reply_uid'] = [
      'title' => $this->t('Last forum reply uid'),
      'help' => $this->t('The User ID of the author of the last forum reply of an entity.'),
      'relationship' => [
        'title' => $this->t('Last forum reply author'),
        'base' => 'users',
        'base field' => 'uid',
        'id' => 'standard',
        'label' => $this->t('Last forum reply author'),
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'field' => [
        'id' => 'numeric',
      ],
    ];

    // Forum reply field name.
    $data['thunder_forum_reply_node_statistics']['field_name'] = [
      'title' => $this->t('Forum reply field name'),
      'help' => $this->t('The field name from which the forum reply originated.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    // Provide a relationship(s) for node entity type.
    if (\Drupal::service('thunder_forum_reply.manager')->getFields()) {
      $node_entity_type = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getEntityType();

      $data['thunder_forum_reply_field_data']['node'] = [
        'relationship' => [
          'title' => $node_entity_type->getLabel(),
          'help' => $this->t('The @entity_type to which the forum reply is a response to.', ['@entity_type' => $node_entity_type->getLabel()]),
          'base' => $node_entity_type->getDataTable() ?: $node_entity_type->getBaseTable(),
          'base field' => $node_entity_type->getKey('id'),
          'relationship field' => 'nid',
          'id' => 'standard',
          'label' => $node_entity_type->getLabel(),
          'extra' => [],
        ],
      ];

      // This relationship does not use the 'field id' column, if the entity has
      // multiple forum reply fields, then this might introduce duplicates, in
      // which case the site-builder should enable aggregation and SUM the
      // reply_count field. We cannot create a relationship from the base table
      // to {thunder_forum_reply_node_statistics} for each field as multiple
      // joins between the same two tables is not supported.
      $data['thunder_forum_reply_node_statistics']['table']['join'][$node_entity_type->getDataTable() ?: $node_entity_type->getBaseTable()] = [
        'type' => 'INNER',
        'left_field' => $node_entity_type->getKey('id'),
        'field' => 'nid',
        'extra' => [],
      ];
    }

    return $data;
  }

}
