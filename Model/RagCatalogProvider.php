<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use MyCompany\AiBlogGenerator\Model\Cache\Type as CacheType;

class RagCatalogProvider
{
    private const CACHE_LIFETIME = 21600;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly CacheInterface $cache,
        private readonly Json $json
    ) {
    }

    public function getContext(?int $productId, ?int $categoryId, int $storeId): array
    {
        if ($productId) {
            return $this->getProductContext($productId, $storeId);
        }

        if ($categoryId) {
            return $this->getCategoryContext($categoryId, $storeId);
        }

        return [];
    }

    public function getProductsContext(array $productIds, int $storeId): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$productIds) {
            return [];
        }

        if (count($productIds) === 1) {
            return $this->getProductContext((int) $productIds[0], $storeId);
        }

        $items = [];
        foreach ($productIds as $productId) {
            try {
                $items[] = $this->getProductContext((int) $productId, $storeId);
            } catch (NoSuchEntityException) {
                continue;
            }
        }

        if (!$items) {
            return [];
        }

        return [
            'type' => 'products',
            'count' => count($items),
            'items' => $items,
        ];
    }

    public function getProductContext(int $productId, int $storeId): array
    {
        $cacheKey = sprintf('mycompany_ai_blog_product_%d_%d', $productId, $storeId);
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            return $this->json->unserialize($cached);
        }

        $product = $this->productRepository->getById($productId, false, $storeId);
        $product->setStoreId($storeId);
        $attributes = [];
        foreach ($product->getCustomAttributes() ?: [] as $attribute) {
            $value = $attribute->getValue();
            if (is_scalar($value) && $value !== '') {
                $attributes[$attribute->getAttributeCode()] = (string) $value;
            }
        }

        $context = [
            'type' => 'product',
            'id' => $product->getId(),
            'name' => (string) $product->getName(),
            'short_description' => (string) $product->getData('short_description'),
            'description' => (string) $product->getData('description'),
            'price' => (string) $product->getPrice(),
            'url' => (string) $product->getProductUrl(),
            'category_ids' => $product->getCategoryIds(),
            'attributes' => $attributes,
        ];

        $this->cache->save($this->json->serialize($context), $cacheKey, [CacheType::CACHE_TAG], self::CACHE_LIFETIME);

        return $context;
    }

    public function getCategoryContext(int $categoryId, int $storeId): array
    {
        $cacheKey = sprintf('mycompany_ai_blog_category_%d_%d', $categoryId, $storeId);
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            return $this->json->unserialize($cached);
        }

        $category = $this->categoryRepository->get($categoryId, $storeId);
        $category->setStoreId($storeId);
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToSelect(['name', 'short_description', 'description', 'price']);
        $collection->addCategoriesFilter(['in' => [$categoryId]]);
        $collection->setPageSize(5);

        $topProducts = [];
        foreach ($collection as $product) {
            $topProducts[] = [
                'id' => (int) $product->getId(),
                'name' => (string) $product->getName(),
                'short_description' => (string) $product->getData('short_description'),
                'description' => (string) $product->getData('description'),
                'price' => (string) $product->getPrice(),
                'url' => (string) $product->setStoreId($storeId)->getProductUrl(),
            ];
        }

        $context = [
            'type' => 'category',
            'id' => $category->getId(),
            'name' => (string) $category->getName(),
            'description' => (string) $category->getDescription(),
            'url' => (string) $category->getUrl(),
            'top_products' => $topProducts,
        ];

        $this->cache->save($this->json->serialize($context), $cacheKey, [CacheType::CACHE_TAG], self::CACHE_LIFETIME);

        return $context;
    }

    public function resolveProductName(int $productId, int $storeId): ?string
    {
        try {
            return (string) $this->productRepository->getById($productId, false, $storeId)->getName();
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
