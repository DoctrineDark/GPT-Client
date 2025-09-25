# GPT Client

## Environment
* ~ PHP 7.1
* Symfony 4.4

## Installation
* Clone the repository
* Run `composer install`
* Run `npm install`
* Run `npm run build`
* Create a .env file from the .env.example file
* Run `php bin/console doctrine:database:create`
* Run `php bin/console doctrine:migrations:migrate`

# Changelog
### v1.0.0 - Jun 9, 2024
- Init version

### v1.0.1 - Jul 11, 2025
- Added Cloudflare Workers AI + Vectorize search mode

### v1.0.2 - Aug 28, 2025
- Added bge-m3 + OpenSearch search mode

### v1.0.3 - Sep 25, 2025
- Update OpenSearch search engine: add support for search pipelines