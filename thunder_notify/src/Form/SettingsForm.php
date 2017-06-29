<?php

namespace Drupal\thunder_notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\thunder_notify\NotificationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures thunder_notify settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The notification type manager.
   *
   * @var \Drupal\thunder_notify\NotificationTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Constructs a \Drupal\thunder_notify\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\thunder_notify\NotificationTypeManagerInterface $type_manager
   *   The notification type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotificationTypeManagerInterface $type_manager, TranslationInterface $string_translation) {
    parent::__construct($config_factory);
    $this->typeManager = $type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('thunder_notify.notification.manager.type'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_notify_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['thunder_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('thunder_notify.settings');

    $collector_cron_url = Url::fromRoute('thunder_notify.cron.collect', ['cron_key' => $config->get('cron_access_key')], ['absolute' => TRUE])->toString();
    $queue_cron_url = Url::fromRoute('thunder_notify.cron.send', ['cron_key' => $config->get('cron_access_key')], ['absolute' => TRUE])->toString();

    // Cron settings.
    $form['cron_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cron settings'),
      '#description' => $this->t('Create a crontab and call :collector_url to add pending notifications to the queue. To process the queue, call :queue_url from another crontab.', [':collector_url' => $collector_cron_url, ':queue_url' => $queue_cron_url]),
    ];
    $form['cron_settings']['thunder_notify_cron_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron access key'),
      '#default_value' => $config->get('cron_access_key'),
      '#required' => TRUE,
      '#size' => 25,
      '#description' => $this->t("Similar to Drupal's cron key this acts as a security token to prevent unauthorised calls to the notification crons."),
    ];

    // Queue settings.
    $form['queue_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Queue settings'),
    ];
    $form['queue_settings']['thunder_notify_queue_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Queue limit'),
      '#step' => 1,
      '#min' => 1,
      '#size' => 5,
      '#default_value' => $config->get('queue.limit'),
      '#description' => $this->t('The maximum number of queued items to process on each run of the <a href=":url">Queue cron</a>.', [':url' => $queue_cron_url]),
    ];

    // Notification type settings.
    $form['type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification type settings'),
    ];
    /* @var $notification_types \Drupal\thunder_notify\NotificationTypeInterface[] */
    $notification_types = $this->typeManager->getInstances();
    $options = [];
    foreach ($notification_types as $plugin_id => $type) {
      $options[$plugin_id] = $type->getLabel();
    }
    $form['type_settings']['thunder_notification_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Notification types'),
      '#options' => $options,
      '#default_value' => $config->get('notification_types'),
      '#description' => $this->t('Select the notification types that should be enabled.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('thunder_notify.settings');

    $config->set('cron_access_key', $form_state->getValue('thunder_notify_cron_access_key'));
    $config->set('queue.limit', $form_state->getValue('thunder_notify_queue_limit'));
    $config->set('notification_types', $form_state->getValue('thunder_notification_types'));

    $config->save();
  }

}
