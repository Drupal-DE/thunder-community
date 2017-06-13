<?php

namespace Drupal\thunder_forum_reply\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\thunder_forum\ThunderForumManagerInterface;
use Drupal\thunder_forum_reply\ForumReplyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for creating a forum reply entity.
 *
 * @see \Drupal\thunder_forum_reply\Entity\ForumReply.
 */
class ForumReplyCreateController extends ControllerBase {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * The forum reply manager.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyManagerInterface
   */
  protected $forumReplyManager;

  /**
   * Constructs a ForumReplyCreateController object.
   *
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   * @param \Drupal\thunder_forum_reply\ForumReplyManagerInterface $forum_reply_manager
   *   The forum reply manager.
   */
  public function __construct(ThunderForumManagerInterface $forum_manager, ForumReplyManagerInterface $forum_reply_manager) {
    $this->forumManager = $forum_manager;
    $this->forumReplyManager = $forum_reply_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('forum_manager'),
      $container->get('thunder_forum_reply.manager')
    );
  }

  /**
   * Create new forum reply entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity this forum reply belongs to.
   * @param string $field_name
   *   The field_name to which the forum reply belongs.
   * @param int|null $pfrid
   *   (optional) Some forum replies are responses to other forum replies. In
   *   those cases, $pfrid is the parent forum replie's ID. Defaults to NULL.
   *
   * @return \Drupal\thunder_forum_reply\ForumReplyInterface
   *   The new forum reply entity.
   */
  protected function createNewForumReplyEntity(NodeInterface $node, $field_name, $pfrid = NULL) {
    return $this->entityTypeManager()
      ->getStorage('thunder_forum_reply')
      ->create([
        'nid' => $node->id(),
        'field_name' => $field_name,
        'pfrid' => $pfrid,
      ]);
  }

  /**
   * Form constructor for the forum reply form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\node\NodeInterface $node
   *   The node this forum reply belongs to.
   * @param string $field_name
   *   The field_name to which the forum reply belongs.
   * @param int|null $pfrid
   *   (optional) Some forum replies are responses to other forum replies. In
   *   those cases, $pfrid is the parent forum reply's ID. Defaults to NULL.
   * @param bool $quote
   *   Whether to quote the forum reply's parent.
   *
   * @return array
   *   An renderable array containing the forum reply form.
   */
  public function form(Request $request, NodeInterface $node, $field_name, $pfrid = NULL, $quote = FALSE) {
    $build = [];

    // The user is just previewing a forum reply.
    if ($request->request->get('op') === $this->t('Preview')) {
      $build['#title'] = $this->t('Preview forum reply');
    }

    // Create dummy reply entity.
    $reply = $this->createNewForumReplyEntity($node, $field_name, $pfrid)
      ->setShouldContainParentQuoteOnCreate($quote);

    // Show the actual forum reply box.
    $build['reply_form'] = $this->entityFormBuilder()->getForm($reply);

    return $build;
  }

  /**
   * Access check for the forum reply form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The forum node this forum reply belongs to.
   * @param string $field_name
   *   The field_name to which the forum reply belongs.
   * @param int|null $pfrid
   *   (optional) Some forum replies are responses to other forum replies. In
   *   those cases, $pfrid is the parent forum replie's ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function formAccess(NodeInterface $node, $field_name, $pfrid = NULL) {
    // Check if node entity and field exists.
    $fields = $this->forumReplyManager->getFields();

    if (!$this->forumManager->checkNodeType($node) || empty($fields[$field_name])) {
      throw new NotFoundHttpException();
    }

    return $this->createNewForumReplyEntity($node, $field_name, $pfrid)->access('create', $this->currentUser(), TRUE);
  }

}
