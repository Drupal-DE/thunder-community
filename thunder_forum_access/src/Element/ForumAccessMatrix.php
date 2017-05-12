<?php

namespace Drupal\thunder_forum_access\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a form element for a table with forum access matrix checkboxes.
 *
 * @FormElement("tfa_access_matrix")
 */
class ForumAccessMatrix extends Table {

  /**
   * Return forum access manager service.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessMatrix
   *   The forum access manager.
   */
  protected static function getForumAccessMatrixService() {
    return \Drupal::service('thunder_forum_access.forum_access_matrix');
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#responsive' => FALSE,
      '#sticky' => TRUE,
      '#pre_render' => [
        [$class, 'preRenderTable'],
        [$class, 'preRenderForumAccessMatrix'],
      ],
      '#process' => [
        [$class, 'processForumAccessMatrix'],
      ],
      '#element_validate' => [
        [$class, 'validateForumAccessMatrix'],
      ],
      '#empty' => '',
      '#theme' => 'table__tfa_access_matrix',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Prepares a 'tfa_access_matrix' #type element for rendering.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderForumAccessMatrix($element) {
    $forum_access_manager = static::getForumAccessMatrixService();
    $roles = $forum_access_manager->getRoles();

    // Build table rows.
    $rows = [];
    foreach ($forum_access_manager->getPermissions() as $entity_type_id => $entity_type_info) {
      // Entity type header row.
      $rows[$entity_type_id] = [
        'data' => [
          'label' => [
            'data' => $entity_type_info['label'],
            'title' => !empty($entity_type_info['description']) ? $entity_type_info['description'] : NULL,
            'colspan' => count($roles) + 1,
            'header' => TRUE,
          ],
        ],
      ];

      // Permission rows.
      foreach ($entity_type_info['permissions'] as $permission_name => $permission) {
        $row = ['data' => []];

        // Permission label.
        $row['data']['label'] = [
          'data' => $element[$entity_type_id][$permission_name]['_label'],
        ];

        foreach ($roles as $role_name => $role) {
          $row['data'][$role_name] = [
            'data' => $element[$entity_type_id][$permission_name][$role_name],
            'class' => [
              'checkbox',
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $element['#rows'] = $rows;

    return $element;
  }

  /**
   * Creates checkbox to populate a forum access matrix table.
   *
   * @param array $element
   *   An associative array containing the properties and children of the forum
   *   access matrix element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processForumAccessMatrix(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $forum_access_manager = static::getForumAccessMatrixService();
    $roles = $forum_access_manager->getRoles();

    $element['#tree'] = TRUE;

    // Apply user permissions library/classes for table styling.
    $element['#attached']['library'][] = 'user/drupal.user.permissions';
    $element['#attributes']['class'][] = 'permissions';

    // Table header column: Permission.
    $element['#header'] = [
      '_permission' => ['data' => t('Permissions')],
    ];

    // Table header columns: Roles.
    foreach ($roles as $role_name => $role) {
      $element['#header'][$role_name] = [
        'data' => $role['label'],
        'title' => !empty($role['description']) ? $role['description'] : NULL,
        'class' => [
          'checkbox',
        ],
      ];
    }

    // Permission labels/descriptions and checkboxes per role.
    foreach ($forum_access_manager->getPermissions() as $entity_type_id => $entity_type_info) {
      foreach ($entity_type_info['permissions'] as $permission_name => $permission) {
        // Permission label.
        $element[$entity_type_id][$permission_name]['_label'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $permission['label'],
            'description' => !empty($permission['description']) ? $permission['description'] : NULL,
          ],
        ];

        // Permission checkboxes per role.
        foreach ($roles as $role_name => $role) {
          $element[$entity_type_id][$permission_name][$role_name] = [
            '#type' => 'checkbox',
            '#title' => $permission['label'],
            '#title_display' => 'invisible',
            '#description' => !empty($permission['description']) ? $permission['description'] : NULL,
            '#description_display' => 'invisible',
            '#default_value' => !empty($element['#value'][$role_name][$entity_type_id][$permission_name]),
            '#return_value' => $permission_name,
            '#parents' => array_merge($element['#parents'], [
              $role_name,
              $entity_type_id,
              $permission_name,
            ]),
            '#attributes' => [
              'title' => $permission['label'],
            ],
          ];
        }
      }
    }

    return $element;
  }

  /**
   * Element validation callback for #type 'tfa_access_matrix'.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   table element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateForumAccessMatrix(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Ensure cleaned value in form state.
    if (!$form_state->getError($element)) {
      $form_state->setValueForElement($element, !empty($element['#value']) ? $element['#value'] : []);
    }
  }

}
