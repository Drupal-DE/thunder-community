<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Database\Connection;

/**
 * Provides forum access record storage service.
 */
class NotificationStorage implements NotificationStorageInterface {

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a NotificationStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return reset($this->loadByProperties(['id' => $id]));
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    return $this->loadByProperties(['id' => $ids]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $properties) {
    $query = $this->database
      ->select('thunder_notify', 'tn')
      ->fields('tn');
    foreach ($properties as $key => $value) {
      if (is_array($value)) {
        $query->condition($key, $value, 'IN');
      }
      else {
        $query->condition($key, $value);
      }
    }

    $results = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);
    array_walk($results, function (&$item) {
      if (!isset($item['data'])) {
        return;
      }
      $item['data'] = unserialize($item['data']);
    });
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $data) {
    if (!isset($data['data'])) {
      $data['data'] = [];
    }
    // Fetch existing record to merge the saved data.
    $properties = [
      'source' => $data['source'],
      'uid' => $data['uid'],
    ];
    if (($existing = $this->loadByProperties($properties)) && !empty($existing['nid'])) {
      // Merge existing data with new data while new data overrides existing.
      $data['data'] = array_replace_recursive($existing['data'], $data['data']);
    }
    // Serialize data so it can be saved.
    $data['data'] = serialize($data['data']);

    $values = [$data['source'], $data['uid'], $data['data']];

    return $this->database
      ->merge('thunder_notify')
      ->condition('uid', $data['uid'])
      ->condition('source', $data['source'])
      ->fields(['source', 'uid', 'data'], $values)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    $this->deleteByProperties(['id' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByProperties(array $properties) {
    if (empty($properties)) {
      return;
    }
    $query = $this->database
      ->delete('thunder_notify');
    foreach ($properties as $key => $value) {
      if (is_array($value)) {
        $query->condition($key, $value, 'IN');
      }
      else {
        $query->condition($key, $value);
      }
    }

    $query->execute();
  }

}
