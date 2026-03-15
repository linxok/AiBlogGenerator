<?php

namespace MyCompany\AiBlogGenerator\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GenerationHistory extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('mycompany_ai_blog_generation', 'entity_id');
    }
}
