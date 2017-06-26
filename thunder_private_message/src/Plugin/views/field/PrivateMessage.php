<?php

namespace Drupal\thunder_private_message\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide renderer that allows linking to a private message.
 *
 * Definition terms:
 * - link_to_private_message default: Should this field have the checkbox "Link
 *   to private message" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_private_message")
 */
class PrivateMessage extends FieldPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PrivateMessage.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateMessageHelperInterface $private_message_helper, RouteMatchInterface $route_match, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->privateMessageHelper = $private_message_helper;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('thunder_private_message.helper'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby.
    if (!empty($this->options['link_to_private_message'])) {
      $this->additional_fields['mid'] = ['table' => 'message_field_data', 'field' => 'mid'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_private_message'] = [
      'default' => isset($this->definition['link_to_private_message default']) ? $this->definition['link_to_private_message default'] : FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_private_message'] = [
      '#title' => $this->t('Link this field to the original piece of content'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'radios',
      '#options' => [
        '' => 'No link',
        'inbox' => $this->t('In inbox context'),
        'outbox' => $this->t('In outbox context'),
      ],
      '#default_value' => !empty($this->options['link_to_private_message']) ? $this->options['link_to_private_message'] : '',
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the private message.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $this->getEntity($values);

    if (!empty($this->options['link_to_private_message']) && !empty($this->additional_fields['mid'])) {
      if ($data !== NULL && $data !== '') {
        // Prepare route name and parameters.
        $route_parameters = [
          'message' => $message->id(),
        ];

        switch ($this->options['link_to_private_message']) {
          case 'inbox':
            $route_name = 'entity.message.canonical.thunder_private_message.inbox';

            if (!($recipient = $this->privateMessageHelper->getMessageRecipient($message))) {
              return $data;
            }

            $route_parameters['user'] = $recipient->id();
            break;

          case 'outbox':
            $route_name = 'entity.message.canonical.thunder_private_message.outbox';

            if (!($sender = $message->getOwnerId())) {
              return $data;
            }

            $route_parameters['user'] = $sender;
            break;
        }

        if (!$route_name) {
          return $data;
        }

        $this->options['alter']['make_link'] = TRUE;

        // Build URL.
        $this->options['alter']['url'] = Url::fromRoute($route_name, $route_parameters);

        if (isset($this->aliases['langcode'])) {
          $languages = \Drupal::languageManager()->getLanguages();
          $langcode = $this->getValue($values, 'langcode');

          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }

      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
