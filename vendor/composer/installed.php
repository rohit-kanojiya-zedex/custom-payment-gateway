<?php return array(
    'root' => array(
        'name' => 'uniquepaymentgatway/easy-stripe-gateway',
        'pretty_version' => '1.0.0',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'stripe/stripe-php' => array(
            'pretty_version' => 'v15.8.0',
            'version' => '15.8.0.0',
            'reference' => '5ed133fa45987771f80ad300be2316c05832f6a7',
            'type' => 'library',
            'install_path' => __DIR__ . '/../stripe/stripe-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'uniquepaymentgatway/easy-stripe-gateway' => array(
            'pretty_version' => '1.0.0',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
