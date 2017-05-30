<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for forum replies.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for forum reply entities.
 */
class ForumReplyStorage extends SqlContentEntityStorage implements ForumReplyStorageInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a ForumReplyStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, AccountInterface $current_user, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('thunder_forum_reply_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ForumReplyInterface $reply) {
    return $this->database->query('SELECT COUNT(*) FROM {thunder_forum_reply_field_revision} WHERE frid = :frid AND default_langcode = 1', [':frid' => $reply->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ForumReplyInterface $reply) {
    return $this->database->query(
      'SELECT vid FROM {thunder_forum_reply_revision} WHERE frid=:frid ORDER BY vid',
      [':frid' => $reply->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {thunder_forum_reply_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOrdinal(ForumReplyInterface $reply, $divisor = 1) {
    // Count how many forum replies (fr1) are before $reply (fr2) in display
    // order. This is the 0-based display ordinal.
    $query = $this->database->select('thunder_forum_reply_field_data', 'fr1');
    $query->innerJoin('thunder_forum_reply_field_data', 'fr2', 'fr2.nid = fr1.nid AND fr2.field_name = fr1.field_name');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('fr2.frid', $reply->id());

    // @todo This might has to be be improved for 'view own unpublished forum
    // replies' permission.
    if (!$this->currentUser->hasPermission('administer forums')) {
      $query->condition('fr1.status', ForumReplyInterface::PUBLISHED);
    }

    // For rendering flat forum replies, frid is used for ordering forum replies
    // due to unpredictable behavior with timestamp, so we make the same
    // assumption here.
    $query->condition('fr1.frid', $reply->id(), '<');

    // Ensure default language.
    $query->condition('fr1.default_langcode', 1);
    $query->condition('fr2.default_langcode', 1);

    // Add metadata to query.
    $query->addTag('entity_access');
    $query->addTag('thunder_forum_reply_access');
    $query->addMetaData('base_table', 'thunder_forum_reply');
    $query->addMetaData('entity', $reply);
    $query->addMetaData('field_name', $reply->getFieldName());

    $ordinal = $query->execute()->fetchField();

    return ($divisor > 1) ? floor($ordinal / $divisor) : $ordinal;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewReplyPageNumber($total_replies, $new_replies, NodeInterface $node, $field_name) {
    $field = $node->getFieldDefinition($field_name);
    $replies_per_page = $field->getSetting('per_page');

    // Only one page of forum replies.
    if ($total_replies <= $replies_per_page) {
      $count = 0;
    }
    else {
      $count = $total_replies - $new_replies;
    }

    return $replies_per_page > 0 ? (int) ($count / $replies_per_page) : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildReplyIds(array $replies) {
    return $this->database->select('thunder_forum_reply_field_data', 'fr')
      ->fields('fr', ['frid'])
      ->condition('pfrid', array_keys($replies), 'IN')
      ->condition('default_langcode', 1)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadThread(NodeInterface $node, $field_name, $replies_per_page = 0, $pager_id = 0) {
    $query = $this->database->select('thunder_forum_reply_field_data', 'fr');
    $query->addField('fr', 'frid');

    $query
      ->condition('fr.nid', $node->id())
      ->condition('fr.field_name', $field_name)
      ->condition('fr.default_langcode', 1)
      ->addTag('entity_access')
      ->addTag('thunder_forum_reply_access')
      ->addMetaData('base_table', 'thunder_forum_reply')
      ->addMetaData('entity', $node)
      ->addMetaData('field_name', $field_name);

    if ($replies_per_page) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($replies_per_page);

      if ($pager_id) {
        $query->element($pager_id);
      }

      $count_query = $this->database->select('thunder_forum_reply_field_data', 'fr');
      $count_query->addExpression('COUNT(*)');

      $count_query
        ->condition('fr.nid', $node->id())
        ->condition('fr.field_name', $field_name)
        ->condition('fr.default_langcode', 1)
        ->addTag('entity_access')
        ->addTag('thunder_forum_reply_access')
        ->addMetaData('base_table', 'thunder_forum_reply')
        ->addMetaData('entity', $node)
        ->addMetaData('field_name', $field_name);
      $query->setCountQuery($count_query);
    }

    // Narrow result based on publishing status and permissions.
    if (!$this->currentUser->hasPermission('administer forums')) {
      $condition_published = new Condition('OR');
      $condition_published->condition('fr.status', ForumReplyInterface::PUBLISHED);

      $condition_own_unpublished = new Condition('AND');
      $condition_own_unpublished->condition('fr.uid', $this->currentUser->id());
      $condition_own_unpublished->condition('1', intval($this->currentUser->hasPermission('view own unpublished forum replies')));

      $condition_published->condition($condition_own_unpublished);

      $query->condition($condition_published);

      if ($replies_per_page) {
        $count_query->condition($condition_published);
      }
    }

    $query->orderBy('fr.frid', 'ASC');

    $frids = $query->execute()->fetchCol();

    $replies = [];
    if ($frids) {
      $replies = $this->loadMultiple($frids);
    }

    return $replies;
  }

}
