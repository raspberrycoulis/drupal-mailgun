<?php

namespace Drupal\mailgun_sender\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MailgunSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailgun_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailgun_sender.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailgun_sender.settings');

    $form['update_api_key'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update API Key'),
      '#description' => $this->t('If checked, this allows you to update the API key with a new one.'),
      '#default_value' => FALSE,
      '#ajax' => [
        'callback' => '::toggleApiKeyField',
        'wrapper' => 'api-key-wrapper',
      ],
    ];

    $form['api_key_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'api-key-wrapper'],
    ];

    $form['api_key_wrapper']['api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Mailgun API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#disabled' => !$form_state->getValue('update_api_key'),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailgun Domain'),
      '#default_value' => $config->get('domain'),
      '#required' => TRUE,
    ];

    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#default_value' => $config->get('region') ?? 'us',
      '#options' => [
        'us' => $this->t('US'),
        'eu' => $this->t('EU'),
      ],
      '#required' => TRUE,
    ];

    $form['from_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Default From Address'),
      '#default_value' => $config->get('from_address'),
      '#required' => TRUE,
    ];

    $form['set_as_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Mailgun as the default mail system'),
      '#default_value' => \Drupal::config('system.mail')->get('interface.default') === 'mailgun_sender',
      '#description' => $this->t('If checked, Mailgun will be used to send all system emails.'),
    ];

    // --- Test Email Section ---
    $form['test_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Send Test Email To'),
      '#description' => $this->t('Enter an email address to send a test message using the current settings.'),
      '#default_value' => '',
    ];

    $form['send_test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Test Email'),
      '#submit' => ['::sendTestEmail'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('update_api_key') && !empty($form_state->getValue('api_key'))) {
      $this->config('mailgun_sender.settings')
        ->set('api_key', $form_state->getValue('api_key'))
        ->set('domain', $form_state->getValue('domain'))
        ->set('region', $form_state->getValue('region'))
        ->set('from_address', $form_state->getValue('from_address'))
        ->save();
    } else {
      $this->config('mailgun_sender.settings')
        ->set('domain', $form_state->getValue('domain'))
        ->set('region', $form_state->getValue('region'))
        ->set('from_address', $form_state->getValue('from_address'))
        ->save();
    }

    // Update default mail system setting if checkbox is toggled.
    if ($form_state->getValue('set_as_default')) {
      \Drupal::configFactory()->getEditable('system.mail')
        ->set('interface.default', 'mailgun_sender')
        ->save();
    }

    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Mailgun settings have been saved.'));
  }

  /**
   * Custom submit handler for sending test email.
   */
  public function sendTestEmail(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('test_email');

    if (empty($to)) {
      $this->messenger()->addError($this->t('Please enter a valid email address to send a test.'));
      return;
    }

    // Load current saved configuration
    $config = $this->config('mailgun_sender.settings');
    $api_key = $config->get('api_key');
    $domain = $config->get('domain');
    $region = $config->get('region');
    $from = $config->get('from_address');

    if (empty($api_key) || empty($domain) || empty($from)) {
      $this->messenger()->addError($this->t('Cannot send test email. Please ensure API Key, Domain, and From Address are configured.'));
      return;
    }

    /** @var \Drupal\mailgun_sender\Service\MailgunService $mailgun */
    $mailgun = \Drupal::service('mailgun_sender.mailgun_service');

    $success = $mailgun->sendEmail(
      $to,
      'Test Email from Drupal Mailgun Module',
      'This is a test email sent from your Mailgun configuration form in Drupal.'
    );

    if ($success) {
      $this->messenger()->addStatus($this->t('Test email sent to %email.', ['%email' => $to]));
    } else {
      $this->messenger()->addError($this->t('Failed to send test email. Check the logs for more details.'));
    }
  }

  /**
   * AJAX callback to show/hide the API key field.
   */
  public function toggleApiKeyField(array &$form, FormStateInterface $form_state) {
    return $form['api_key_wrapper'];
  }

}