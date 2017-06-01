/**
 * @file
 * Defines Javascript behaviors for the thunder_forum_access module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behaviors for tabs in the forum access configuration form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the forum access configuration
   *   form.
   */
  Drupal.behaviors.thunderForumAccessSummaries = {
    attach: function (context) {
      // Permissions.
      $(context).find('.thunder-forum-access-form-permissions').drupalSetSummary(function (context) {
        var $inheritanceCheckbox = $(context).find('.js-form-item-inherit-permissions input');

        if ($inheritanceCheckbox.is(':checked')) {
          return Drupal.t('Inherited');
        }

        return Drupal.t('Custom');
      });

      // Members.
      $(context).find('.thunder-forum-access-form-members').drupalSetSummary(function (context) {
        var $inheritanceCheckbox = $(context).find('.js-form-item-inherit-members input');

        if ($inheritanceCheckbox.is(':checked')) {
          return Drupal.t('Inherited');
        }

        return Drupal.t('Custom');
      });

      // Moderators.
      $(context).find('.thunder-forum-access-form-moderators').drupalSetSummary(function (context) {
        var $inheritanceCheckbox = $(context).find('.js-form-item-inherit-moderators input');

        if ($inheritanceCheckbox.is(':checked')) {
          return Drupal.t('Inherited');
        }

        return Drupal.t('Custom');
      });
    }
  };

})(jQuery, Drupal);
