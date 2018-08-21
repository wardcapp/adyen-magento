# Adyen Payment plugin for Magento
Use Adyen's plugin for Magento to offer frictionless payments online, in-app, and in-store.

## Requirements
The plugin supports the Magento Community (version 1.8 and higher) and Enterprise edition (version 1.13 and higher). 
For Magento 2.x please use the following plugin: [https://github.com/Adyen/adyen-magento2](https://github.com/Adyen/adyen-magento2)

## Collaboration
We commit all our new features directly into our GitHub repository.
But you can also request or suggest new features or code changes yourself!


## Installation
Copy the folders to your main Magento environment or use composer:
```
composer require adyen/payment
```

## Documentation
[Magento documentation](https://docs.adyen.com/developers/plug-ins-and-partners/magento-1)

## Videos
* [Point-of-Sale demo of the Adyen Payment module](https://vimeo.com/128983014)

## Setup Cron
Make sure that your Magento cron is running every minute. We are using a cronjob to process the notifications, our webhook service. The cronjob will be executed every minute. It only executes the notifications that have been received at least 5 minutes ago. This is to ensure that Magento has created the order, and all save after events are executed. A handy tool to get insight into your cronjobs is AOE scheduler. You can download this tool through <a target="_blank" href="https://github.com/AOEpeople/Aoe_Scheduler/releases">GitHub</a>.

## Support
You can create issues on our Magento Repository. In case of specific problems with your account, you can also contact  <a href="mailto:support@adyen.com">support@adyen.com</a>.

## Licence
MIT license. For more information, see the LICENSE file.