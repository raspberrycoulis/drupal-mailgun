<?php
namespace Drupal\mailgun_sender\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MailgunTestForm extends FormBase {

  public function getFormId() {
    return 'mailgun_sender_test_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['to'] = [
      '#type' => 'email',
      '#title' => $this->t('To Email'),
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body (Text Only)'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Test Email'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('to');
    $subject = $form_state->getValue('subject');
    $body = $form_state->getValue('body');

    $mailgun = \Drupal::service('mailgun_sender.mailgun_service');
    $success = $mailgun->sendEmail($to, $subject, $body);

    if ($success) {
      $this->messenger()->addMessage($this->t('Email successfully sent to %to.', ['%to' => $to]));
    } else {
      $this->messenger()->addError($this->t('Email failed to send. Check the logs for more details.'));
    }
  }
}