<?php

return [
	/*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Aqui definimos el entorno en el cual probaremos los servicios de 
    | Transbank, por defecto se maneja INTEGRACION para evitar cargos 
    |
    */
	'environment' => env('WEBPAY_ENV', 'integration'),

	/*
    |--------------------------------------------------------------------------
    | Commerce Code
    |--------------------------------------------------------------------------
    |
    | Este código es entregado a la empresa por parte de Transbank para 
    | poder integrar los servicios
    |
    */
	'commerce_code' => env('WEBPAY_COMMERCE_CODE'),


    /*
    |--------------------------------------------------------------------------
    | Keys and Certs
    |--------------------------------------------------------------------------
    |
    */
	'private_key' => env('WEBPAY_PRIVATE_KEY'),
	'public_cert' => env('WEBPAY_PUBLIC_CERT'),
	'webpay_cert' => env('WEBPAY_CERT'),

	'store_codes' => env('WEBPAY_STORE_CODES'),

    /*
    |--------------------------------------------------------------------------
    | WSDL URLs
    |--------------------------------------------------------------------------
    |
    | Endpoints ofrecidos por Transbank para poder integrar los servicios
    |
    */
    'wsdl_urls' => [
        'integration' => 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl',
        'certification' => 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl',
        'production' => 'https://webpay3g.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl',
    ]
];