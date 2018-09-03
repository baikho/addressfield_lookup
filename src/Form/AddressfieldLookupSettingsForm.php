<?php

/**
 * @file
 * Contains \Drupal\addressfield_lookup\Form\AddressfieldLookupSettingsForm.
 */

namespace Drupal\addressfield_lookup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class AddressfieldLookupSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addressfield_lookup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('addressfield_lookup.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['addressfield_lookup.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Extra fields.
    $form['addressfield_lookup_addressfield_hide_extra_fields'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide additional fields'),
      '#description' => t('Do not show fields such as names or company on the address field lookup form.'),
      '#default_value' => \Drupal::config('addressfield_lookup.settings')->get('addressfield_lookup_addressfield_hide_extra_fields'),
    ];

    // Address details cache length.
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/addressfield_lookup.settings.yml and config/schema/addressfield_lookup.schema.yml.
    $form['addressfield_lookup_cache_length'] = [
      '#type' => 'textfield',
      '#title' => t('Cache length'),
      '#default_value' => \Drupal::config('addressfield_lookup.settings')->get('addressfield_lookup_cache_length'),
      '#description' => t('Length (in seconds) to keep address details in the cache.'),
      '#element_validate' => [
        'element_validate_integer_positive'
        ],
    ];

    return parent::buildForm($form, $form_state);
  }

}
