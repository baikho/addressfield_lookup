<?php

namespace Drupal\addressfield_lookup\Form;

use Drupal\addressfield_lookup\AddressLookupManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for address field lookup services.
 */
class ServicesOverviewForm extends FormBase {

  /**
   * The address lookup plugin manager.
   *
   * @var \Drupal\addressfield_lookup\AddressLookupManager
   */
  protected $pluginManager;

  /**
   * Constructs a new CheckoutFlowForm object.
   *
   * @param \Drupal\addressfield_lookup\AddressLookupManager $plugin_manager
   *   The address lookup plugin manager.
   */
  public function __construct(AddressLookupManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address_lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addressfield_lookup_services_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the list of available services.
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    $form['services'] = [
      '#type' => 'table',
      '#header' => [
        'id' => $this->t('Service name'),
        'default' => $this->t('Default'),
        'operations' => $this->t('Operations'),
      ],
      '#empty' => $this->t('There are currently no address field lookup services available.'),
    ];

    foreach ($plugins as $id => $label) {
      $form['services'][$id] = [
        'id' => [
          '#markup' => $this->t('@service_name <small> (Machine name: @service_id)</small>', [
            '@service_name' => $label,
            '@service_id' => $id,
          ])
        ],
        'default' => [
          '#type' => 'radio',
          '#parents' => ['default_service'],
          '#title' => t('Set @title as default', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#return_value' => $id,
          '#id' => 'edit-default-service-' . $id,
          '#default_value' => (\Drupal::config('addressfield_lookup.settings')->get('default_service') == $id) ? $id : NULL,
        ],
        // @todo
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [],
          ],
        ],
      ];
    }

    // Actions.
     $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    $form['actions']['test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test default service'),
      '#submit' => ['::testDefaultService'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the default service.
    // @todo require config object in constructor.
    \Drupal::configFactory()->getEditable('addressfield_lookup.settings')->set('default_service', $form_state->getValue(['default_service']))->save();

    // Show a message.
    // @todo replace with messenger service.
    drupal_set_message($this->t('Configuration saved.'));
  }

}
