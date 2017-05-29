/**
 * @file
 * Attaches behaviors for the Thunder Forum Reply module's "new" indicator.
 *
 * May only be loaded for authenticated users, with the History module
 * installed.
 */

(function ($, Drupal, window) {

  'use strict';

  /**
   * Renders "new" forum reply indicators wherever necessary.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches "new" forum reply indicators behavior.
   */
  Drupal.behaviors.thunderForumReplyNewIndicator = {
    attach: function (context) {
      // Collect all "new" forum reply indicator placeholders (and their
      // corresponding node IDs) newer than 30 days ago that have not already
      // been read after their last forum reply timestamp.
      var nodeIDs = [];
      var $placeholders = $(context)
        .find('[data-thunder-forum-reply-timestamp]')
        .once('history')
        .filter(function () {
          var $placeholder = $(this);
          var replyTimestamp = parseInt($placeholder.attr('data-thunder-forum-reply-timestamp'), 10);
          var nodeID = $placeholder.closest('[data-history-node-id]').attr('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, replyTimestamp)) {
            nodeIDs.push(nodeID);
            return true;
          }
          else {
            return false;
          }
        });

      if ($placeholders.length === 0) {
        return;
      }

      // Fetch the node read timestamps from the server.
      Drupal.history.fetchTimestamps(nodeIDs, function () {
        processReplyNewIndicators($placeholders);
      });
    }
  };

  /**
   * Processes the markup for "new forum reply" indicators.
   *
   * @param {jQuery} $placeholders
   *   The elements that should be processed.
   */
  function processReplyNewIndicators($placeholders) {
    var isFirstNewReply = true;
    var newReplyString = Drupal.t('new');
    var $placeholder;

    $placeholders.each(function (index, placeholder) {
      $placeholder = $(placeholder);
      var timestamp = parseInt($placeholder.attr('data-thunder-forum-reply-timestamp'), 10);
      var $node = $placeholder.closest('[data-history-node-id]');
      var nodeID = $node.attr('data-history-node-id');
      var lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      if (timestamp > lastViewTimestamp) {
        // Turn the placeholder into an actual "new" indicator.
        var $reply = $(placeholder)
          .removeClass('hidden')
          .text(newReplyString)
          .closest('.js-thunder-forum-reply')
          // Add 'new' class to the forum reply, so it can be styled.
          .addClass('new');

        // Insert "new" anchor just before the "reply-<frid>" anchor if
        // this is the first new forum reply in the DOM.
        if (isFirstNewReply) {
          isFirstNewReply = false;
          $reply.prev().before('<a id="new" />');
          // If the URL points to the first new forum reply, then scroll to that
          // forum reply.
          if (window.location.hash === '#new') {
            window.scrollTo(0, $reply.offset().top - Drupal.displace.offsets.top);
          }
        }
      }
    });
  }

})(jQuery, Drupal, window);
