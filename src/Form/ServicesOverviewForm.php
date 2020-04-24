<?php

namespace Drupal\addressfield_lookup\Form;

use Drupal\addressfield_lookup\AddressLookupManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for address field lookup services.
 */
class ServicesOverviewForm extends FormBase {

  use ConfigFormBaseTrait;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(AddressLookupManager $plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $plugin_manager;
    $this->setConfigFactory($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['addressfield_lookup.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address_lookup'),
      $container->get('config.factory')
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
          '#default_value' => ($this->config('addressfield_lookup.settings')->get('default_service') === $id) ? $id : NULL,
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
    $this->config('addressfield_lookup.settings')->set('default_service', $form_state->getValue(['default_service']))->save();

    // Show a message.
    $this->messenger()->addStatus($this->t('Configuration saved.'));
  }

  /**
   * Submit handler: Test the default address field lookup services.
   *
   * @return bool
   *   True if testing succeed, false otherwise.
   */
  public function testDefaultService() {
    $service_id = $this->pluginManager->getDefaultId();

    if (!$service_id) {
      $this->messenger()->addWarning($this->t('Could not find the default service.'));
      return FALSE;
    }

    $service_definition = $this->pluginManager->getDefinition($service_id);

    // Check that there is some test data.
    if (!isset($service_definition['test_data'])) {
      $this->messenger()->addWarning($this->t('Could not test the default service (%service_name) as it does not define any test data.', ['%service_name' => $service_definition['label']]));
      return FALSE;
    }

    // Perform an address search with the default service test data.
    if ($test_addresses = $this->pluginManager->getAddresses($service_definition['test_data'], NULL, TRUE)) {
      // Run secondary test to get the full details of the 1st result.
      if ($test_address_details = $this->pluginManager->getAddressDetails($test_addresses[0]['id'], TRUE)) {
        // Tidy up.
        unset($test_addresses, $test_address_details);

        // The test passed.
        $this->messenger()->addStatus($this->t('The default service (%service_name) test was successful.', ['%service_name' => $service_definition['label']]));
        return TRUE;
      }
      // The test failed.
      $this->messenger()->addError($this->t('The default service (%service_name) test failed. The full address details lookup failed.', ['%service_name' => $service_definition['label']]));
      return FALSE;
    }
    // The test failed.
    $this->messenger()->addError($this->t('The default service (%service_name) test failed. The address lookup failed.', ['%service_name' => $service_definition['label']]));
    return FALSE;
  }

}
