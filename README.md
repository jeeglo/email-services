# Email Services
This library provides a uniform way to integrate with several email services. Currently it supports:
- Aweber
- Constant Contact
- Sendlane
- Mailchimp
- ActiveCampaign
- ConvertKit
- Mailerlite
- Kyvio
- Getresponse
- iContact
- Drip
- Ontraport
- EmailOctopus
- Sendiio
- Mailvio
- Infusionsoft
- SendFox
- Moosend
- Campaign Refinery
- SendGrid
- Platform.Ly

## Development
Create an index.php file in the root folder. Call your service from this file. Make sure to autoload all the classes to make it work.

Use this snippet at the start of the file to autoload your classes:
```php
require_once 'vendor/autoload.php';
```

With that done, You can load any class from the src directory and quickly test it out.