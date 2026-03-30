<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\Cron;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use MyCompany\AiBlogGenerator\Controller\Adminhtml\AbstractGenerate;
use MyCompany\AiBlogGenerator\Cron\GeneratePosts;

class Run extends AbstractGenerate
{
    public function __construct(
        Context $context,
        private readonly GeneratePosts $generatePosts,
        private readonly RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();

        try {
            $result = $this->generatePosts->run(true);
            $message = (string) ($result['message'] ?? __('Generation completed.'));
            $errors = (int) ($result['errors'] ?? 0);

            if ($errors > 0) {
                $message .= ' ' . __('Errors: %1.', $errors);
            }

            $this->messageManager->addSuccessMessage(__($message));
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'aibloggenerator']);
    }
}
