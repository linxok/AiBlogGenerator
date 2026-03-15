<?php

namespace MyCompany\AiBlogGenerator\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class AbstractGenerate extends Action
{
    public const ADMIN_RESOURCE = 'MyCompany_AiBlogGenerator::generate';
}
