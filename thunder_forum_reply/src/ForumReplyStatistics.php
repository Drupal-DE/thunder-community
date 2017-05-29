<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines a service for storing and retrieving forum reply statistics.
 */
class ForumReplyStatistics implements ForumReplyStatisticsInterface {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the ForumReplyStatistics service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Connection $database, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function read(array $nodes, $accurate = TRUE) {
    $options = $accurate ? [] : ['target' => 'replica'];

    $stats = $this->database->select('thunder_forum_reply_node_statistics', 'frs', $options)
      ->fields('frs')
      ->condition('frs.nid', array_keys($nodes), 'IN')
      ->execute();

    $statistics_records = [];

    while ($entry = $stats->fetchObject()) {
      $statistics_records[] = $entry;
    }

    return $statistics_records;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(NodeInterface $node) {
    $this->database->delete('thunder_forum_reply_node_statistics')
      ->condition('nid', $node->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function create(NodeInterface $node, array $fields) {
    $query = $this->database->insert('thunder_forum_reply_node_statistics')
      ->fields([
        'nid',
        'field_name',
        'frid',
        'last_reply_timestamp',
        'last_reply_uid',
        'reply_count',
      ]);

    foreach ($fields as $field_name => $detail) {
      // Skip fields that forum node does not have.
      if (!$node->hasField($field_name)) {
        continue;
      }

      // Get the user ID from the entity if it's set, or default to the
      // currently logged in user.
      $last_reply_uid = 0;

      if ($node instanceof EntityOwnerInterface) {
        $last_reply_uid = $node->getOwnerId();
      }

      // Default to current user when entity does not implement
      // EntityOwnerInterface or author is not set.
      if (!isset($last_reply_uid)) {
        $last_reply_uid = $this->currentUser->id();
      }

      // Default to REQUEST_TIME when entity does not have a changed property.
      $last_reply_timestamp = REQUEST_TIME;

      // @todo Make forum reply statistics language aware.
      if ($node instanceof EntityChangedInterface) {
        $last_reply_timestamp = $node->getChangedTimeAcrossTranslations();
      }

      $query->values([
        'nid' => $node->id(),
        'field_name' => $field_name,
        'frid' => 0,
        'last_reply_timestamp' => $last_reply_timestamp,
        'last_reply_uid' => $last_reply_uid,
        'reply_count' => 0,
      ]);
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumCount() {
    return $this->database->query('SELECT MAX(reply_count) FROM {thunder_forum_reply_node_statistics}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function update(ForumReplyInterface $reply) {
    // Allow bulk updates and inserts to temporarily disable the maintenance of
    // the {thunder_forum_reply_node_statistics} table.
    if (!$this->state->get('thunder_forum_reply.maintain_node_statistics')) {
      return;
    }

    $query = $this->database->select('thunder_forum_reply_field_data', 'fr');
    $query->addExpression('COUNT(frid)');
    $count = $query->condition('fr.nid', $reply->getRepliedNodeId())
      ->condition('fr.field_name', $reply->getFieldName())
      ->condition('fr.status', ForumReplyInterface::PUBLISHED)
      ->condition('default_langcode', 1)
      ->execute()
      ->fetchField();

    // Forum replies exist.
    if ($count > 0) {
      $last_reply = $this->database->select('thunder_forum_reply_field_data', 'fr')
        ->fields('fr', [
          'frid',
          'changed',
          'uid',
        ])
        ->condition('fr.nid', $reply->getRepliedNodeId())
        ->condition('fr.field_name', $reply->getFieldName())
        ->condition('fr.status', ForumReplyInterface::PUBLISHED)
        ->condition('default_langcode', 1)
        ->orderBy('fr.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      // Use merge here because forum node could be created before forum reply
      // field.
      $this->database->merge('thunder_forum_reply_node_statistics')
        ->fields([
          'frid' => $last_reply->frid,
          'reply_count' => $count,
          'last_reply_timestamp' => $last_reply->changed,
          'last_reply_uid' => $last_reply->uid,
        ])
        ->keys([
          'nid' => $reply->getRepliedNodeId(),
          'field_name' => $reply->getFieldName(),
        ])
        ->execute();
    }

    // Forum replies do not exist.
    else {
      $node = $reply->getRepliedNode();

      // Get the user ID from the entity if it's set, or default to the
      // currently logged in user.
      if ($node instanceof EntityOwnerInterface) {
        $last_reply_uid = $node->getOwnerId();
      }

      // Default to current user when entity does not implement
      // EntityOwnerInterface or author is not set.
      if (!isset($last_reply_uid)) {
        $last_reply_uid = $this->currentUser->id();
      }

      $this->database->update('thunder_forum_reply_node_statistics')
        ->fields([
          'frid' => 0,
          'reply_count' => 0,
          // Use the changed date of the entity if it's set, or default to
          // REQUEST_TIME.
          'last_reply_timestamp' => ($node instanceof EntityChangedInterface) ? $node->getChangedTimeAcrossTranslations() : REQUEST_TIME,
          'last_reply_uid' => $last_reply_uid,
        ])
        ->condition('nid', $reply->getRepliedNodeId())
        ->condition('field_name', $reply->getFieldName())
        ->execute();
    }

    // Reset the cache of the replied forum node, so that when the entity is
    // loaded the next time, the statistics will be loaded again.
    $this->entityTypeManager->getStorage('node')
      ->resetCache([$reply->getRepliedNodeId()]);
  }

}
