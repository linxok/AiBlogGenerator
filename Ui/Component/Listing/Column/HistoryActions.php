<?php

namespace MyCompany\AiBlogGenerator\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class HistoryActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as & $item) {
            $actions = [];
            $entityId = (int) ($item['entity_id'] ?? 0);
            $postId = (int) ($item['post_id'] ?? 0);

            if ($postId > 0) {
                $actions['edit_post'] = [
                    'href' => $this->urlBuilder->getUrl('mageplaza_blog/post/edit', ['id' => $postId]),
                    'label' => __('Edit Post'),
                ];
            }

            if ($entityId > 0) {
                $actions['delete_record'] = [
                    'href' => $this->urlBuilder->getUrl('aibloggenerator/history/delete', ['id' => $entityId]),
                    'label' => __('Delete Record'),
                    'confirm' => [
                        'title' => __('Delete History Record'),
                        'message' => __('Are you sure you want to delete this history record?'),
                    ],
                    'post' => true,
                ];
            }

            $item[$name] = $actions;
        }

        return $dataSource;
    }
}
