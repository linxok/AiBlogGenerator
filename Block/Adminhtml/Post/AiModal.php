<?php

namespace MyCompany\AiBlogGenerator\Block\Adminhtml\Post;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\System\Store;
use MyCompany\AiBlogGenerator\Helper\Data;

class AiModal extends Template
{
    protected $_template = 'MyCompany_AiBlogGenerator::post/ai_modal.phtml';

    public function __construct(
        Context $context,
        private readonly Store $systemStore,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly Data $helper,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getStoreOptions(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, true);
    }

    public function getCategoryOptions(): array
    {
        $options = [['value' => '', 'label' => __('-- None --')]];
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addIsActiveFilter();

        foreach ($collection as $category) {
            if (!$category->getId()) {
                continue;
            }
            $options[] = [
                'value' => (string) $category->getId(),
                'label' => (string) $category->getName(),
            ];
        }

        return $options;
    }

    public function getConfig(): string
    {
        return $this->json->serialize([
            'previewUrl' => $this->getUrl('aibloggenerator/generate/preview'),
            'saveUrl' => $this->getUrl('aibloggenerator/generate/save'),
            'productSearchUrl' => $this->getUrl('aibloggenerator/product/search'),
            'defaultWordCount' => $this->helper->getDefaultWordCount(),
            'autoPublish' => $this->helper->isAutoPublish(),
        ]);
    }
}
