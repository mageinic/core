# MageINIC Core User Guide

## 1. How to install

### Method 1: Install via composer [Recommend]

Run the following command in Magento 2 root folder

```
composer require mageinic/core

php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## 2. Get Support

- Feel free to [contact us](https://www.mageinic.com/contact.html) if you have any further questions.
- Like this project, Give us a **Star**