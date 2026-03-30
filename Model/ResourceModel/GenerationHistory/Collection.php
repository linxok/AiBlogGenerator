<?php

namespace MyCompany\AiBlogGenerator\Model\ResourceModel\GenerationHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MyCompany\AiBlogGenerator\Model\GenerationHistory as Model;
use MyCompany\AiBlogGenerator\Model\ResourceModel\GenerationHistory as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
