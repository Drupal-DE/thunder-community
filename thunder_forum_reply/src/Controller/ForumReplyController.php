<?php

namespace Drupal\thunder_forum_reply\Controller;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Url;
use Drupal\thunder_forum_reply\ForumReplyInterface;
use Drupal\thunder_forum_reply\ForumReplyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller for the forum reply entity.
 *
 * @see \Drupal\thunder_forum_reply\Entity\ForumReply.
 */
class ForumReplyController extends ControllerBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The forum reply manager.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyManagerInterface
   */
  protected $forumReplyManager;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a ForumReplyController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\thunder_forum_reply\ForumReplyManagerInterface $forum_reply_manager
   *   The forum reply manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(HttpKernelInterface $http_kernel, ForumReplyManagerInterface $forum_reply_manager, EntityFieldManagerInterface $entity_field_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityRepository = $entity_repository;
    $this->httpKernel = $http_kernel;
    $this->forumReplyManager = $forum_reply_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('thunder_forum_reply.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * Redirects forum reply links to correct page depending on reply settings.
   *
   * Since forum reply are paged there is no way to guarantee which page a forum
   * reply appears on. Forum reply paging settings may be changed at any time.
   * Therefore we use a central routing function for forum reply links, which
   * calculates the page number based on current forum reply settings and
   * returns the full forum reply view with the pager set dynamically.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $thunder_forum_reply
   *   A forum reply entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The forum reply listing set to the page on which the forum reply appears.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function forumReplyPermalink(Request $request, ForumReplyInterface $thunder_forum_reply) {
    if ($node = $thunder_forum_reply->getRepliedNode()) {
      // Check access permissions for the forum node entity.
      if (!$node->access('view')) {
        throw new AccessDeniedHttpException();
      }

      $field_definition = $this->entityFieldManager->getFieldDefinitions($node->getEntityTypeId(), $node->bundle())[$thunder_forum_reply->getFieldName()];

      // Find the current display page for this forum.
      $page = $this->entityTypeManager()->getStorage('thunder_forum_reply')->getDisplayOrdinal($thunder_forum_reply, $field_definition->getSetting('per_page'));

      // @todo: Cleaner sub request handling.
      $subrequest_url = $node->toUrl()->setOption('query', ['page' => $page])->toString(TRUE);
      $redirect_request = Request::create($subrequest_url->getGeneratedUrl(), 'GET', $request->query->all(), $request->cookies->all(), [], $request->server->all());

      // Carry over the session to the subrequest.
      if ($session = $request->getSession()) {
        $redirect_request->setSession($session);
      }

      $request->query->set('page', $page);

      $response = $this->httpKernel->handle($redirect_request, HttpKernelInterface::SUB_REQUEST);

      if ($response instanceof CacheableResponseInterface) {
        // @todo Once path aliases have cache tags (see
        //   https://www.drupal.org/node/2480077), add test coverage that
        //   the cache tag for a replies entity's path alias is added to the
        //   forum reply's permalink response, because there can be blocks or
        //   other content whose renderings depend on the subrequest's URL.
        $response->addCacheableDependency($subrequest_url);
      }

      return $response;
    }

    throw new NotFoundHttpException();
  }

  /**
   * The _title_callback for the page that renders the forum reply permalink.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $thunder_forum_reply
   *   The current forum reply.
   *
   * @return string
   *   The translated forum reply title.
   */
  public function forumReplyPermalinkTitle(ForumReplyInterface $thunder_forum_reply) {
    return $this->entityRepository->getTranslationFromContext($thunder_forum_reply)->label();
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function renderNewRepliesNodeLinks(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $nids = $request->request->get('node_ids');
    $field_name = $request->request->get('field_name');

    if (!isset($nids)) {
      throw new NotFoundHttpException();
    }

    // Only handle up to 100 nodes.
    $nids = array_slice($nids, 0, 100);

    $links = [];

    foreach ($nids as $nid) {
      $node = $this->entityTypeManager()
        ->getStorage('node')
        ->load($nid);

      $new = $this->forumReplyManager->getCountNewReplies($node);

      /** @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface $reply_storage */
      $reply_storage = $this->entityTypeManager()->getStorage('thunder_forum_reply');

      $page_number = $reply_storage->getNewReplyPageNumber($node->{$field_name}->reply_count, $new, $node, $field_name);

      $query = $page_number ? ['page' => $page_number] : NULL;

      $links[$nid] = [
        'new_reply_count' => (int) $new,
        'first_new_reply_link' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['query' => $query, 'fragment' => 'new']),
      ];
    }

    return new JsonResponse($links);
  }

}
