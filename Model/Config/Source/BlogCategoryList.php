<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Mageplaza\Blog\Model\CategoryFactory;
use Magento\Framework\Data\OptionSourceInterface;

class BlogCategoryList implements OptionSourceInterface
{
    public function __construct(
        private readonly CategoryFactory $categoryFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [];

        $collection = $this->categoryFactory->create()->getCollection();
        $collection->setOrder('name', 'ASC');

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
