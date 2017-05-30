<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\forum\ForumIndexStorage;
use Drupal\node\NodeInterface;
use Drupal\thunder_forum_reply\ForumReplyInterface;

/**
 * Provides Thunder forum index storage service.
 */
class ThunderForumIndexStorage extends ForumIndexStorage {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ThunderForumIndexStorage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $database, ThunderForumManagerInterface $forum_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($database);

    $this->forumManager = $forum_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(NodeInterface $node) {
    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      return parent::updateIndex($node);
    }

    $nid = $node->id();

    $count = $this->database->query("SELECT COUNT(frid) FROM {thunder_forum_reply_field_data} fr INNER JOIN {forum_index} i ON fr.nid = i.nid WHERE fr.nid = :nid AND fr.field_name = 'forum_replies' AND fr.status = :status AND fr.default_langcode = 1", [
      ':nid' => $nid,
      ':status' => ForumReplyInterface::PUBLISHED,
    ])->fetchField();

    // Forum replies exist.
    if ($count > 0) {
      $last_reply = $this->database->queryRange("SELECT frid, created, uid FROM {thunder_forum_reply_field_data} WHERE nid = :nid AND field_name = 'forum_replies' AND status = :status AND default_langcode = 1 ORDER BY frid DESC", 0, 1, [
        ':nid' => $nid,
        ':status' => ForumReplyInterface::PUBLISHED,
      ])->fetchObject();

      $this->database->update('forum_index')
        ->fields( array(
          'comment_count' => $count,
          'last_comment_timestamp' => $last_reply->created,
        ))
        ->condition('nid', $nid)
        ->execute();
    }

    // Forum replies do not exist.
    else {
      // @todo This should be actually filtering on the desired node language
      $this->database->update('forum_index')
        ->fields( array(
          'comment_count' => 0,
          'last_comment_timestamp' => $node->getCreatedTime(),
        ))
        ->condition('nid', $nid)
        ->execute();
    }
  }

}
