<?php

namespace Drupal\thunder_friends;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the forum reply entity type.
 */
class FriendshipViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Status. @todo: Custom options form with dropdown.
    $data['thunder_friendship']['status']['title'] = $this->t('Friendship status');
    $data['thunder_friendship']['status']['help'] = $this->t('Status of the friendship.');
    $data['thunder_friendship']['status']['filter']['label'] = $this->t('Friendship status');

    // Initiator.
    $data['thunder_friendship']['uid']['title'] = $this->t('Initiator uid');
    $data['thunder_friendship']['uid']['relationship']['title'] = $this->t('Initiator');
    $data['thunder_friendship']['uid']['relationship']['help'] = $this->t('The user ID of the friendship initiator.');
    $data['thunder_friendship']['uid']['relationship']['label'] = $this->t('initiator');

    // Friend.
    $data['thunder_friendship']['uid']['title'] = $this->t('Friend uid');
    $data['thunder_friendship']['uid']['relationship']['title'] = $this->t('Friend');
    $data['thunder_friendship']['uid']['relationship']['help'] = $this->t('The user ID of the friendship friend.');
    $data['thunder_friendship']['uid']['relationship']['label'] = $this->t('friend');

    // Other friend (not current user).
    $data['thunder_friendship']['friendship_initiator_other'] = [
      'title' => $this->t('Other friend'),
      'help' => $this->t('Friend of current user.'),
      'relationship' => [
        'base' => 'users_field_data',
        'base field' => 'uid',
        'field' => 'uid',
        'other field' => 'fuid',
        'id' => 'thunder_friendship',
        'label' => $this->t('other friend'),
      ],
    ];
    $data['thunder_friendship']['uid_me'] = [
      'title' => $this->t('Current user'),
      'help' => $this->t('Current user in friendship.'),
      'argument' => [
        'id' => 'initiator_or_other',
      ],
    ];

    return $data;
  }

}
