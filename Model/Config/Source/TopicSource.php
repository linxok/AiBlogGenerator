<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TopicSource implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['label' => __('Manual Queue'), 'value' => 'manual'],
            ['label' => __('New Products'), 'value' => 'new_products'],
            ['label' => __('Category SEO'), 'value' => 'category_pages'],
        ];
    }
}
