# Rabble Content Bundle
The content bundle is for managing content within Rabble. You can add content types using Symfony configuration files.

# Installation
Install the bundle by running
```sh
composer require rabble/content-bundle
```

Add the following class to your `config/bundles.php` file:
```php
return [
    ...
    Rabble\ContentBundle\RabbleContentBundle::class => ['all' => true],
]
```
