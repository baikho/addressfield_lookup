<?php

/**
 * @file
 * Contains \Drupal\addressfield_lookup\Form\AddressfieldLookupServicesOverviewForm.
 */

namespace Drupal\addressfield_lookup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class AddressfieldLookupServicesOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addressfield_lookup_services_overview_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Get the list of available services.
    $addressfield_lookup_services = addressfield_lookup_services();

    // Initialize the form.
    $form = [];

    // Build list of all services.
    $options = [];

    // Check we have some services.
    if (count($addressfield_lookup_services)) {
      foreach ($addressfield_lookup_services as $machine_name => $addressfield_lookup_service) {
        // Add the service name.
        $form['services'][$machine_name] = [
          '#markup' => t('@service_name <small> (Machine name: @service_machine_name)</small>', [
            '@service_name' => $addressfield_lookup_service['name'],
            '@service_machine_name' => $machine_name,
          ])
          ];

        // Add the config path (if present).
        if (isset($addressfield_lookup_service['config path'])) {
          // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['services'][$machine_name]['config'] = array(
//           '#markup' => l(t('configure'), $addressfield_lookup_service['config path']),
//         );

        }

        // Add to the options list.
        $options[$machine_name] = '';
      }

      // @FIXME
      // Could not extract the default value because it is either indeterminate, or
      // not scalar. You'll need to provide a default value in
      // config/install/addressfield_lookup.settings.yml and config/schema/addressfield_lookup.schema.yml.
      $form['addressfield_lookup_default_service'] = [
        '#type' => 'radios',
        '#title' => t('Default address field lookup service'),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#default_value' => \Drupal::config('addressfield_lookup.settings')->get('addressfield_lookup_default_service'),
      ];

      $form['actions'] = ['#type' => 'actions'];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save configuration'),
      ];

      $form['actions']['test'] = [
        '#type' => 'submit',
        '#value' => t('Test default service'),
        '#submit' => [
          'addressfield_lookup_services_test_default_service'
          ],
      ];
    }
    else {
      // There are no services.
      drupal_set_message(t('There are currently no address field lookup services available.'), 'error');
    }

    // Add our custom theme for the form.
    $form['#theme'] = 'addressfield_lookup_services_overview_form';

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Set the default service.
    \Drupal::configFactory()->getEditable('addressfield_lookup.settings')->set('addressfield_lookup_default_service', $form_state->getValue(['addressfield_lookup_default_service']))->save();

    // Show a message.
    drupal_set_message(t('Configuration saved.'));
  }

}
