<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MyCompany_AiBlogGenerator::history';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('MyCompany_AiBlogGenerator::history');
        $page->getConfig()->getTitle()->prepend(__('AI Blog Generator'));
        return $page;
    }
}
