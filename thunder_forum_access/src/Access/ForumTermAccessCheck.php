<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Access\AccessResult;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_forum\ThunderForumManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for forum term entities.
 */
class ForumTermAccessCheck implements AccessInterface {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a new ForumTermAccessCheck.
   *
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   */
  public function __construct(ThunderForumManagerInterface $forum_manager) {
    $this->forumManager = $forum_manager;
  }

  /**
   * Checks access to the forum taxonomy term entity operation on given route.
   *
   * The value of the '_thunder_forum_access_forum_term_access' key must be in
   * the pattern 'entity_slug_name.operation.' For example, this will check a
   * forum taxonomy term for 'update' access:
   * @code
   * pattern: '/foo/{taxonomy_term}/bar'
   * requirements:
   *   _thunder_forum_access_forum_term_access: 'taxonomy_term.update'
   * @endcode
   *
   * Available operations are 'view', 'update', 'create', and 'delete'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_thunder_forum_access_forum_term_access');
    list($entity_type, $operation) = explode('.', $requirement);

    if ($entity_type === 'taxonomy_term') {
      // If there is valid entity of the given entity type, check its access.
      $parameters = $route_match->getParameters();

      if ($parameters->has($entity_type)) {
        /** @var \Drupal\taxonomy\TermInterface $entity */
        $entity = $parameters->get($entity_type);
        if ($entity instanceof EntityInterface && $this->forumManager->isForumTerm($entity)) {
          return $entity->access($operation, $account, TRUE);
        }
      }
    }

    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return AccessResult::neutral();
  }

}
