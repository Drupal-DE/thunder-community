<?php

namespace Drupal\thunder_forum_reply_history\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\thunder_forum_reply\ForumReplyInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Thunder Forum Reply History module routes.
 */
class HistoryController extends ControllerBase {

  /**
   * Returns a set of forum reply' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getReadTimestamps(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $frids = $request->request->get('thunder_forum_reply_ids');
    if (!isset($frids)) {
      throw new NotFoundHttpException();
    }
    return new JsonResponse(thunder_forum_reply_history_read_multiple($frids));
  }

  /**
   * Marks a forum reply as read by the current user right now.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $thunder_forum_reply
   *   The forum reply whose "last read" timestamp should be updated.
   */
  public function read(Request $request, ForumReplyInterface $thunder_forum_reply) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Update the thunder_forum_reply_history table, stating that this user
    // viewed this forum_reply.
    thunder_forum_reply_history_write($thunder_forum_reply->id());

    return new JsonResponse((int) thunder_forum_reply_history_read($thunder_forum_reply->id()));
  }

}
