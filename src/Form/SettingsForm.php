<?php

namespace Drupal\addressfield_lookup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addressfield_lookup_settings_form';
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('addressfield_lookup.settings');

    // Extra fields.
    $form['addressfield_hide_extra_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide additional fields'),
      '#description' => $this->t('Do not show fields such as names or company on the address field lookup form.'),
      '#default_value' => $config->get('addressfield_hide_extra_fields'),
    ];

    // Address details cache length.
    $form['cache_length'] = [
      '#type' => 'number',
      '#title' => t('Cache length'),
      '#default_value' => $config->get('cache_length'),
      '#description' => $this->t('Length (in seconds) to keep address details in the cache.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('addressfield_lookup.settings')
      ->set('addressfield_hide_extra_fields', $form_state->getValue('addressfield_hide_extra_fields'))
      ->set('cache_length', $form_state->getValue('cache_length'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
