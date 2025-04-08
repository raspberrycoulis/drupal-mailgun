# Drupal Mailgun

This is a basic Drupal module that is designed to utilise Mailgun's API to send emails.

> [!CAUTION]
> **This is experimental!** Whilst some functionality works, it currently does not change Drupal's default mail backend to use the Mailgun service

## What does it do?

In short, this uses Mailgun's API to send emails. When configured, the module can set Mailgun to be the main mail sending mechanism within Drupal (or restored to the previous configuration) via a simple checkbox. This has been tested with PHPMailer and Symfony.

## Installation

Add the contents of `mailgun_sender` to `/var/www/html/web/modules/custom/` (or wherever the `../web/modules/custom` directory exists on your Drupal instance), then clear Drupal's cache (either via the admin UI or by running `drush cr`)

Once installed, a new menu item appears under `Configuration --> System` called `Mailgun settings` where you can set it up.

Populate the necessary details and hit save. You can send a test email to check it works, but be sure to save your details first.

### Required

In order for the Mailgun API to work, the following information is required:

* API key - your sending API key within your Mailgun domain
* Sending domain - the domain you have configured within Mailgun
* Region - either :eu: EU or :us: US, depending on where you added your domain within Mailgun
* From email - the address your email will be sent from

You can also attempt to set Mailgun as the defailt mail backend by checking the box. This does not always work, hence the caution above.

> [!TIP]
> Once you have set up the necessary details, you can send a test to an email of your choosing to check the connection works.