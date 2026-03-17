<?php

namespace MyCompany\AiBlogGenerator\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CategoryTree extends Field
{
    protected $_template = 'MyCompany_AiBlogGenerator::system/config/category_tree.phtml';

    public function __construct(
        Context $context,
        private readonly CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->setElement($element);

        return $this->_toHtml();
    }

    public function getCategoryTree(): array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->setOrder('position', 'ASC');

        $categories = [];
        foreach ($collection as $category) {
            if ((int) $category->getLevel() < 1) {
                continue;
            }

            $categories[(int) $category->getId()] = [
                'id' => (int) $category->getId(),
                'name' => (string) $category->getName(),
                'level' => (int) $category->getLevel(),
                'parent_id' => (int) $category->getParentId(),
            ];
        }

        return $this->buildTree($categories);
    }

    public function getSelectedCategories(): array
    {
        $value = (string) $this->getElement()->getValue();
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value)), static fn ($id) => $id > 0));
    }

    public function getElementName(): string
    {
        return (string) $this->getElement()->getName();
    }

    public function getElementId(): string
    {
        return (string) $this->getElement()->getHtmlId();
    }

    public function renderCategoryTree(array $categories, array $selected, string $elementId): string
    {
        if ($categories === []) {
            return '';
        }

        $html = '';
        foreach ($categories as $category) {
            $hasChildren = !empty($category['children']);
            $categoryId = (int) $category['id'];
            $isChecked = in_array($categoryId, $selected, true);

            $html .= '<div class="category-item' . ($hasChildren ? ' has-children' : '') . '">';
            if ($hasChildren) {
                $html .= '<span class="category-toggle" id="' . $this->escapeHtmlAttr($elementId) . '_toggle_' . $categoryId . '" onclick="categoryTreeToggle_' . $this->escapeJs($elementId) . '(' . $categoryId . ')">+</span>';
            } else {
                $html .= '<span class="category-toggle" style="visibility:hidden;">·</span>';
            }

            $html .= '<input type="checkbox" class="category-checkbox" value="' . $categoryId . '" id="' . $this->escapeHtmlAttr($elementId) . '_cat_' . $categoryId . '"';
            if ($isChecked) {
                $html .= ' checked="checked"';
            }
            if ($hasChildren) {
                $html .= ' onchange="categoryTreeCheckboxChange_' . $this->escapeJs($elementId) . '(' . $categoryId . ', this.checked)"';
            }
            $html .= ' />';
            $html .= '<label class="category-label" for="' . $this->escapeHtmlAttr($elementId) . '_cat_' . $categoryId . '">' . $this->escapeHtml((string) $category['name']) . '</label>';

            if ($hasChildren) {
                $html .= '<div class="category-children" id="' . $this->escapeHtmlAttr($elementId) . '_children_' . $categoryId . '">';
                $html .= $this->renderCategoryTree($category['children'], $selected, $elementId);
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($parentId === null) {
                if ((int) $category['level'] === 1) {
                    $tree[$category['id']] = $category;
                    $tree[$category['id']]['children'] = $this->buildTree($categories, (int) $category['id']);
                }

                continue;
            }

            if ((int) $category['parent_id'] === $parentId) {
                $tree[$category['id']] = $category;
                $tree[$category['id']]['children'] = $this->buildTree($categories, (int) $category['id']);
            }
        }

        return $tree;
    }
}
