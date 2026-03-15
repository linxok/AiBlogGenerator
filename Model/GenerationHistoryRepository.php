<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\Serializer\Json;
use MyCompany\AiBlogGenerator\Model\ResourceModel\GenerationHistory as GenerationHistoryResource;

class GenerationHistoryRepository
{
    public function __construct(
        private readonly GenerationHistoryFactory $historyFactory,
        private readonly GenerationHistoryResource $resource,
        private readonly Json $json
    ) {
    }

    public function create(array $data): GenerationHistory
    {
        $model = $this->historyFactory->create();
        $model->addData($data);

        try {
            $this->resource->save($model);
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(__('Unable to save generation history.'), $exception);
        }

        return $model;
    }

    public function createFromGenerationPayload(array $requestPayload, array $responsePayload, string $status = 'generated'): GenerationHistory
    {
        return $this->create([
            'topic' => (string) ($requestPayload['topic'] ?? ''),
            'status' => $status,
            'model' => (string) ($requestPayload['model'] ?? ''),
            'store_id' => (int) ($requestPayload['store_id'] ?? 0),
            'category_id' => !empty($requestPayload['category_id']) ? (int) $requestPayload['category_id'] : null,
            'product_id' => !empty($requestPayload['product_id'])
                ? (int) $requestPayload['product_id']
                : (!empty($requestPayload['product_ids'][0]) ? (int) $requestPayload['product_ids'][0] : null),
            'keywords' => (string) ($requestPayload['keywords'] ?? ''),
            'tone' => (string) ($requestPayload['tone'] ?? ''),
            'request_payload' => $this->json->serialize($requestPayload),
            'response_payload' => $this->json->serialize($responsePayload),
            'preview_html' => (string) ($responsePayload['content_html'] ?? ''),
            'is_published' => 0,
        ]);
    }
}
