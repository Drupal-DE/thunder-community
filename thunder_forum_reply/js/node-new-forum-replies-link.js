/**
 * @file
 * Attaches behaviors for the Thunder Forum Reply module's "X new forum replies"
 * link.
 *
 * May only be loaded for authenticated users, with the History module
 * installed.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Render "X new forum replies" links wherever necessary.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches new forum reply links behavior.
   */
  Drupal.behaviors.thunderForumReplyNewRepliesLink = {
    attach: function (context) {
      // Collect all "X new forum replies" node link placeholders (and their
      // corresponding node IDs) newer than 30 days ago that have not already
      // been read after their last forum reply timestamp.
      var nodeIDs = [];
      var $placeholders = $(context)
        .find('[data-history-node-last-thunder-forum-reply-timestamp]')
        .once('history')
        .filter(function () {
          var $placeholder = $(this);
          var lastReplyTimestamp = parseInt($placeholder.attr('data-history-node-last-thunder-forum-reply-timestamp'), 10);
          var nodeID = $placeholder.closest('[data-history-node-id]').attr('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, lastReplyTimestamp)) {
            nodeIDs.push(nodeID);
            // Hide this placeholder link until it is certain we'll need it.
            hide($placeholder);
            return true;
          }
          else {
            // Remove this placeholder link from the DOM because we won't need
            // it.
            remove($placeholder);
            return false;
          }
        });

      if ($placeholders.length === 0) {
        return;
      }

      // Perform an AJAX request to retrieve node read timestamps.
      Drupal.history.fetchTimestamps(nodeIDs, function () {
        processNodeNewReplyLinks($placeholders);
      });
    }
  };

  /**
   * Hides a "new forum reply" element.
   *
   * @param {jQuery} $placeholder
   *   The placeholder element of the new forum reply link.
   *
   * @return {jQuery}
   *   The placeholder element passed in as a parameter.
   */
  function hide($placeholder) {
    return $placeholder
      // Find the parent <li>.
      .closest('.thunder-forum-reply-new-replies')
      // Find the preceding <li>, if any, and give it the 'last' class.
      .prev().addClass('last')
      // Go back to the parent <li> and hide it.
      .end().hide();
  }

  /**
   * Removes a "new forum reply" element.
   *
   * @param {jQuery} $placeholder
   *   The placeholder element of the new forum reply link.
   */
  function remove($placeholder) {
    hide($placeholder).remove();
  }

  /**
   * Shows a "new forum reply" element.
   *
   * @param {jQuery} $placeholder
   *   The placeholder element of the new forum reply link.
   *
   * @return {jQuery}
   *   The placeholder element passed in as a parameter.
   */
  function show($placeholder) {
    return $placeholder
      // Find the parent <li>.
      .closest('.thunder-forum-reply-new-replies')
      // Find the preceding <li>, if any, and remove its 'last' class, if any.
      .prev().removeClass('last')
      // Go back to the parent <li> and show it.
      .end().show();
  }

  /**
   * Processes new forum reply links and adds appropriate text in relevant
   * cases.
   *
   * @param {jQuery} $placeholders
   *   The placeholder elements of the current page.
   */
  function processNodeNewReplyLinks($placeholders) {
    // Figure out which placeholders need the "x new forum replies" links.
    var $placeholdersToUpdate = {};
    var fieldName = 'thunder_forum_reply';
    var $placeholder;

    $placeholders.each(function (index, placeholder) {
      $placeholder = $(placeholder);
      var timestamp = parseInt($placeholder.attr('data-history-node-last-thunder-forum-reply-timestamp'), 10);
      fieldName = $placeholder.attr('data-history-node-field-name');
      var nodeID = $placeholder.closest('[data-history-node-id]').attr('data-history-node-id');
      var lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      // Queue this placeholder's "X new forum replies" link to be downloaded
      // from the server.
      if (timestamp > lastViewTimestamp) {
        $placeholdersToUpdate[nodeID] = $placeholder;
      }
      // No "X new forum replies" link necessary; remove it from the DOM.
      else {
        remove($placeholder);
      }
    });

    // Perform an AJAX request to retrieve node view timestamps.
    var nodeIDs = Object.keys($placeholdersToUpdate);
    if (nodeIDs.length === 0) {
      return;
    }

    /**
     * Renders the "X new forum replies" links.
     *
     * Either use the data embedded in the page or perform an AJAX request to
     * retrieve the same data.
     *
     * @param {object} results
     *   Data about new forum reply links indexed by nodeID.
     */
    function render(results) {
      for (var nodeID in results) {
        if (results.hasOwnProperty(nodeID) && $placeholdersToUpdate.hasOwnProperty(nodeID)) {
          $placeholdersToUpdate[nodeID]
            .attr('href', results[nodeID].first_new_reply_link)
            .text(Drupal.formatPlural(results[nodeID].new_reply_count, '1 new reply', '@count new replies'))
            .removeClass('hidden');
          show($placeholdersToUpdate[nodeID]);
        }
      }
    }

    if (drupalSettings.thunder_forum_reply && drupalSettings.thunder_forum_reply.newRepliesLinks) {
      render(drupalSettings.thunder_forum_reply.newRepliesLinks.node[fieldName]);
    }
    else {
      $.ajax({
        url: Drupal.url('forum/render_new_replies_node_links'),
        type: 'POST',
        data: {'node_ids[]': nodeIDs, 'field_name': fieldName},
        dataType: 'json',
        success: render
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
