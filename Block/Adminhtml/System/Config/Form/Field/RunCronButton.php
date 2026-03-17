<?php

namespace MyCompany\AiBlogGenerator\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RunCronButton extends Field
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $url = $this->getUrl('aibloggenerator/cron/run');

        return '<button type="button" class="action-default" onclick="setLocation(\'' . $url . '\')">'
            . (string) __('Run Generation Now')
            . '</button>';
    }
}
