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
      //'#default_value' => $config->get('api_key'), // Remove if works from fresh
      '#default_value' => '',
      '#required' => TRUE,
      '#disabled' => !$form_state->getValue('update_api_key'),
      '#placeholder' => '*****************',
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
      '#description' => $this->t('If checked, Drupal will send mail via Mailgun. Unchecking this will restore the previous mail system.'),
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
    $config = $this->config('mailgun_sender.settings');

    // Remove if works with fresh install
    /*if ($form_state->getValue('update_api_key') && !empty($form_state->getValue('api_key'))) {
      $config->set('api_key', $form_state->getValue('api_key'));
    }*/

    if ($form_state->getValue('update_api_key')) {
      $new_api_key = $form_state->getValue(['api_key_wrapper', 'api_key']);
      if (!empty($new_api_key)) {
        $config->set('api_key', $new_api_key);
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);

    $config
      ->set('domain', $form_state->getValue('domain'))
      ->set('region', $form_state->getValue('region'))
      ->set('from_address', $form_state->getValue('from_address'))
      ->save();

    // If Symfony Mailer is installed, suggest MAILER_DSN setup.
    if (\Drupal::moduleHandler()->moduleExists('symfony_mailer')) {
      $api_key = $form_state->getValue('api_key') ?: $config->get('api_key');
      $domain = $form_state->getValue('domain') ?: $config->get('domain');
      $region = $form_state->getValue('region') ?: $config->get('region');

      if (!empty($api_key) && !empty($domain)) {
        $dsn = sprintf('mailgun+https://api:%s@%s', $api_key, $domain);
        $dsn_line = '$settings[\'mailer_dsn\'] = \'' . $dsn . '\';';

        $settings_php = DRUPAL_ROOT . '/sites/default/settings.php';
        $settings_local = DRUPAL_ROOT . '/sites/default/settings.local.php';

        $writable_files = [];
        if (file_exists($settings_php) && is_writable($settings_php)) {
          $writable_files[] = 'settings.php';
        }
        if (file_exists($settings_local) && is_writable($settings_local)) {
          $writable_files[] = 'settings.local.php';
        }

        if (empty($writable_files)) {
          $this->messenger()->addWarning($this->t('Neither settings.php nor settings.local.php is writable. Please copy and paste the following into one of those files manually:'));
        } else {
          $this->messenger()->addStatus($this->t('You can copy the following DSN into %files:', ['%files' => implode(' or ', $writable_files)]));
        }

        $this->messenger()->addStatus($this->t('<code>@dsn</code>', ['@dsn' => $dsn_line]));
      }
    }

    // Update default mail system setting if checkbox is toggled.
    $system_mail_config = \Drupal::configFactory()->getEditable('system.mail');
    $current_default = \Drupal::config('system.mail')->get('interface.default');
    $mailgun_config = \Drupal::configFactory()->getEditable('mailgun_sender.settings');
    $default_system = \Drupal::configFactory()->getEditable('mailsystem.settings');

    if ($form_state->getValue('set_as_default')) {
    if ($current_default !== 'mailgun_sender') {
        $mailgun_config->set('previous_mail_interface', $current_default)->save();
        $mailgun_config->set('previous_mail_interface_sender', $default_system->get('defaults.sender'))->save();
        $mailgun_config->set('previous_mail_interface_formatter', $default_system->get('defaults.formatter'))->save();

        if ($current_default === 'smtp') {
        $this->messenger()->addWarning($this->t('PHPMailer SMTP is currently active and will be overridden.'));
        }
    }

    $system_mail_config->set('interface.default', 'mailgun_sender')->save();
    $default_system->set('defaults.sender', 'mailgun_sender')->save();
    $default_system->set('defaults.formatter', 'mailgun_sender')->save();

    $this->messenger()->addStatus($this->t('Mailgun is now set as the default mail system.'));
    } else {
    $previous = $mailgun_config->get('previous_mail_interface') ?? 'php_mail';
    $previous_sender = $mailgun_config->get('previous_mail_interface_sender') ?? 'php_mail';
    $previous_formatter = $mailgun_config->get('previous_mail_interface_formatter') ?? 'php_mail';

    if ($current_default === 'mailgun_sender') {
        $system_mail_config->set('interface.default', $previous)->save();
        $default_system->set('defaults.sender', $previous_sender)->save();
        $default_system->set('defaults.formatter', $previous_formatter)->save();

        $this->messenger()->addStatus($this->t('Mailgun has been unset. Restored previous mail system: @id.', ['@id' => $previous]));
    }
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
