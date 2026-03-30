<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MyCompany\AiBlogGenerator\Model\ModelProvider;

class ModelList implements OptionSourceInterface
{
    public function __construct(private readonly ModelProvider $modelProvider)
    {
    }

    public function toOptionArray(): array
    {
        $models = $this->modelProvider->getModelOptions();

        if (!$models) {
            return [
                ['label' => __('Default OpenRouter Model'), 'value' => 'openai/gpt-4o-mini']
            ];
        }

        return $models;
    }
}
