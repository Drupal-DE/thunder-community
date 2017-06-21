<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_forum\ThunderForumManagerInterface;

/**
 * Forum reply manager contains common functions to manage forum reply fields.
 */
class ForumReplyManager implements ForumReplyManagerInterface {

  use StringTranslationTrait;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Construct the ForumReplyManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, Connection $connection, QueryFactory $query_factory, ThunderForumManagerInterface $forum_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->connection = $connection;
    $this->currentUser = $current_user;
    $this->entityManager = $entity_manager;
    $this->forumManager = $forum_manager;
    $this->moduleHandler = $module_handler;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $map = $this->entityManager->getFieldMapByFieldType('thunder_forum_reply');

    return isset($map['node']) ? $map['node'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountNewReplies(ContentEntityInterface $entity, $field_name = NULL, $timestamp = 0) {
    // Only perform this check for authenticated users.
    if ($this->currentUser->isAuthenticated()) {
      switch ($entity->getEntityTypeId()) {
        case 'node':
          return $this->getCountNewRepliesForNode($entity, $field_name, $timestamp);

        case 'taxonomy_term':
          return $this->getCountNewRepliesForTerm($entity, $field_name, $timestamp);
      }
    }

    return FALSE;
  }

  /**
   * Returns number of new forum replies on a given forum node for a user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The forum node to get the new replies count for.
   * @param string $field_name
   *   (optional) The field_name to count forum replies for. Defaults to any
   *   field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to time of last user access the
   *   forum node.
   *
   * @return int
   *   The number of new forum replies.
   *
   * @see static::getCountNewReplies()
   */
  protected function getCountNewRepliesForNode(NodeInterface $node, $field_name, $timestamp) {
    $query = $this->connection->select('thunder_forum_reply_field_data', 'fr');
    $query->addExpression('COUNT(fr.frid)', 'count');
    $query->condition('fr.nid', $node->id());
    $query->addTag('entity_access')
      ->addTag('thunder_forum_reply_access')
      ->addMetaData('base_table', 'thunder_forum_reply')
      ->addMetaData('entity', $node);

    // Limit to a particular field.
    if ($field_name) {
      $query->condition('fr.field_name', $field_name);
      $query->addMetaData('field_name', $field_name);
    }

    // Check forum reply history for number of unread replies (if module is
    // enabled).
    if ($this->moduleHandler->moduleExists('thunder_forum_reply_history')) {
      if (!$timestamp) {
        $timestamp = HISTORY_READ_LIMIT;
      }

      $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

      $query->leftJoin('thunder_forum_reply_history', 'h', 'fr.frid = h.frid AND h.uid = :uid', [':uid' => $this->currentUser->id()]);

      $count = $query
        ->condition('fr.created', $timestamp, '>')
        ->isNull('h.frid')
        ->execute()
        ->fetchField();

      return $count > 0;
    }

    // @todo Replace module handler with optional history service injection
    //   after https://www.drupal.org/node/2081585.
    elseif ($this->moduleHandler->moduleExists('history')) {
      // Retrieve the timestamp at which the current user last viewed this
      // forum node.
      if (!$timestamp) {
        $timestamp = history_read($node->id());
      }

      $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

      // Use the timestamp to retrieve the number of new forum replies.
      $count = $query
        ->condition('fr.created', $timestamp, '>')
        ->condition('fr.status', ForumReplyInterface::PUBLISHED)
        ->execute()
        ->fetchField();

      return $count;
    }

    return 0;
  }

  /**
   * Returns number of new forum replies on a given forum term for a user.
   *
   * This queries the whole forum tree below the passed forum term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The forum term to get the new replies count for.
   * @param string $field_name
   *   (optional) The field_name to count forum replies for. Defaults to any
   *   field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to HISTORY_READ_LIMIT.
   *
   * @return int
   *   The number of new forum replies.
   *
   * @see static::getCountNewReplies()
   */
  protected function getCountNewRepliesForTerm(TermInterface $term, $field_name, $timestamp) {
    $tids = $this->forumManager->getChildTermIds($term->id());
    $tids[$term->id()] = $term->id();

    if (!$timestamp) {
      $timestamp = HISTORY_READ_LIMIT;
    }

    // Create dummy node for potential access query alters.
    $node = Node::create([
      'type' => 'forum',
      'taxonomy_forums' => ['entity' => $term],
    ]);

    $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

    $query = $this->connection->select('forum_index', 'fi');
    $query->condition('fi.tid', $tids, 'IN')
      ->condition('fr.created', $timestamp, '>');
    $query->innerJoin('thunder_forum_reply_field_data', 'fr', 'fi.nid = fr.nid');
    $query->addExpression('COUNT(fr.frid)', 'count');

    if ($field_name) {
      // Limit to a particular field.
      $query->condition('fr.field_name', $field_name);
      $query->addMetaData('field_name', $field_name);
    }

    $query->addTag('entity_access')
      ->addTag('node_access')
      ->addTag('thunder_forum_reply_access')
      ->addTag('views_forum_topics')
      ->addMetaData('base_table', 'thunder_forum_reply')
      ->addMetaData('entity', $node);

    // Check forum reply history for number of unread replies (if module is
    // enabled).
    if (1 == 0 && $this->moduleHandler->moduleExists('thunder_forum_reply_history')) {
      $query->leftJoin('thunder_forum_reply_history', 'h', 'fr.frid = h.frid AND h.uid = :uid', [':uid' => $this->currentUser->id()]);

      $count = $query
        ->isNull('h.frid')
        ->execute()
        ->fetchField();

      return $count;
    }

    // @todo Replace module handler with optional history service injection
    //   after https://www.drupal.org/node/2081585.
    elseif ($this->moduleHandler->moduleExists('history')) {
      $query->leftJoin('history', 'h', 'fr.nid = h.nid AND h.uid = :uid', [':uid' => $this->currentUser->id()]);

      $or = $query->orConditionGroup()
        ->isNull('h.nid')
        ->where('h.timestamp < fr.created');

      $count = $query
        ->condition($or)
        ->execute()
        ->fetchField();

      return $count;
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnreadReply(ForumReplyInterface $reply, AccountInterface $account) {
    if ($this->moduleHandler->moduleExists('thunder_forum_reply_history') && $this->currentUser->isAuthenticated()) {
      $query = $this->connection->select('thunder_forum_reply_field_data', 'fr');
      $query->leftJoin('thunder_forum_reply_history', 'h', 'fr.frid = h.frid AND h.uid = :uid', [':uid' => $account->id()]);
      $query->addExpression('COUNT(fr.frid)', 'count');

      return $query
        ->condition('fr.frid', $reply->id())
        ->condition('fr.created', HISTORY_READ_LIMIT, '>')
        ->isNull('h.frid')
        ->execute()
        ->fetchField() > 0;
    }

    return FALSE;
  }

}
