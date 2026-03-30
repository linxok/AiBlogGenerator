# MyCompany_AiBlogGenerator

Magento 2 module for AI-powered blog generation for Mageplaza Blog using OpenRouter.

This module extends Mageplaza Blog:
https://github.com/mageplaza/magento-2-blog

## Features

- Generate SEO-oriented blog post previews from the admin panel
- Save generated content directly into Mageplaza Blog posts
- Use the module-level default AI model from system configuration
- Searchable `Default Model` field in admin configuration
- Product and category context support for richer prompts
- Generation history grid in the Magento admin area
- Cron-based automatic generation support
- OpenRouter model list loading and chat completion integration

## Requirements

- PHP `^8.2`
- Magento 2.4.7 compatible stack
- `magento/framework ^103.0`
- `mageplaza/magento-2-blog-extension ^4.3`
- Valid OpenRouter API key

Mageplaza Blog repository:
https://github.com/mageplaza/magento-2-blog

## Installation

Install the dependency in your Magento project and place the module in the codebase.

### Option 1: as a VCS package

Add the repository to your Magento root `composer.json`, then require the package.

```bash
composer require mycompany/module-ai-blog-generator
```

### Option 2: manual module placement

Copy the module to:

```text
app/code/MyCompany/AiBlogGenerator
```

Then run:

```bash
bin/magento module:enable MyCompany_AiBlogGenerator
bin/magento setup:upgrade
bin/magento cache:flush
```

If you use production mode or need regenerated DI assets:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

## Configuration

Go to:

```text
Stores -> Configuration -> MyCompany -> AI Blog Generator
```

Configure at least:

- Enable
- OpenRouter API Key
- Default Model
- Temperature
- Max Tokens
- Default Word Count
- Auto Publish Generated Posts

## Usage

### Manual generation

Open a Mageplaza Blog post in admin and use the `Generate with AI` action.

The modal supports:

- Topic
- Keywords
- Tone
- Word count
- Store views
- Category
- Product context

The AI model is taken from the module configuration, not from the modal.

### Automatic generation

Configure cron options in:

```text
Stores -> Configuration -> MyCompany -> AI Blog Generator -> Cron Automatic Generation
```

## Logging

Module logs are written to:

```text
var/log/ai_blog_generator.log
```

## Main module structure

```text
MyCompany/AiBlogGenerator/
├── Block/
├── Controller/
├── Cron/
├── Helper/
├── Logger/
├── Model/
├── etc/
├── view/
├── composer.json
└── registration.php
```

## Notes

- The OpenRouter API key must be stored in Magento configuration.
- The key should never be committed to Git.
- Mageplaza Blog must be installed and enabled before this module is enabled.
- Mageplaza Blog project: https://github.com/mageplaza/magento-2-blog

## License

Currently declared as `proprietary` in `composer.json`. Adjust this before publishing publicly if needed.
