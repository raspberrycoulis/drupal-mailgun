<?php

namespace Drupal\mailgun_sender\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\MailPluginBase;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the Mailgun Sender mail backend plugin.
 *
 * @Mail(
 *   id = "mailgun_sender",
 *   label = @Translation("Mailgun Sender"),
 *   description = @Translation("Sends mail via the Mailgun HTTP API.")
 * )
 */
class MailgunSenderMail extends MailPluginBase implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // You could customise headers/body here if needed
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $config = \Drupal::config('mailgun_sender.settings');
    $api_key = $config->get('api_key');
    $domain = $config->get('domain');
    $region = $config->get('region') ?? 'us';

    if (empty($api_key) || empty($domain)) {
      \Drupal::logger('mailgun_sender')->error('Missing Mailgun API configuration.');
      return FALSE;
    }

    $endpoint = $region === 'eu'
      ? 'https://api.eu.mailgun.net/v3/' . $domain . '/messages'
      : 'https://api.mailgun.net/v3/' . $domain . '/messages';

    $params = [
      'auth' => ['api', $api_key],
      'form_params' => [
        'from' => $message['from'],
        'to' => $message['to'],
        'subject' => $message['subject'],
        'text' => is_array($message['body']) ? implode("\n", $message['body']) : $message['body'],
      ],
    ];

    try {
      $client = \Drupal::httpClient();
      $response = $client->post($endpoint, $params);

      if ($response->getStatusCode() === 200) {
        \Drupal::logger('mailgun_sender')->info('Email sent to %to via Mailgun.', ['%to' => $message['to']]);
        return TRUE;
      }
      else {
        \Drupal::logger('mailgun_sender')->error('Mailgun responded with HTTP %code', ['%code' => $response->getStatusCode()]);
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('mailgun_sender')->error('Mailgun request failed: @error', ['@error' => $e->getMessage()]);
    }

    return FALSE;
  }

}