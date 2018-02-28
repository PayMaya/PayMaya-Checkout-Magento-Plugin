<?php

require_once __DIR__ . '/lib/load.php';

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'PayMaya_Checkout',
    __DIR__
);
