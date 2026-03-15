<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CronFrequency implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['label' => __('Hourly'), 'value' => 'hourly'],
            ['label' => __('Daily'), 'value' => 'daily'],
            ['label' => __('Weekly'), 'value' => 'weekly'],
        ];
    }
}
