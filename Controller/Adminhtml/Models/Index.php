<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\Models;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MyCompany\AiBlogGenerator\Controller\Adminhtml\AbstractGenerate;
use MyCompany\AiBlogGenerator\Model\ModelProvider;

class Index extends AbstractGenerate
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ModelProvider $modelProvider
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'items' => $this->modelProvider->getModelOptions(),
        ]);
    }
}
