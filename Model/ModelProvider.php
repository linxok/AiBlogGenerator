<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MyCompany\AiBlogGenerator\Model\Cache\Type as CacheType;

class ModelProvider
{
    private const CACHE_KEY = 'mycompany_ai_blog_generator_openrouter_models';
    private const CACHE_LIFETIME = 86400;

    public function __construct(
        private readonly OpenRouterClient $openRouterClient,
        private readonly CacheInterface $cache,
        private readonly Json $json
    ) {
    }

    public function getModels(): array
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            return $this->json->unserialize($cached);
        }

        $response = $this->openRouterClient->fetchModels();
        $data = $response['data'] ?? [];
        $this->cache->save($this->json->serialize($data), self::CACHE_KEY, [CacheType::CACHE_TAG], self::CACHE_LIFETIME);

        return $data;
    }

    public function getModelOptions(): array
    {
        $options = [];
        foreach ($this->getModels() as $model) {
            $id = (string) ($model['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $options[] = [
                'label' => (string) ($model['name'] ?? $id),
                'value' => $id,
            ];
        }

        return $options;
    }
}
