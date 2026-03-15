<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use MyCompany\AiBlogGenerator\Helper\Data;
use MyCompany\AiBlogGenerator\Logger\Logger;

class OpenRouterClient
{
    private const MODELS_URL = 'https://openrouter.ai/api/v1/models';
    private const CHAT_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private readonly Curl $curl,
        private readonly Json $json,
        private readonly Data $helper,
        private readonly Logger $logger
    ) {
    }

    public function fetchModels(): array
    {
        $start = microtime(true);
        $this->curl->setHeaders([
            'Accept' => 'application/json',
        ]);
        $this->curl->get(self::MODELS_URL);
        $status = (int) $this->curl->getStatus();
        $body = (string) $this->curl->getBody();

        $this->logger->info('OpenRouter model list request', [
            'status' => $status,
            'duration' => microtime(true) - $start,
        ]);

        if ($status >= 400) {
            throw new LocalizedException(__('Unable to fetch OpenRouter models.'));
        }

        return $this->json->unserialize($body);
    }

    public function chat(array $messages, ?string $model = null, ?float $temperature = null, ?int $maxTokens = null, ?int $storeId = null): array
    {
        $apiKey = $this->helper->getApiKey($storeId);
        if ($apiKey === '') {
            throw new LocalizedException(__('OpenRouter API key is not configured.'));
        }

        $payload = [
            'model' => $model ?: $this->helper->getDefaultModel($storeId),
            'temperature' => $temperature ?? $this->helper->getTemperature($storeId),
            'max_tokens' => $maxTokens ?? $this->helper->getMaxTokens($storeId),
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
        ];

        $start = microtime(true);
        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
        $this->curl->post(self::CHAT_URL, $this->json->serialize($payload));
        $status = (int) $this->curl->getStatus();
        $body = (string) $this->curl->getBody();

        $this->logger->info('OpenRouter chat request', [
            'model' => $payload['model'],
            'status' => $status,
            'duration' => microtime(true) - $start,
        ]);

        if ($status >= 400) {
            $this->logger->error('OpenRouter chat error', ['status' => $status, 'body' => $body]);
            throw new LocalizedException(__('OpenRouter request failed.'));
        }

        return $this->json->unserialize($body);
    }
}
