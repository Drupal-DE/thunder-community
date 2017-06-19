<?php

namespace Drupal\thunder_forum;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\forum\ForumManager;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager service.
 */
class ThunderForumManager extends ForumManager implements ThunderForumManagerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ThunderForumManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager service.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, Connection $connection, TranslationInterface $string_translation, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory, $entity_manager, $connection, $string_translation, $comment_manager);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumStatistics($tid) {
    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      return parent::getForumStatistics($tid);
    }

    if (empty($this->forumStatistics)) {
      // Prime the statistics.
      $query = $this->connection->select('node_field_data', 'n');
      $query->join('thunder_forum_reply_node_statistics', 'frns', "n.nid = frns.nid AND frns.field_name = 'forum_replies'");
      $query->join('forum', 'f', 'n.vid = f.vid');
      $query->addExpression('COUNT(n.nid)', 'topic_count');
      $query->addExpression('SUM(frns.reply_count)', 'comment_count');

      $this->forumStatistics = $query
        ->fields('f', [
          'tid',
        ])
        ->condition('n.status', 1)
        ->condition('n.default_langcode', 1)
        ->groupBy('tid')
        ->addTag('node_access')
        ->execute()
        ->fetchAllAssoc('tid');
    }

    if (!empty($this->forumStatistics[$tid])) {
      return $this->forumStatistics[$tid];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForumTermByNode(NodeInterface $node) {
    $field_name = 'taxonomy_forums';

    // Is forum node type and has forum taxonomy term?
    if ($this->checkNodeType($node) && !$node->get($field_name)->isEmpty()) {
      return $node->get($field_name)->first()->entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPost($tid) {
    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      return parent::getLastPost($tid);
    }

    if (!empty($this->lastPostData[$tid])) {
      return $this->lastPostData[$tid];
    }

    // Query "Last Post" information for this forum.
    $query = $this->connection->select('node_field_data', 'n');
    $query->join('forum', 'f', 'n.vid = f.vid AND f.tid = :tid', [':tid' => $tid]);
    $query->join('thunder_forum_reply_node_statistics', 'frns', "n.nid = frns.nid AND frns.field_name = 'forum_replies'");
    $query->join('users_field_data', 'u', 'frns.last_reply_uid = u.uid AND u.default_langcode = 1');
    $query->addField('n', 'nid');
    $query->addField('u', 'name', 'last_reply_name');

    $topic = $query
      ->fields('frns', [
        'frid',
        'last_reply_timestamp',
        'last_reply_uid',
      ])
      ->condition('n.status', 1)
      ->orderBy('last_reply_timestamp', 'DESC')
      ->range(0, 1)
      ->addTag('node_access')
      ->execute()
      ->fetchObject();

    // Build the last post information.
    $last_post = new \stdClass();

    if (!empty($topic->last_reply_timestamp)) {
      $last_post->created = $topic->last_reply_timestamp;
      $last_post->name = $topic->last_reply_name;
      $last_post->uid = $topic->last_reply_uid;

      // Add more information about last post entity.
      $last_post->entity_id = $topic->frid ? $topic->frid : $topic->nid;
      $last_post->entity_type_id = $topic->frid ? 'thunder_forum_reply' : 'node';
    }

    $this->lastPostData[$tid] = $last_post;

    return $last_post;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent($tid) {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityManager
      ->getStorage('taxonomy_term');

    if (($parents = $term_storage->loadParents($tid))) {
      return reset($parents);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId($tid) {
    $parent = $this->getParent($tid);

    return $parent ? $parent->id() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopics($tid, AccountInterface $account) {
    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      return parent::getTopics($tid, $account);
    }

    $config = $this->configFactory->get('forum.settings');
    $forum_per_page = $config->get('topics.page_limit');
    $sortby = $config->get('topics.order');

    $header = [
      ['data' => $this->t('Topic'), 'field' => 'f.title'],
      ['data' => $this->t('Replies'), 'field' => 'f.comment_count'],
      ['data' => $this->t('Last reply'), 'field' => 'f.last_comment_timestamp'],
    ];

    $order = $this->getTopicOrder($sortby);
    for ($i = 0; $i < count($header); $i++) {
      if ($header[$i]['field'] == $order['field']) {
        $header[$i]['sort'] = $order['sort'];
      }
    }

    $query = $this->connection->select('forum_index', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->fields('f')
      ->condition('f.tid', $tid)
      ->addTag('node_access')
      ->addMetaData('base_table', 'forum_index')
      ->orderBy('f.sticky', 'DESC')
      ->orderByHeader($header)
      ->limit($forum_per_page);

    $count_query = $this->connection->select('forum_index', 'f');
    $count_query->condition('f.tid', $tid);
    $count_query->addExpression('COUNT(*)');
    $count_query->addTag('node_access');
    $count_query->addMetaData('base_table', 'forum_index');

    $query->setCountQuery($count_query);
    $result = $query->execute();

    $nids = [];
    foreach ($result as $record) {
      $nids[] = $record->nid;
    }

    if ($nids) {
      $nodes = $this->entityManager->getStorage('node')->loadMultiple($nids);

      $query = $this->connection->select('node_field_data', 'n')
        ->extend('Drupal\Core\Database\Query\TableSortExtender');
      $query->addField('n', 'nid');
      $query->join('thunder_forum_reply_node_statistics', 'frns', "n.nid = frns.nid AND frns.field_name = 'forum_replies'");
      $query->fields('frns', [
        'frid',
      ]);
      $query->addField('frns', 'last_reply_uid', 'last_comment_uid');
      $query->addField('frns', 'last_reply_timestamp', 'last_comment_timestamp');
      $query->addField('frns', 'reply_count', 'comment_count');

      $query->join('forum_index', 'f', 'f.nid = n.nid');
      $query->addField('f', 'tid', 'forum_tid');

      $query->join('users_field_data', 'u', 'n.uid = u.uid AND u.default_langcode = 1');
      $query->addField('u', 'name');

      $query->join('users_field_data', 'u2', 'frns.last_reply_uid = u2.uid AND u.default_langcode = 1');
      $query->addField('u2', 'name', 'last_comment_name');

      $query
        ->fields('u2', [
          'name',
        ])
        ->orderBy('f.sticky', 'DESC')
        ->orderByHeader($header)
        ->condition('n.nid', $nids, 'IN')
        // @todo This should be actually filtering on the desired node language
        //   and just fall back to the default language.
        ->condition('n.default_langcode', 1);

      $result = [];
      foreach ($query->execute() as $row) {
        $topic = $nodes[$row->nid];
        $topic->comment_mode = $topic->forum_replies->status;

        foreach ($row as $key => $value) {
          $topic->{$key} = $value;
        }

        $result[] = $topic;
      }
    }
    else {
      $result = [];
    }

    $topics = [];
    $first_new_found = FALSE;

    /** @var \Drupal\thunder_forum_reply\ForumReplyManagerInterface $forum_reply_manager */
    $forum_reply_manager = \Drupal::service('thunder_forum_reply.manager');

    foreach ($result as $topic) {
      if ($account->isAuthenticated()) {
        // A forum is new if the topic is new, or if there are new forum replies
        // since the user's last visit.
        if ($topic->forum_tid != $tid) {
          $topic->new = 0;
        }
        else {
          $history = $this->lastVisit($topic->id(), $account);

          // This special variable handling of new_replies is needed, because
          // template_preprocess_forums() would throw an error otherwise due to
          // the missing forum comment field.
          $topic->new_replies = 0;
          $topic->_new_replies = $forum_reply_manager->getCountNewReplies($topic, 'forum_replies', $history);
          $topic->new = $topic->_new_replies || ($topic->last_comment_timestamp > $history);
        }
      }
      else {
        // Do not track "new replies" status for topics if the user is
        // anonymous.
        $topic->new_replies = 0;
        $topic->new = 0;
      }

      // Make sure only one topic is indicated as the first new topic.
      $topic->first_new = FALSE;
      if ($topic->new != 0 && !$first_new_found) {
        $topic->first_new = TRUE;
        $first_new_found = TRUE;
      }

      if ($topic->comment_count > 0) {
        $last_reply = new \stdClass();
        $last_reply->created = $topic->last_comment_timestamp;
        $last_reply->name = $topic->last_comment_name;
        $last_reply->uid = $topic->last_comment_uid;
        $last_reply->entity_id = $topic->frid;
        $last_reply->entity_type_id = 'thunder_forum_reply';
        $topic->last_reply = $last_reply;
      }

      $topics[$topic->id()] = $topic;
    }

    return [
      'topics' => $topics,
      'header' => $header,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isForumContainer(TermInterface $term) {
    return $this->isForumTerm($term) && $term->hasField('forum_container') && !empty($term->forum_container->value);
  }

  /**
   * {@inheritdoc}
   */
  public function isForumTerm(TermInterface $term) {
    return $term->bundle() === $this->configFactory->get('forum.settings')->get('vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function isForumTermForm($form_id) {
    return in_array($form_id, [
      'taxonomy_term_forums_forum_form',
      'taxonomy_term_forums_container_form',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function isHotTopic(NodeInterface $node) {
    $hot_threshold = $this->configFactory->get('forum.settings')->get('topics.hot_threshold');

    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      $query = $this->connection->select('comment_entity_statistics', 'ces')
        ->fields('ces', [
          'comment_count',
        ])
        ->condition('ces.entity_id', $node->id())
        ->condition('ces.entity_type', 'node')
        ->condition('ces.field_name', 'comment_forum');

      $comment_count = $query->execute()->fetchField();

      return $comment_count > $hot_threshold;
    }

    $query = $this->connection->select('thunder_forum_reply_node_statistics', 'frns')
      ->fields('frns', [
        'reply_count',
      ])
      ->condition('frns.nid', $node->id())
      ->condition('frns.field_name', 'forum_replies');

    $reply_count = $query->execute()->fetchField();

    return $reply_count > $hot_threshold;
  }

  /**
   * {@inheritdoc}
   */
  public function isTopicWithNewReplies(NodeInterface $node, AccountInterface $account) {
    if ($account->isAnonymous()) {
      return FALSE;
    }

    $history = $this->lastVisit($node->id(), $account);

    // Forum reply entity type is not used instead of comments?
    if (!$this->moduleHandler->moduleExists('thunder_forum_reply')) {
      return $this->commentManager->getCountNewComments($node, 'comment_forum', $history) > 0;
    }

    /** @var \Drupal\thunder_forum_reply\ForumReplyManagerInterface $forum_reply_manager */
    $forum_reply_manager = \Drupal::service('thunder_forum_reply.manager');

    return $forum_reply_manager->getCountNewReplies($node, 'forum_replies', $history) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnreadTopic(NodeInterface $node, AccountInterface $account) {
    $query = $this->connection->select('node_field_data', 'n');
    $query->leftJoin('history', 'h', 'n.nid = h.nid AND h.uid = :uid', [':uid' => $account->id()]);
    $query->addExpression('COUNT(n.nid)', 'count');

    return $query
      ->condition('n.nid', $node->id())
      ->condition('n.created', HISTORY_READ_LIMIT, '>')
      ->isNull('h.nid')
      ->execute()
      ->fetchField() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function unreadTopics($term, $uid) {
    $vid = $this->configFactory->get('forum.settings')->get('vocabulary');

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityManager->getStorage('taxonomy_term');

    $tids = [];
    foreach ($term_storage->loadTree($vid, $term) as $item) {
      $tids[$item->tid] = $item->tid;
    }

    if (empty($tids)) {
      return parent::unreadTopics($term, $uid);
    }

    $query = $this->connection->select('node_field_data', 'n');
    $query->join('forum', 'f', 'n.vid = f.vid AND f.tid IN (:tids[])', [':tids[]' => $tids]);
    $query->leftJoin('history', 'h', 'n.nid = h.nid AND h.uid = :uid', [':uid' => $uid]);
    $query->addExpression('COUNT(n.nid)', 'count');

    return $query
      ->condition('status', 1)
      // @todo This should be actually filtering on the desired node status
      //   field language and just fall back to the default language.
      ->condition('n.default_langcode', 1)
      ->condition('n.created', HISTORY_READ_LIMIT, '>')
      ->isNull('h.nid')
      ->addTag('node_access')
      ->execute()
      ->fetchField();
  }

}
