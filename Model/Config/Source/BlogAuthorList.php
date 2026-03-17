<?php

namespace MyCompany\AiBlogGenerator\Model\Config\Source;

use Mageplaza\Blog\Model\AuthorFactory;
use Magento\Framework\Data\OptionSourceInterface;

class BlogAuthorList implements OptionSourceInterface
{
    public function __construct(
        private readonly AuthorFactory $authorFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [];

        $collection = $this->authorFactory->create()->getCollection();
        $collection->setOrder('name', 'ASC');

        foreach ($collection as $author) {
            if (!$author->getId()) {
                continue;
            }

            $options[] = [
                'label' => (string) $author->getName(),
                'value' => (string) $author->getId(),
            ];
        }

        return $options;
    }
}
