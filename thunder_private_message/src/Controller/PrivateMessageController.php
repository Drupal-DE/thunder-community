<?php

namespace Drupal\thunder_private_message\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for adding private messages.
 */
class PrivateMessageController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a PrivateMessageController object.
   *
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(PrivateMessageHelperInterface $private_message_helper, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('thunder_private_message.helper'),
      $container->get('current_user')
    );
  }

  /**
   * Form constructor for the create private message form.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The sender user object.
   * @param \Drupal\Core\Session\AccountInterface|null $recipient
   *   An optional recipient user object.
   * @param \Drupal\message\MessageInterface|null $message
   *   An optional private message object, that the new message should be a
   *   reply to.
   *
   * @return array
   *   An renderable array containing the create private message form.
   */
  public function addForm(AccountInterface $user, AccountInterface $recipient = NULL, MessageInterface $message = NULL) {
    $values = [
      'template' => 'thunder_private_message',
    ];

    // Prepopulate recipient?
    if (isset($recipient)) {
      $values['tpm_recipient']['entity'] = $recipient;
    }

    // Prepopulate more values (if reply to another message).
    if (isset($message)) {
      // Subject.
      $values['tpm_title'] = $this->t('RE: @title', [
        '@title' => ltrim(preg_replace('!^' . preg_quote($this->t('RE:')) . '!i', '', $this->privateMessageHelper->getMessageSubject($message))),
      ]);

      // Message.
      $values['tpm_message'] = '<blockquote>' . $this->privateMessageHelper->getMessageBody($message) . '</blockquote>';
    }

    // Create message object.
    $message = Message::create($values);

    // Build form.
    $form = $this->entityFormBuilder()
      ->getForm($message, 'thunder_private_message_form');

    return $form;
  }

  /**
   * Access check for the create private message form.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The sender user object.
   * @param \Drupal\Core\Session\AccountInterface|null $recipient
   *   An optional recipient user object.
   * @param \Drupal\message\MessageInterface|null $message
   *   An optional private message object, that the new message should be a
   *   reply to.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   */
  public function addFormAccess(AccountInterface $user, AccountInterface $recipient = NULL, MessageInterface $message = NULL) {
    return $this->entityTypeManager()
      ->getAccessControlHandler('message')
      ->createAccess('thunder_private_message', $user, [
        'recipient' => $recipient,
        'reply_to' => $message,
      ], TRUE);
  }

  /**
   * Renders the private message inbox page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to render the inbox for.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function inbox(AccountInterface $user) {
    $build = [];

    // Build message list view.
    $build['messages'] = views_embed_view('thunder_private_messages', 'block_inbox', $user->id());

    return $build;
  }

  /**
   * Access check for the private message inbox/outbox.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   */
  public function inboxOutboxAccess(AccountInterface $user) {
    // User is allowed to use private messages?
    $user_is_allowed_to_use_private_messages = $user->hasPermission('view any message template')
      || $user->hasPermission('create any message template')
      || $user->hasPermission('create thunder_private_message message')
      || $user->hasPermission('view thunder_private_message message');

    $current_user_is_admin = $this->privateMessageHelper->userIsAllowedToBypassAccessChecks($this->currentUser);

    // Current user is the accessed user?
    $user_is_current_user = $this->currentUser->id() === $user->id();

    $access = AccessResult::allowedIf($current_user_is_admin || ($user_is_current_user && $user_is_allowed_to_use_private_messages))
      ->addCacheableDependency($this->currentUser)
      ->addCacheableDependency($user)
      ->cachePerUser()
      ->cachePerPermissions();

    return $access;
  }

  /**
   * Renders the private message page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to render the message for.
   * @param string $message_directory
   *   The name of the directory the message is in. Possible values:
   *     - 'inbox': The inbox directory.
   *     - 'outbox': The outbox directory.
   * @param \Drupal\message\MessageInterface $message
   *   The message to render.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function message(AccountInterface $user, $message_directory, MessageInterface $message) {
    return $this->entityTypeManager()
      ->getViewBuilder($message->getEntityTypeId())
      ->view($message, 'full');
  }

  /**
   * Access check for the private message page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check access for.
   * @param string $message_directory
   *   The name of the directory the message is in. Possible values:
   *     - 'inbox': The inbox directory.
   *     - 'outbox': The outbox directory.
   * @param \Drupal\message\MessageInterface $message
   *   The message to render.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   */
  public function messageAccess(AccountInterface $user, $message_directory, MessageInterface $message) {
    $access = $this->entityTypeManager()
      ->getAccessControlHandler($message->getEntityTypeId())
      ->access($message, 'view', $this->currentUser(), TRUE)
      ->addCacheContexts(['route.name']);

    switch ($message_directory) {
      case 'inbox':
        $access = $access->orIf(AccessResult::forbiddenIf(!$this->privateMessageHelper->userIsRecipient($user, $message)));
        break;

      case 'outbox':
        $access = $access->orIf(AccessResult::forbiddenIf(!$this->privateMessageHelper->userIsSender($user, $message)));
        break;
    }

    return $access;
  }

  /**
   * The _title_callback for the page that renders the private message.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message.
   *
   * @return string
   *   The translated message subject.
   */
  public function messageTitle(MessageInterface $message) {
    return $this->privateMessageHelper->getMessageSubject($message);
  }

  /**
   * Renders the private message outbox page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to render the outbox for.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function outbox(AccountInterface $user) {
    $build = [];

    // Build message list view.
    $build['messages'] = views_embed_view('thunder_private_messages', 'block_outbox', $user->id());

    return $build;
  }

}
