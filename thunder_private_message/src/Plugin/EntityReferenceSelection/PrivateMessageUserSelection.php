<?php

namespace Drupal\thunder_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "thunder_private_message:user",
 *   label = @Translation("Private messages: User selection"),
 *   entity_types = {"user"},
 *   group = "thunder_private_message",
 *   weight = 0
 * )
 */
class PrivateMessageUserSelection extends UserSelection {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a new PrivateMessageUserSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $connection, ConfigFactoryInterface $config_factory, PrivateMessageHelperInterface $private_message_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user, $connection);

    $this->configFactory = $config_factory;
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $config = $this->configFactory->get('thunder_private_message.settings');

    // Override parent handler settings and set configuration.
    $handler_settings = &$this->configuration['handler_settings'];
    $handler_settings['include_anonymous'] = FALSE;

    // Set roles from config.
    $roles = $config->get('recipients.roles') ?: [];
    unset($roles['authenticated']);
    if (!empty($roles)) {
      $handler_settings['filter']['role'] = array_keys(array_filter($roles));
    }

    $query = parent::buildEntityQuery($match, $match_operator);

    // Do not let users write messages to themselves.
    $query->condition('uid', $this->currentUser->id(), '<>');

    if ($this->privateMessageHelper->userIsAllowedToBypassAccessChecks($this->currentUser)) {
      return $query;
    }

    // Exclude users not wanting private messages.
    $query->condition('tpm_allow_messages', 1, '=');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('thunder_private_message.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // Override parent handler settings and set configuration.
    $this->configuration['handler_settings']['include_anonymous'] = FALSE;

    return parent::entityQueryAlter($query);
  }

}
