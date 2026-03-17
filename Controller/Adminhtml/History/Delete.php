<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryRepository;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'MyCompany_AiBlogGenerator::history';

    public function __construct(
        Context $context,
        private readonly GenerationHistoryRepository $historyRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('id');

        if ($id <= 0) {
            $this->messageManager->addErrorMessage(__('History record is missing.'));
            return $resultRedirect->setPath('aibloggenerator/history/index');
        }

        try {
            $this->historyRepository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('History record has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete history record.'));
        }

        return $resultRedirect->setPath('aibloggenerator/history/index');
    }
}
