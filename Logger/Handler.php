<?php

namespace MyCompany\AiBlogGenerator\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $loggerType = Logger::DEBUG;

    protected $fileName = '/var/log/ai_blog_generator.log';
}
