/**
 * @file
 * Attaches behaviors for the Thunder Forum Reply module's "by-viewer" class.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Add 'by-viewer' class to forum replies written by the current user.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.thunderForumReplyByViewer = {
    attach: function (context) {
      var currentUserID = parseInt(drupalSettings.user.uid, 10);
      $('[data-thunder-forum-reply-user-id]')
        .filter(function () {
          return parseInt(this.getAttribute('data-thunder-forum-reply-user-id'), 10) === currentUserID;
        })
        .addClass('by-viewer');
    }
  };

})(jQuery, Drupal, drupalSettings);
