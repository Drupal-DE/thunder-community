<?php

namespace Drupal\thunder_forum_subscription\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for forum subscriptions.
 */
class ForumSubscriptionController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a ForumSubscriptionController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Returns a user's forum subscriptions page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user whose forum subscriptions should be displayed.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function forumSubscriptionsPerUser(AccountInterface $user) {
    $build = [];

    // User is allowed to subscribe/unsubscribe forums.
    $user_is_allowed_to_flag_unflag_forums = $user->hasPermission('flag thunder_forum_subscription_forum')
      || $user->hasPermission('unflag thunder_forum_subscription_forum');

    // User is allowed to subscribe/unsubscribe topics.
    $user_is_allowed_to_flag_unflag_topics = $user->hasPermission('flag thunder_forum_subscription_topic')
      || $user->hasPermission('unflag thunder_forum_subscription_topic');

    // Forum subscriptions.
    if ($user_is_allowed_to_flag_unflag_forums) {
      $build['forum_subscriptions'] = views_embed_view('thunder_forum_subscriptions_forum', 'block', $user->id());
    }

    // Forum topic subscriptions.
    if ($user_is_allowed_to_flag_unflag_topics) {
      $build['forum_topic_subscriptions'] = views_embed_view('thunder_forum_subscriptions_topic', 'block', $user->id());
    }

    return $build;
  }

  /**
   * Checks whether a user may access a user's forum subscriptions.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user whose forum subscriptions should be accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function forumSubscriptionsPerUserAccess(AccountInterface $user) {
    // User is allowed to subscribe/unsubscribe forums/topics?
    $user_is_allowed_to_flag_unflag = $user->hasPermission('flag thunder_forum_subscription_forum')
      || $user->hasPermission('flag thunder_forum_subscription_topic')
      || $user->hasPermission('unflag thunder_forum_subscription_forum')
      || $user->hasPermission('unflag thunder_forum_subscription_topic');

    // Current user is the accessed user?
    $user_is_current_user = $this->currentUser->id() === $user->id();

    $access = AccessResult::allowedIf($this->currentUser->hasPermission('administer forums') || ($user_is_current_user && $user_is_allowed_to_flag_unflag))
      ->addCacheableDependency($this->currentUser)
      ->addCacheableDependency($user)
      ->cachePerUser()
      ->cachePerPermissions();

    return $access;
  }

}
