# Laravel Google Shopping

In this package Google Shopping API has been used you can use this package to consume Google shopping API. 

## Getting Started

Follow the instruction to install and use this package.

### Installing

Add Laravel-Ebay to your composer file via the composer require command:


```bash
$ composer require hkonnet/laravel-google-shopping
```

Or add it to `composer.json` manually:

```json
"require":{
    "hkonnet/laravel-google-shopping": "^0.1"
}
```

Register the service provider by adding it to the providers key in config/app.php. Also register the facade by adding it to the aliases key in config/app.php.

**Laravel 5.1 or greater**
```php
'providers' => [
    ...
    Hkonnet\LaravelGoogleShopping\Providers\GoogleShoppingServiceProvider::class, 
]
```
**Laravel 5**
```php
'providers' => [
    ...
    'Hkonnet\LaravelGoogleShopping\Providers\GoogleShoppingServiceProvider', 
]

```

Next to get started, you'll need to publish all vendor assets:

```bash
$ php artisan vendor:publish --provider="Hkonnet\LaravelGoogleShopping\Providers\GoogleShoppingServiceProvider"
```
This will create a **config/google_shopping.php** file in your app that you can modify to set your configuration.

###Configuration
After installation, you will need to add your google shopping settings to your .env file.

- You can set mode to sandbox or production
- You can set Application name
- You can set configuration directory e.g in the root you can create .credential folder 
- You can set auth type (Right now auth via Service account credentials are supported)
- Set Service account file path.

**Note** : How to create service account credentials here the details for you 
https://developers.google.com/shopping-content/v2/how-tos/service-accounts
