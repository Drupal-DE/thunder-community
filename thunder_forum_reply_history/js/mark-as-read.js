/**
 * @file
 * Marks the forum replies listed in drupalSettings.thunder_forum_reply_history.itemsToMarkAsRead
 * as read.
 *
 * Uses the Tunder Forum Reply History module JavaScript API.
 *
 * @see Drupal.thunder_forum_reply_history
 */

(function (window, Drupal, drupalSettings) {

  'use strict';

  // When the window's "load" event is triggered, mark all enumerated forum
  // replies as read. This still allows for Drupal behaviors (which are
  // triggered on the "DOMContentReady" event) to add "new" and "updated"
  // indicators.
  window.addEventListener('load', function () {
    if (drupalSettings.thunder_forum_reply_history && drupalSettings.thunder_forum_reply_history.itemsToMarkAsRead) {
      Object.keys(drupalSettings.thunder_forum_reply_history.itemsToMarkAsRead).forEach(Drupal.thunder_forum_reply_history.markAsRead);
    }
  });

})(window, Drupal, drupalSettings);
