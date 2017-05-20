<?php

namespace Drupal\thunder_private_message\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local tasks for the revision overview.
 */
class PrivateMessageDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Creates a new PrivateMessageDeriver instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $account = \Drupal::request()->attributes->has('user') ? \Drupal::request()->get('user') : \Drupal::currentUser();
    if (!$account instanceof AccountInterface) {
      // Try to load user.
      $account = User::load($account);
    }
    return new static($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->account->tpr_allow_messages) || !$this->account->tpr_allow_messages || $this->account->tpr_allow_messages->isEmpty() || !$this->account->tpr_allow_messages->first()->value) {
      return parent::getDerivativeDefinitions($base_plugin_definition);
    }

    $this->derivatives = [];
    $this->derivatives['entity.user.private_message'] = [
      'route_name' => 'view.private_messages.inbox',
      'title' => 'private messages',
      'base_route' => 'entity.user.canonical',
      'weight' => 25,
      'cache_contexts' => ['user'],
    ];
    $this->derivatives['entity.user.private_message.inbox'] = [
      'route_name' => 'view.private_messages.inbox',
      'title' => 'Inbox',
      'parent_id' => 'entity.message.private_messages:entity.user.private_message',
      'cache_contexts' => ['user'],
    ];
    $this->derivatives['entity.user.private_message.outbox'] = [
      'route_name' => 'view.private_messages.outbox',
      'title' => 'Sent',
      'parent_id' => 'entity.message.private_messages:entity.user.private_message',
      'cache_contexts' => ['user'],
    ];

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
