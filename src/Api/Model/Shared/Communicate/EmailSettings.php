<?php

namespace Api\Model\Shared\Communicate;

class EmailSettings
{
    public function __construct()
    {
    }

    /** @var string */
    public $fromAddress;

    /** @var string */
    public $fromName;
}
