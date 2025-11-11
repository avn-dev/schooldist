# Spam detection

## Installation

Install the package via composer

```bash
composer require fidelo-software/laravel-spamshield
```

## Basic usage
Generate spamshield instance with strategies you want to use
```php
$spamshield = new \FideloSoftware\Spam\SpamShield(
    [
        new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3),
        new \FideloSoftware\Spam\Strategies\TimestampStrategy($store, 5),
        new \FideloSoftware\Spam\Strategies\LinkStrategy(0),
        new \FideloSoftware\Spam\Strategies\ValueBlacklistStrategy(['name' => ['John Doe']]),
    ], 
    $store
    $logger // optional
);
```

Start all strategies onload processes when form is initializing
```php
// Form initialization 
$spamshield->onload($form, $request);
```

Include all html parts of defined strategies in your form rendering process
```php
// Form rendering
echo $spamshield->html($form);
```

Execute all strategies on form submit to check for spam
```php
// Submit
try {
    $spamshield->detect($form, $request);
} catch(\FideloSoftware\Spam\Exceptions\BannedException $e) {
    ...
} catch(\FideloSoftware\Spam\Exceptions\SpamDetectionException $e) {
    ...
}
```
## Check user request globally
```php
// Check if user is globally banned after to many attempts
\FideloSoftware\Spam\SpamShield::isBanned($store, $request);
```
