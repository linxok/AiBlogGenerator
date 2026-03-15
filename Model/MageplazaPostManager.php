<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mageplaza\Blog\Model\PostFactory;

class MageplazaPostManager
{
    public function __construct(
        private readonly PostFactory $postFactory,
        private readonly DateTime $dateTime
    ) {
    }

    public function saveGeneratedPost(array $generatedData, array $payload, ?int $existingPostId = null): int
    {
        $post = $this->postFactory->create();
        if ($existingPostId) {
            $post->load($existingPostId);
        }

        $post->addData([
            'name' => (string) ($generatedData['title'] ?? ''),
            'short_description' => (string) ($generatedData['short_description'] ?? ''),
            'post_content' => (string) ($generatedData['content_html'] ?? ''),
            'url_key' => (string) ($generatedData['url_key'] ?? ''),
            'meta_title' => (string) ($generatedData['meta_title'] ?? ''),
            'meta_description' => (string) ($generatedData['meta_description'] ?? ''),
            'meta_keywords' => implode(',', $generatedData['tags'] ?? []),
            'publish_date' => $this->dateTime->gmtDate(),
            'enabled' => !empty($payload['auto_publish']) ? 1 : 0,
            'in_rss' => 1,
            'allow_comment' => 1,
            'author_id' => (int) ($payload['author_id'] ?? 1),
            'store_ids' => $payload['store_ids'] ?? [(int) ($payload['store_id'] ?? 0)],
        ]);

        if (!empty($payload['blog_category_ids'])) {
            $post->setCategoriesIds(array_map('intval', (array) $payload['blog_category_ids']));
        }

        if (!empty($payload['product_id'])) {
            $post->setProductsData([(int) $payload['product_id'] => ['position' => 0]]);
        }

        try {
            $post->save();
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(__('Unable to save Mageplaza blog post.'), $exception);
        }

        return (int) $post->getId();
    }
}
