<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides private message helper service.
 */
class PrivateMessageHelper implements PrivateMessageHelperInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PrivateMessageHelper.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnreadCount(AccountInterface $recipient = NULL) {
    $cache =& drupal_static(get_class($this) . '::' . __METHOD__, []);
    $recipient = isset($recipient) ? $recipient : $this->currentUser;

    // Return statically cached result (if found).
    if (isset($cache[$recipient->id()])) {
      return $cache[$recipient->id()];
    }

    $entity_type = $this->entityTypeManager->getDefinition('message');
    $field_name = 'tpm_recipient';
    $bundle = 'thunder_private_message';
    $flag_id = 'message_deleted';

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager
      ->getStorage($entity_type->id());

    $table_name = $storage->getTableMapping()->getFieldTableName($field_name);
    $column_names = $storage->getTableMapping()->getColumnNames($field_name);

    // Return '0' if recipient table is strange.
    if (!isset($column_names['target_id'])) {
      return $cache[$recipient->id()] = 0;
    }

    $query = $this->database->select($table_name, 'r');

    // Join field data table.
    $query->innerJoin($entity_type->getDataTable(), 'fd', 'r.entity_id = fd.mid');

    // Join history table.
    $query->leftJoin('message_history', 'mh', 'r.entity_id = mh.mid AND mh.uid = :uid', [
      ':uid' => $recipient->id(),
    ]);

    // Join flagging table.
    $query->leftJoin('flagging', 'f', 'r.entity_id = f.entity_id AND f.entity_type = :entity_type AND f.flag_id = :flag_id AND f.uid = :uid', [
      ':entity_type' => $entity_type->id(),
      ':flag_id' => $flag_id,
      ':uid' => $recipient->id(),
    ]);

    // Add conditions to only get count for private messages where the
    // passed user is recipient, that were not flagged as deleted and are
    // unread within the specified range of HISTORY_READ_LIMIT.
    $query->condition('r.bundle', $bundle);
    $query->condition('r.' . $column_names['target_id'], $recipient->id());
    $query->condition('fd.created', \HISTORY_READ_LIMIT, '>');
    $query->isNull('f.created');
    $query->isNull('mh.timestamp');

    // Statically cache result.
    return $cache[$recipient->id()] = $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function userCanWriteMessageToOtherUser(AccountInterface $recipient, AccountInterface $sender = NULL) {
    $sender = isset($sender) ? $sender : $this->currentUser;

    // Is administrator?
    if ($sender->hasPermission('bypass thunder_private_message access') || $sender->hasPermission('administer thunder_private_message')) {
      return TRUE;
    }

    // User is allowed to write private messages?
    elseif ($sender->hasPermission('create thunder_private_message message')) {
      // Recipient allows private messages?
      return !$recipient->hasField('tpm_allow_messages') || !$recipient->tpm_allow_messages->isEmpty() || $recipient->tpm_allow_messages->value;
    }

    return FALSE;
  }

}
