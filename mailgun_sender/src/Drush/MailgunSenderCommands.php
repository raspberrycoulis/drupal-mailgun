<?php

namespace Drupal\mailgun_sender\Drush;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\Core\Mail\MailManagerInterface;

class MailgunSenderCommands extends DrushCommands {

  protected MailManagerInterface $mailManager;

  public function __construct() {
    $this->mailManager = \Drupal::service('plugin.manager.mail');
  }

  #[CLI\Command(name: 'mailgun:test-email', aliases: ['mg-test'])]
  #[CLI\Argument(name: 'to', description: 'The email address to send the test to.')]
  #[CLI\Option(name: 'subject', description: 'The subject of the test email.')]
  #[CLI\Option(name: 'body', description: 'The body of the test email.')]
  #[CLI\Usage(name: 'drush mailgun:test-email user@example.com', description: 'Sends a test email to user@example.com')]
  public function sendTestEmail(string $to, array $options = ['subject' => '', 'body' => '']): void {
    $subject = $options['subject'] ?: 'Mailgun Test Email';
    $body = $options['body'] ?: 'This is a test email sent using Mailgun.';
  
    $params = [
      'subject' => $subject,
      'body' => $body,
    ];
  
    $from = \Drupal::config('mailgun_sender.settings')->get('from_address');
    if (!$from) {
      $this->output()->writeln('❌ Mailgun "from address" is not configured.');
      return;
    }
  
    $result = $this->mailManager->mail(
      'mailgun_sender',
      'test',
      $to,
      \Drupal::languageManager()->getDefaultLanguage()->getId(),
      $params,
      $from
    );
  
    if (!empty($result) && isset($result['result']) && $result['result'] === TRUE) {
      $this->output()->writeln("✅ Test email sent to $to from $from.");
    } else {
      $this->output()->writeln("❌ Failed to send test email to $to.");
    }
  }
}