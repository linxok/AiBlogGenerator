<?php

namespace MyCompany\AiBlogGenerator\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_GENERAL = 'aibloggenerator/general/';
    public const XML_PATH_CRON = 'aibloggenerator/cron/';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private readonly EncryptorInterface   $encryptor
    )
    {
        parent::__construct($context);
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL . 'enable', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getApiKey(?int $storeId = null): string
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_GENERAL . 'openrouter_api_key',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === '') {
            return '';
        }

        return trim((string)$this->encryptor->decrypt($value));
    }

    public function getDefaultModel(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_GENERAL . 'default_model', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getTemperature(?int $storeId = null): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_GENERAL . 'temperature', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getMaxTokens(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_GENERAL . 'max_tokens', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDefaultWordCount(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_GENERAL . 'default_word_count', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isAutoPublish(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL . 'auto_publish', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CRON . 'cron_enabled', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getCronFrequency(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'cron_frequency', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getPostsPerRun(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'posts_per_run', ScopeConfigInterface::SCOPE_TYPE_DEFAULT));
    }

    public function getTopicSource(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'topic_source', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getTargetCategoryIds(?int $storeId = null): array
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'target_category_ids', ScopeInterface::SCOPE_STORE, $storeId);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value)), static fn($id) => $id > 0));
    }

    public function getCronTone(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'tone', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getCronAuthorId(?int $storeId = null): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'author_id', ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getCronBlogCategoryIds(?int $storeId = null): array
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'blog_category_ids', ScopeInterface::SCOPE_STORE, $storeId);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value)), static fn($id) => $id > 0));
    }

    public function isSkipDuplicatesEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CRON . 'skip_duplicates', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getTargetStoreViews(): array
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_CRON . 'target_store_views', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value)), static fn($id) => $id >= 0));
    }
}
