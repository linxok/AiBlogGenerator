<?php

namespace MyCompany\AiBlogGenerator\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use MyCompany\AiBlogGenerator\Model\Config\Source\ModelList;

class SearchableModelSelect extends Field
{
    public function __construct(
        Context $context,
        private readonly ModelList $modelList,
        private readonly Escaper $escaper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->setValues($this->modelList->toOptionArray());

        $searchId = $element->getHtmlId() . '_search';
        $selectId = $element->getHtmlId();
        $selectedValue = (string) $element->getValue();
        $selectedLabel = '';

        foreach ((array) $element->getValues() as $option) {
            if ((string) ($option['value'] ?? '') === $selectedValue) {
                $selectedLabel = (string) ($option['label'] ?? '');
                break;
            }
        }

        $searchValue = $selectedLabel !== '' ? $selectedLabel : $selectedValue;
        $searchHtml = '<input type="text"'
            . ' id="' . $this->escaper->escapeHtmlAttr($searchId) . '"'
            . ' class="admin__control-text"'
            . ' value="' . $this->escaper->escapeHtmlAttr($searchValue) . '"'
            . ' placeholder="' . $this->escaper->escapeHtmlAttr((string) __('Type to filter models')) . '"'
            . ' autocomplete="off"'
            . ' style="margin-bottom:10px;"'
            . '/>';

        $script = <<<HTML
<script>
require(['jquery'], function ($) {
    var \$select = $('#{$selectId}');
    var \$search = $('#{$searchId}');

    if (!\$select.length || !\$search.length || \$search.data('mycompanyModelSearchReady')) {
        return;
    }

    \$search.data('mycompanyModelSearchReady', true);

    var options = [];
    \$select.find('option').each(function () {
        options.push({
            value: String($(this).attr('value') || ''),
            label: String($(this).text() || '')
        });
    });

    function renderOptions(query) {
        var normalized = String(query || '').toLowerCase();
        var selected = String(\$select.val() || '');
        var filtered = options.filter(function (option) {
            return normalized === ''
                || option.label.toLowerCase().indexOf(normalized) !== -1
                || option.value.toLowerCase().indexOf(normalized) !== -1;
        });

        \$select.empty();

        if (!filtered.length) {
            \$select.append($('<option></option>').attr('value', '').text('No models found'));
            return;
        }

        filtered.forEach(function (option) {
            var \$option = $('<option></option>').attr('value', option.value).text(option.label);
            if (option.value === selected) {
                \$option.prop('selected', true);
            }
            \$select.append(\$option);
        });

        if (\$select.val() !== selected && filtered.length) {
            \$select.val(filtered[0].value);
        }
    }

    \$search.on('input', function () {
        renderOptions($(this).val());
    });

    \$select.on('change', function () {
        var selectedValue = String(\$select.val() || '');
        var current = options.find(function (option) {
            return option.value === selectedValue;
        });
        if (current) {
            \$search.val(current.label);
        }
    });
});
</script>
HTML;

        return $searchHtml . $element->getElementHtml() . $script;
    }
}
