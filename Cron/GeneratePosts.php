<?php

namespace MyCompany\AiBlogGenerator\Cron;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Blog\Model\PostFactory;
use MyCompany\AiBlogGenerator\Helper\Data;
use MyCompany\AiBlogGenerator\Logger\Logger;
use MyCompany\AiBlogGenerator\Model\BlogGenerator;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryFactory;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryRepository;
use MyCompany\AiBlogGenerator\Model\MageplazaPostManager;

class GeneratePosts
{
    public function __construct(
        private readonly Data $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly BlogGenerator $blogGenerator,
        private readonly MageplazaPostManager $postManager,
        private readonly GenerationHistoryFactory $generationHistoryFactory,
        private readonly GenerationHistoryRepository $historyRepository,
        private readonly PostFactory $postFactory,
        private readonly Logger $logger
    ) {
    }

    public function execute(): void
    {
        $this->run();
    }

    public function run(bool $force = false): array
    {
        if (!$this->helper->isCronEnabled() || !$this->helper->isEnabled()) {
            return [
                'processed' => 0,
                'errors' => 0,
                'message' => 'Cron generation is disabled.',
            ];
        }

        if (!$force && !$this->canRunNow()) {
            return [
                'processed' => 0,
                'errors' => 0,
                'message' => __('Cron frequency window has not elapsed yet.')->render(),
            ];
        }

        $storeIds = $this->helper->getTargetStoreViews();
        if (!$storeIds) {
            $storeIds = array_map(static fn ($store) => (int) $store->getId(), $this->storeManager->getStores());
        }

        $count = 0;
        $errors = 0;
        foreach ($storeIds as $storeId) {
            foreach ($this->buildQueueForStore($storeId) as $payload) {
                if ($count >= $this->helper->getPostsPerRun()) {
                    return [
                        'processed' => $count,
                        'errors' => $errors,
                        'message' => __('Generated %1 post(s).', $count)->render(),
                    ];
                }

                if ($this->helper->isSkipDuplicatesEnabled($storeId) && $this->isDuplicatePayload($payload, $storeId)) {
                    $this->historyRepository->create([
                        'topic' => (string) ($payload['topic'] ?? ''),
                        'status' => 'cron_skipped_duplicate',
                        'model' => (string) ($payload['model'] ?? ''),
                        'store_id' => $storeId,
                        'category_id' => !empty($payload['category_id']) ? (int) $payload['category_id'] : null,
                        'product_id' => !empty($payload['product_id']) ? (int) $payload['product_id'] : null,
                        'keywords' => (string) ($payload['keywords'] ?? ''),
                        'tone' => (string) ($payload['tone'] ?? ''),
                        'request_payload' => json_encode($payload),
                        'response_payload' => null,
                        'preview_html' => null,
                        'is_published' => 0,
                    ]);
                    continue;
                }

                try {
                    $generated = $this->blogGenerator->generate($payload);
                    $postId = $this->postManager->saveGeneratedPost($generated, [
                        'author_id' => $this->helper->getCronAuthorId($storeId),
                        'store_id' => $storeId,
                        'store_ids' => [$storeId],
                        'product_id' => $payload['product_id'] ?? null,
                        'blog_category_ids' => $this->helper->getCronBlogCategoryIds($storeId),
                        'auto_publish' => (int) $this->helper->isAutoPublish($storeId),
                    ]);

                    $this->historyRepository->create([
                        'topic' => (string) ($payload['topic'] ?? ''),
                        'status' => 'cron_saved',
                        'model' => (string) ($payload['model'] ?? ''),
                        'store_id' => $storeId,
                        'post_id' => $postId,
                        'category_id' => !empty($payload['category_id']) ? (int) $payload['category_id'] : null,
                        'product_id' => !empty($payload['product_id']) ? (int) $payload['product_id'] : null,
                        'keywords' => (string) ($payload['keywords'] ?? ''),
                        'tone' => (string) ($payload['tone'] ?? ''),
                        'request_payload' => json_encode($payload),
                        'response_payload' => json_encode($generated),
                        'preview_html' => (string) ($generated['content_html'] ?? ''),
                        'is_published' => (int) $this->helper->isAutoPublish($storeId),
                    ]);
                    $count++;
                } catch (\Throwable $exception) {
                    $errors++;
                    $this->logger->error('Cron generation failed', ['message' => $exception->getMessage(), 'store_id' => $storeId]);
                }
            }
        }

        return [
            'processed' => $count,
            'errors' => $errors,
            'message' => __('Generated %1 post(s).', $count)->render(),
        ];
    }

    private function canRunNow(): bool
    {
        $collection = $this->generationHistoryFactory->create()->getCollection();
        $collection->addFieldToFilter('status', ['in' => ['cron_saved', 'cron_skipped_duplicate']]);
        $collection->setOrder('generated_at', 'DESC');
        $collection->setPageSize(1);

        $lastItem = $collection->getFirstItem();
        if (!$lastItem->getId()) {
            return true;
        }

        $generatedAt = strtotime((string) $lastItem->getData('generated_at'));
        if (!$generatedAt) {
            return true;
        }

        $requiredSeconds = match ($this->helper->getCronFrequency()) {
            'hourly' => 3600,
            'weekly' => 604800,
            default => 86400,
        };

        return (time() - $generatedAt) >= $requiredSeconds;
    }

    private function buildQueueForStore(int $storeId): array
    {
        $topicSource = $this->helper->getTopicSource();
        if ($topicSource === 'new_products') {
            $queue = [];
            $collection = $this->productCollectionFactory->create();
            $collection->setStoreId($storeId);
            $collection->addStoreFilter($storeId);
            $collection->addAttributeToSelect(['name']);
            $collection->setPageSize($this->helper->getPostsPerRun());
            $collection->setOrder('created_at', 'DESC');

            foreach ($collection as $product) {
                $queue[] = [
                    'topic' => (string) __('Why %1 is worth attention', $product->getName()),
                    'keywords' => (string) $product->getName(),
                    'tone' => $this->resolveTone($storeId, 'professional'),
                    'word_count' => $this->helper->getDefaultWordCount($storeId),
                    'store_id' => $storeId,
                    'product_id' => (int) $product->getId(),
                    'category_id' => null,
                    'model' => $this->helper->getDefaultModel($storeId),
                ];
            }

            return $queue;
        }

        $targetCategoryIds = $this->helper->getTargetCategoryIds($storeId);
        if ($topicSource === 'category_pages' && $targetCategoryIds !== []) {
            $selectedCategoryId = $targetCategoryIds[array_rand($targetCategoryIds)];
            $category = $this->categoryFactory->create()->load($selectedCategoryId);
            $categoryName = (string) $category->getName();

            return [[
                'topic' => $categoryName !== '' ? (string) __('How to choose %1', $categoryName) : 'Category buying guide',
                'keywords' => $categoryName !== '' ? $categoryName . ', category guide' : 'category guide',
                'tone' => $this->resolveTone($storeId, 'expert'),
                'word_count' => $this->helper->getDefaultWordCount($storeId),
                'store_id' => $storeId,
                'category_id' => (int) $selectedCategoryId,
                'model' => $this->helper->getDefaultModel($storeId),
            ]];
        }

        return [[
            'topic' => 'Seasonal ecommerce trends',
            'keywords' => 'ecommerce trends, buying guide',
            'tone' => $this->resolveTone($storeId, 'professional'),
            'word_count' => $this->helper->getDefaultWordCount($storeId),
            'store_id' => $storeId,
            'model' => $this->helper->getDefaultModel($storeId),
        ]];
    }

    private function resolveTone(int $storeId, string $default): string
    {
        $tone = trim($this->helper->getCronTone($storeId));

        return $tone !== '' ? $tone : $default;
    }

    private function isDuplicatePayload(array $payload, int $storeId): bool
    {
        $topic = trim((string) ($payload['topic'] ?? ''));
        $productId = !empty($payload['product_id']) ? (int) $payload['product_id'] : null;
        $categoryId = !empty($payload['category_id']) ? (int) $payload['category_id'] : null;

        $historyCollection = $this->generationHistoryFactory->create()->getCollection();
        $historyCollection->addFieldToFilter('status', ['in' => ['cron_saved', 'saved']]);
        $historyCollection->addFieldToFilter('store_id', $storeId);
        if ($productId) {
            $historyCollection->addFieldToFilter('product_id', $productId);
        } elseif ($categoryId) {
            $historyCollection->addFieldToFilter('category_id', $categoryId);
        } elseif ($topic !== '') {
            $historyCollection->addFieldToFilter('topic', $topic);
        }
        $historyCollection->setPageSize(1);

        if ($historyCollection->getSize() > 0) {
            return true;
        }

        if ($topic === '') {
            return false;
        }

        $postCollection = $this->postFactory->create()->getCollection();
        $postCollection->addFieldToFilter('name', $topic);
        $postCollection->setPageSize(1);

        return $postCollection->getSize() > 0;
    }
}
