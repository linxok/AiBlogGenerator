<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MyCompany\AiBlogGenerator\Controller\Adminhtml\AbstractGenerate;
use MyCompany\AiBlogGenerator\Model\BlogGenerator;
use MyCompany\AiBlogGenerator\Model\GenerationHistoryRepository;

class Preview extends AbstractGenerate
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly BlogGenerator $blogGenerator,
        private readonly GenerationHistoryRepository $historyRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $payload = $this->getRequest()->getParams();
            $generated = $this->blogGenerator->generate($payload);
            $history = $this->historyRepository->createFromGenerationPayload($payload, $generated, 'preview');

            return $result->setData([
                'success' => true,
                'data' => $generated,
                'history_id' => (int) $history->getId(),
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
