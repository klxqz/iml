<?php

return array(
    'name' => 'Курьерская доставка IML',
    'description' => '',
    'vendor' => 985310,
    'version' => '1.1.1',
    'img' => 'img/iml.png',
    'shop_settings' => true,
    'frontend' => false,
    'handlers' => array(
        'backend_orders' => 'backendOrders',
        'backend_order' => 'backendOrder',
        'backend_menu' => 'backendMenu',
    ),
);
//EOF
