<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use MyCompany\AiBlogGenerator\Controller\Adminhtml\AbstractGenerate;
use MyCompany\AiBlogGenerator\Model\BlogGenerator;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryRepository;
use MyCompany\AiBlogGenerator\Model\MageplazaPostManager;

class Save extends AbstractGenerate
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly BlogGenerator $blogGenerator,
        private readonly MageplazaPostManager $postManager,
        private readonly GenerationHistoryRepository $historyRepository,
        private readonly Json $json
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $payload = $this->getRequest()->getParams();
            $generated = !empty($payload['generated_json'])
                ? $this->json->unserialize((string) $payload['generated_json'])
                : $this->blogGenerator->generate($payload);

            $postId = $this->postManager->saveGeneratedPost(
                $generated,
                [
                    'author_id' => $this->_auth->getUser()->getId(),
                    'store_id' => (int) ($payload['store_id'] ?? 0),
                    'store_ids' => isset($payload['store_ids']) ? (array) $payload['store_ids'] : [(int) ($payload['store_id'] ?? 0)],
                    'product_id' => !empty($payload['product_id']) ? (int) $payload['product_id'] : null,
                    'blog_category_ids' => !empty($payload['blog_category_ids']) ? (array) $payload['blog_category_ids'] : [],
                    'auto_publish' => !empty($payload['auto_publish']) ? 1 : 0,
                ],
                !empty($payload['post_id']) ? (int) $payload['post_id'] : null
            );

            $history = $this->historyRepository->create([
                'topic' => (string) ($payload['topic'] ?? $generated['title'] ?? ''),
                'status' => 'saved',
                'model' => (string) ($generated['model'] ?? ''),
                'store_id' => (int) ($payload['store_id'] ?? 0),
                'post_id' => $postId,
                'category_id' => !empty($payload['category_id']) ? (int) $payload['category_id'] : null,
                'product_id' => !empty($payload['product_id']) ? (int) $payload['product_id'] : null,
                'keywords' => (string) ($payload['keywords'] ?? ''),
                'tone' => (string) ($payload['tone'] ?? ''),
                'request_payload' => $this->json->serialize($payload),
                'response_payload' => $this->json->serialize($generated),
                'preview_html' => (string) ($generated['content_html'] ?? ''),
                'is_published' => !empty($payload['auto_publish']) ? 1 : 0,
            ]);

            return $result->setData([
                'success' => true,
                'post_id' => $postId,
                'history_id' => (int) $history->getId(),
                'edit_url' => $this->_url->getUrl('mageplaza_blog/post/edit', ['id' => $postId]),
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
