<?php

declare(strict_types=1);

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductSelectionResolver
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function resolveProductIds(array $payload, int $storeId): array
    {
        $productIds = [];

        if (!empty($payload['product_id'])) {
            $productIds[] = (int) $payload['product_id'];
        }

        $skus = $this->extractSkus((string) ($payload['product_skus'] ?? ''));
        if ($skus) {
            $collection = $this->collectionFactory->create();
            $collection->setStoreId($storeId);
            $collection->addStoreFilter($storeId);
            $collection->addAttributeToSelect(['sku']);
            $collection->addAttributeToFilter('sku', ['in' => $skus]);

            foreach ($collection as $product) {
                $productIds[] = (int) $product->getId();
            }
        }

        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));

        return $productIds;
    }

    public function extractSkus(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn ($sku) => $sku !== '');

        return array_values(array_unique($parts));
    }
}
