# Circular Protocol PHP API

The `CircularProtocolAPI` class is a PHP implementation for interacting with the Circular Labs blockchain. It provides methods for performing various operations such as fetching wallet information, registering a wallet, fetching asset information, and sending transactions.

## Dependencies

This class requires the following dependencies:

- PHP 8.0 or higher
- Composer
- Elliptic PHP library

## Installation via Composer

```bash
composer require circular-protocol/circular-protocol-api
```

## Installation via Repository

First, you need to install Composer and then run the following command to install the Elliptic library:

```bash
composer require simplito/elliptic-php
```

## Docs

Read the docs on [GitBook](https://circular-protocol.gitbook.io/circular-sdk/api-docs/php)

## CAVEATS

* Please note the following:
    * Error handling is very simplistic and should be tailored to your specific needs.
    * The fetch method in this PHP class does not behave exactly like the JavaScript fetch function especially regarding error handling.
      -- Consider replacing it with a more robust solution like Guzzle or CURL in a production environment.

## Useful Links

- [Documentation](https://circular-protocol.gitbook.io/circular-sdk/api-docs/php)
- [Packagist Repository](https://packagist.org/packages/circular-protocol/circular-protocol-api)
- [GitHub](https://github.com/circular-protocol/circular-php)

## Disclaimer

This class is provided as-is, and it is up to the user to ensure that it is used correctly and securely. Always remember to keep your private keys secure and never share them with anyone. Always test your code thoroughly before deploying it in a production environment.

## License

This library is open-source and available for both private and commercial use. For detailed terms, please refer to the LICENSE file provided in the repository.
