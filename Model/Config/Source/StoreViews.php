<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store;

class StoreViews implements OptionSourceInterface
{
    public function __construct(private readonly Store $systemStore)
    {
    }

    public function toOptionArray(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, true);
    }
}
