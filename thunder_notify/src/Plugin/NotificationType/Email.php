<?php

namespace Drupal\thunder_notify\Plugin\NotificationType;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\thunder_notify\NotificationTypeBase;
use Egulias\EmailValidator\EmailValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Email notification type.
 *
 * @NotificationType(
 *   id = "email",
 *   label = @Translation("Email"),
 *   message_tokens = {
 *     "notifications": @Translation("Messages from notification sources")
 *   }
 * )
 */
class Email extends NotificationTypeBase {

  /**
   * The email manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EmailValidatorInterface $email_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $messages, array $replacements = []) {
    $email = $replacements['{user:mail}'];
    if (!$this->emailValidator->isValid($email)) {
      return FALSE;
    }
    // Build email params.
    $params = [
      'subject' => $this->buildSubject(),
      'message' => $this->buildMessage(),
    ];
    // Replace tokens in message.
    $replacements += [
      '{messages}' => implode("\n", $messages),
    ];
    $params['subject'] = strtr($params['subject'], $replacements);
    $params['message'] = strtr($params['message'], $replacements);

    // Send the email.
    $this->mailManager->mail('thunder_notify', 'notification_email', $email, $replacements['{user:langcode}'], $params);

    return TRUE;
  }

}
