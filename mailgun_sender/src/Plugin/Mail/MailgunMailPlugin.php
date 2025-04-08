<?php

namespace Drupal\mailgun_sender\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\mailgun_sender\Service\MailgunService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the Mailgun implementation of MailInterface.
 *
 * @Mail(
 *   id = "mailgun_sender",
 *   label = @Translation("Mailgun Sender"),
 *   description = @Translation("Sends email via Mailgun API.")
 * )
 */
class MailgunMailPlugin implements MailInterface, ContainerFactoryPluginInterface {

  protected $mailgunService;

  public function __construct(MailgunService $mailgun_service) {
    $this->mailgunService = $mailgun_service;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('mailgun_sender.mailgun_service')
    );
  }

  public function format(array $message) {
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    return $message;
  }

  public function mail(array $message) {
    $to = $message['to'];
    $subject = $message['subject'];
    $body = is_array($message['body']) ? implode("\n", $message['body']) : $message['body'];
    return $this->mailgunService->sendEmail($to, $subject, $body);
  }
}