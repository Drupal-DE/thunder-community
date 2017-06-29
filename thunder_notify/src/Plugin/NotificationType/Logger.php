<?php

namespace Drupal\thunder_notify\Plugin\NotificationType;

use Drupal\thunder_notify\NotificationTypeBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fallback notification type.
 *
 * Log notifications (if logging is enabled).
 *
 * @NotificationType(
 *   id = "logger",
 *   label = @Translation("Logger"),
 *   message_tokens = {
 *     "notifications": @Translation("Messages from notification sources")
 *   }
 * )
 */
class Logger extends NotificationTypeBase {

  /**
   * The used logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      \Drupal::logger('thunder_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    $message = parent::buildMessage();

    $output = [$message];

    return implode("\n", $output);
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $messages, array $replacements = []) {
    $log_message[] = '<pre>';
    $log_message[] = $this->buildSubject();
    $log_message[] = '---';
    $log_message[] = $this->buildMessage();
    $log_message[] = '</pre>';

    $replacements += [
      '{messages}' => implode("\n", $messages),
    ];

    $this->logger->info(strtr(implode("\n", $log_message), $replacements));
    return TRUE;
  }

}
