<?php

namespace MyCompany\AiBlogGenerator\Block\Adminhtml\Post;

class Edit extends \Mageplaza\Blog\Block\Adminhtml\Post\Edit
{
    protected function _construct()
    {
        parent::_construct();

        if ($this->getRequest()->getParam('history')) {
            return;
        }

        $this->buttonList->add(
            'mycompany_ai_generate',
            [
                'label' => __('Generate with AI'),
                'class' => 'secondary',
                'id' => 'mycompany-ai-generate-button',
                'onclick' => 'window.MyCompanyAiBlogGeneratorOpen && window.MyCompanyAiBlogGeneratorOpen();',
            ],
            -90
        );
    }
}
