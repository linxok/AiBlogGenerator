<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\Model\AbstractModel;
use MyCompany\AiBlogGenerator\Model\ResourceModel\GenerationHistory as ResourceModel;

class GenerationHistory extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
