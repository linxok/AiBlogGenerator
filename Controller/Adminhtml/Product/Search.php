<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml\Product;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use MyCompany\AiBlogGenerator\Controller\Adminhtml\AbstractGenerate;

class Search extends AbstractGenerate
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $query = trim((string) $this->getRequest()->getParam('q'));
        $items = [];

        if ($query !== '') {
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku']);
            $collection->addAttributeToFilter([
                ['attribute' => 'name', 'like' => '%' . $query . '%'],
                ['attribute' => 'sku', 'like' => '%' . $query . '%'],
            ]);
            $collection->setPageSize(10);

            foreach ($collection as $product) {
                $items[] = [
                    'id' => (int) $product->getId(),
                    'sku' => (string) $product->getSku(),
                    'label' => sprintf('%s (%s)', (string) $product->getName(), (string) $product->getSku()),
                ];
            }
        }

        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'items' => $items,
        ]);
    }
}
