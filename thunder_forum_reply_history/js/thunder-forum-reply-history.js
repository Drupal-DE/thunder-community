/**
 * @file
 * JavaScript API for the Thunder Forum Reply History module, with client-side
 * caching.
 *
 * May only be loaded for authenticated users, with the Thunder Forum Reply
 * History module enabled.
 */

(function ($, Drupal, drupalSettings, storage) {

  'use strict';

  var currentUserID = parseInt(drupalSettings.user.uid, 10);

  // Any forum reply that is older than 30 days is automatically considered
  // read, so for these we don't need to perform a request at all!
  var thirtyDaysAgo = Math.round(new Date().getTime() / 1000) - 30 * 24 * 60 * 60;

  // Use the data embedded in the page, if available.
  var embeddedLastReadTimestamps = false;
  if (drupalSettings.history && drupalSettings.history.lastReadTimestamps) {
    embeddedLastReadTimestamps = drupalSettings.history.lastReadTimestamps;
  }

  /**
   * @namespace
   */
  Drupal.thunder_forum_reply_history = {

    /**
     * Fetch "last read" timestamps for the given forum replies.
     *
     * @param {Array} forumReplyIds
     *   An array of forum reply IDs.
     * @param {function} callback
     *   A callback that is called after the requested timestamps were fetched.
     */
    fetchTimestamps: function (forumReplyIds, callback) {
      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps) {
        callback();
        return;
      }

      $.ajax({
        url: Drupal.url('forum/reply/history/get-read-timestamps'),
        type: 'POST',
        data: {'thunder_forum_reply_ids[]': forumReplyIds},
        dataType: 'json',
        success: function (results) {
          for (var forumReplyId in results) {
            if (results.hasOwnProperty(forumReplyId)) {
              storage.setItem('Drupal.thunder_forum_reply_history.' + currentUserID + '.' + forumReplyId, results[forumReplyId]);
            }
          }
          callback();
        }
      });
    },

    /**
     * Get the last read timestamp for the given forum reply.
     *
     * @param {number|string} forumReplyId
     *   A forum reply ID.
     *
     * @return {number}
     *   A UNIX timestamp.
     */
    getLastRead: function (forumReplyId) {
      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[forumReplyId]) {
        return parseInt(embeddedLastReadTimestamps[forumReplyId], 10);
      }
      return parseInt(storage.getItem('Drupal.thunder_forum_reply_history.' + currentUserID + '.' + forumReplyId) || 0, 10);
    },

    /**
     * Marks a forum reply as read, store the last read timestamp client-side.
     *
     * @param {number|string} forumReplyId
     *   A forum reply ID.
     */
    markAsRead: function (forumReplyId) {
      $.ajax({
        url: Drupal.url('forum/reply/history/' + forumReplyId + '/read'),
        type: 'POST',
        dataType: 'json',
        success: function (timestamp) {
          // If the data is embedded in the page, don't store on the client
          // side.
          if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[forumReplyId]) {
            return;
          }

          storage.setItem('Drupal.thunder_forum_reply_history.' + currentUserID + '.' + forumReplyId, timestamp);
        }
      });
    },

    /**
     * Determines whether a server check is necessary.
     *
     * Any forum reply that is >30 days old never gets a "new" or "updated"
     * indicator. Any content that was published before the oldest known reading
     * also never gets a "new" or "updated" indicator, because it must've been
     * read already.
     *
     * @param {number|string} forumReplyId
     *   A forum reply ID.
     * @param {number} contentTimestamp
     *   The time at which some content was published.
     *
     * @return {bool}
     *   Whether a server check is necessary for the given forum reply and its
     *   timestamp.
     */
    needsServerCheck: function (forumReplyId, contentTimestamp) {
      // First check if the content is older than 30 days, then we can bail
      // early.
      if (contentTimestamp < thirtyDaysAgo) {
        return false;
      }

      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[forumReplyId]) {
        return contentTimestamp > parseInt(embeddedLastReadTimestamps[forumReplyId], 10);
      }

      var minLastReadTimestamp = parseInt(storage.getItem('Drupal.thunder_forum_reply_history.' + currentUserID + '.' + forumReplyId) || 0, 10);
      return contentTimestamp > minLastReadTimestamp;
    }
  };

})(jQuery, Drupal, drupalSettings, window.localStorage);
