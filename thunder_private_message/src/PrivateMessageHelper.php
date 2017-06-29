<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\message\MessageInterface;

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
  public function getMessageBody(MessageInterface $message) {
    if ($message->hasField('tpm_message') && !$message->get('tpm_message')->isEmpty()) {
      return $message->get('tpm_message')->first()->value;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageRecipient(MessageInterface $message) {
    if ($message->hasField('tpm_recipient') && isset($message->get('tpm_recipient')->entity)) {
      $recipient = $message->get('tpm_recipient')->first()->entity;

      return $recipient->isAuthenticated() ? $recipient : NULL;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageSubject(MessageInterface $message) {
    if ($message->hasField('tpm_title') && !$message->get('tpm_title')->isEmpty()) {
      return $message->get('tpm_title')->first()->value;
    }

    return NULL;
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
    $flag_id = 'thunder_private_message_deleted';

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
  public function isUnreadMessage(MessageInterface $message, AccountInterface $account) {
    if ($this->userIsRecipient($account, $message)) {
      $query = $this->database->select('message_field_data', 'm');
      $query->leftJoin('message_history', 'h', 'm.mid = h.mid AND h.uid = :uid', [':uid' => $account->id()]);
      $query->addExpression('COUNT(m.mid)', 'count');

      return $query
        ->condition('m.mid', $message->id())
        ->condition('m.created', HISTORY_READ_LIMIT, '>')
        ->isNull('h.mid')
        ->execute()
        ->fetchField() > 0;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanWriteMessageToOtherUser(AccountInterface $recipient, AccountInterface $sender = NULL) {
    $sender = isset($sender) ? $sender : $this->currentUser;

    // Users try to write message to themselves?
    if ($sender->id() === $recipient->id()) {
      return FALSE;
    }

    // Is administrator?
    elseif ($this->userIsAllowedToBypassAccessChecks($sender)) {
      return TRUE;
    }

    // User is allowed to write private messages?
    elseif ($sender->hasPermission('create any message template') || $sender->hasPermission('create thunder_private_message message')) {
      // Recipient allows private messages?
      return !$recipient->hasField('tpm_allow_messages') || !empty($recipient->tpm_allow_messages->value);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userIsAllowedToBypassAccessChecks(AccountInterface $account) {
    // @todo Fix 'adminster messages' permission string when fixed in message
    // module.
    return $account->hasPermission('adminster messages')
      || $account->hasPermission('bypass message access control')
      || $account->hasPermission('bypass thunder_private_message access')
      || $account->hasPermission('administer thunder_private_message');
  }

  /**
   * {@inheritdoc}
   */
  public function userIsRecipient(AccountInterface $user, MessageInterface $message) {
    if (($recipient = $this->getMessageRecipient($message))) {
      return $recipient->id() === $user->id();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userIsSender(AccountInterface $user, MessageInterface $message) {
    return $message->getOwnerId() === $user->id();
  }

}
