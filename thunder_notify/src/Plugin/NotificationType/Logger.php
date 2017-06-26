<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fallback notification type.
 *
 * Log notifications (if logging is enabled).
 *
 * @NotificationType(
 *   id = "logger",
 *   label = @Translation("Logger")
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    parent::create(
      $configuration,
      $plugin_id,
      $plugin_definition,
      \Drupal::configFactory(),
      \Drupal::logger('thunder_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildMessage() {
    $output = [];
    $output = $this->t('New notification for user @username', ['@username' => '']);

    return implode("\n", $output);
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    if (!$this->configFactory->get('thunder_notify.settings')->get('logger.enabled')) {
      // Nothing to do here.
      return TRUE;
    }

    $this->logger->info($this->buildMessage());
    return TRUE;
  }

}
