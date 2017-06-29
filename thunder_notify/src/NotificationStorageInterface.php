<?php

namespace Drupal\thunder_notify;

/**
 * Provides a notification storage interface.
 */
interface NotificationStorageInterface {

  /**
   * Save information about a notification.
   *
   * @param array $data
   *   The information to save:
   *   - uid: ID of user to notify
   *   - source: plugin-ID of notification source
   *   - data: data to send (used by the notification source plugin)
   *
   * @return int
   *   0 for update, 1 for insert.
   */
  public function save(array $data);

  /**
   * Load notification information using the unique identifier.
   *
   * @param int $id
   *   Internal identifier of notification record.
   *
   * @return array|false
   *   The notification record or false if no record exists with this ID.
   */
  public function load($id);

  /**
   * Return multiple notification records.
   *
   * @param int[] $ids
   *   List of notification IDs to load.
   *
   * @return array[]
   *   The notification records.
   */
  public function loadMultiple(array $ids);

  /**
   * Load notifications by specified properties.
   *
   * @param array $properties
   *   List of properties to filter the list of notifications.
   *
   * @code
   *   $notifications = $storage->loadByProperties(['uid' => 1]);
   *   $notifications = $storage->loadByProperties(['source' => 'message']);
   * @endcode
   *
   * @return array
   *   The matching notification records.
   */
  public function loadByProperties(array $properties);

  /**
   * Delete a single notification record.
   *
   * @param int $id
   *   ID of notification record to delete.
   */
  public function delete($id);

  /**
   * Delete multiple notification records.
   *
   * @param array $properties
   *   List of properties to filter the list of notifications.
   */
  public function deleteByProperties(array $properties);

}
