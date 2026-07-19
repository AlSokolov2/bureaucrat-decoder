# Contributing

This is a personal portfolio template. You are welcome to fork it,
open issues, and submit pull requests.

## Development

```bash
composer install
cp .env.example .env
php artisan key:generate

# Lint
composer lint

# Test
composer test
```

PRs should pass `composer lint && composer test`.

## License

GPLv3. Any derivative work must also be licensed under GPLv3.
