<?php

namespace Drupal\thunder_friends\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Relationship plugin for thunder_friendship.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("thunder_friendship")
 */
class Friendship extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->definition['other field'])) {
      // Nothing to do here.
      parent::query();
      return;
    }

    // Figure out what base table this relationship brings to the party.
    $table_data = Views::viewsData()->get($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensureMyTable();

    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $base_field;
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = $this->realField;
    $def['adjusted'] = TRUE;

    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    // Create first join.
    $join = Views::pluginManager('join')->createInstance($id, $def);
    // Create second join.
    $def['left_field'] = $this->definition['other field'];
    $join_other = Views::pluginManager('join')->createInstance($id, $def);

    // Use a short alias for this.
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);
    $this->query->addRelationship($alias, $join_other, $this->definition['base'], $this->relationship);

    // Add some meta information to make altering the query easier.
    $this->query->addTag('thunder_friendship_relation');
    $this->query->addTag('self_or_friend');

    // Add access tags if the base table provide it.
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->addTag($access_tag);
    }
  }

}
