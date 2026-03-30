<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CategoryList implements OptionSourceInterface
{
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function toOptionArray(): array
    {
        $options = [
            ['label' => __('-- Please Select --'), 'value' => '0']
        ];

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addIsActiveFilter();

        foreach ($collection as $category) {
            if (!$category->getId()) {
                continue;
            }
            $options[] = [
                'label' => (string) $category->getName(),
                'value' => (string) $category->getId(),
            ];
        }

        return $options;
    }
}
