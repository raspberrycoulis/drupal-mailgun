# Drupal Mailgun

This is a basic Drupal module that is designed to utilse Mailgun's API to send emails.

> :warning: **This is experimental!** Whilst some functionality works, it currently does not change Drupal's default mail backend to use the Mailgun service.

## What does it do?

In short, this uses Mailgun's API to send emails.

## Installation

Add the contents of `mailgun_sender` to `/var/www/html/web/modules/custom/` (or wherever the `../web/modules/custom` directory exists on your Drupal instance). Clear Drupal's cache and then head to `/admin/config/system/mailgun-sender` to configure.

Populate the necessary details and hit save. You can send a test email to check it works, but be sure to save your details first.