<?php
namespace Drupal\mailgun_sender\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

class MailgunService {

  protected $config;
  protected $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->config = $config_factory->get('mailgun_sender.settings');
    $this->httpClient = $http_client;
  }

  public function sendEmail(string $to, string $subject, string $body): bool {
    $domain = $this->config->get('domain');
    $apiKey = $this->config->get('api_key');
    $region = $this->config->get('region');
    $from = $this->config->get('from_address');

    $endpoint = $region === 'eu'
      ? "https://api.eu.mailgun.net/v3/{$domain}/messages"
      : "https://api.mailgun.net/v3/{$domain}/messages";

    try {
      $response = $this->httpClient->request('POST', $endpoint, [
        'auth' => ['api', $apiKey],
        'form_params' => [
          'from' => $from,
          'to' => $to,
          'subject' => $subject,
          'text' => $body,
        ],
      ]);

      return $response->getStatusCode() === 200;
    } catch (\Exception $e) {
      \Drupal::logger('mailgun_sender')->error('Mailgun send failed: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }
}