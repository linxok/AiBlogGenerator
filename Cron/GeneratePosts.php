<?php

namespace MyCompany\AiBlogGenerator\Cron;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use MyCompany\AiBlogGenerator\Helper\Data;
use MyCompany\AiBlogGenerator\Logger\Logger;
use MyCompany\AiBlogGenerator\Model\BlogGenerator;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryRepository;
use MyCompany\AiBlogGenerator\Model\MageplazaPostManager;

class GeneratePosts
{
    public function __construct(
        private readonly Data $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly BlogGenerator $blogGenerator,
        private readonly MageplazaPostManager $postManager,
        private readonly GenerationHistoryRepository $historyRepository,
        private readonly Logger $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->helper->isCronEnabled() || !$this->helper->isEnabled()) {
            return;
        }

        $storeIds = $this->helper->getTargetStoreViews();
        if (!$storeIds) {
            $storeIds = array_map(static fn ($store) => (int) $store->getId(), $this->storeManager->getStores());
        }

        $count = 0;
        foreach ($storeIds as $storeId) {
            foreach ($this->buildQueueForStore($storeId) as $payload) {
                if ($count >= $this->helper->getPostsPerRun()) {
                    return;
                }
                try {
                    $generated = $this->blogGenerator->generate($payload);
                    $postId = $this->postManager->saveGeneratedPost($generated, [
                        'author_id' => 1,
                        'store_id' => $storeId,
                        'store_ids' => [$storeId],
                        'product_id' => $payload['product_id'] ?? null,
                        'blog_category_ids' => [],
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
                    $this->logger->error('Cron generation failed', ['message' => $exception->getMessage(), 'store_id' => $storeId]);
                }
            }
        }
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
                    'topic' => __('Why %1 is worth attention', $product->getName()),
                    'keywords' => (string) $product->getName(),
                    'tone' => 'professional',
                    'word_count' => $this->helper->getDefaultWordCount($storeId),
                    'store_id' => $storeId,
                    'product_id' => (int) $product->getId(),
                    'category_id' => null,
                    'model' => $this->helper->getDefaultModel($storeId),
                ];
            }
            return $queue;
        }

        if ($topicSource === 'category_pages' && $this->helper->getTargetCategoryId()) {
            return [[
                'topic' => 'Category buying guide',
                'keywords' => 'category guide',
                'tone' => 'expert',
                'word_count' => $this->helper->getDefaultWordCount($storeId),
                'store_id' => $storeId,
                'category_id' => $this->helper->getTargetCategoryId(),
                'model' => $this->helper->getDefaultModel($storeId),
            ]];
        }

        return [[
            'topic' => 'Seasonal ecommerce trends',
            'keywords' => 'ecommerce trends, buying guide',
            'tone' => 'professional',
            'word_count' => $this->helper->getDefaultWordCount($storeId),
            'store_id' => $storeId,
            'model' => $this->helper->getDefaultModel($storeId),
        ]];
    }
}
