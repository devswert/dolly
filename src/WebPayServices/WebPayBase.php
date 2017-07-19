<?php

namespace Devswert\Dolly\WebPayServices;

use Devswert\Dolly\Exceptions\InvalidConfigException;
use Devswert\Dolly\SOAP\WSSecuritySoapClient;

abstract class WebPayBase{
    public function __construct(Array $classmap){
        $this->validateConfig();
    	$private_key = file_get_contents(base_path().'/'.config('dolly.private_key'));
    	$public_cert = file_get_contents(base_path().'/'.config('dolly.public_cert'));
    	$endpoint = config('dolly.wsdl_urls.'. config('dolly.environment') );
        
    	return new WSSecuritySoapClient($endpoint, $private_key, $public_cert, [
    	    "classmap" => $classmap,
    	    "trace" => true,
    	    "exceptions" => true
    	]);
    }

    private function validateConfig(){
        if( is_null(config('dolly')) )
            throw new InvalidConfigException('Dolly no tiene su configuración publicada, por favor configura bien el package', 1);

        if( is_null(config('dolly.commerce_code')) )
            throw new InvalidConfigException('Debes indicar un código de comercio válido para WebPay', 1);

        if( !in_array(config('dolly.environment'), ['integration','certification','production']) )
            throw new InvalidConfigException('Los entornos de WebPay solo pueden ser integration, certification o production', 1);

        if( !file_exists(base_path().'/'.config('dolly.private_key')) )
            throw new InvalidConfigException('La llave privada de WebPay no existe', 1);

        if( !file_exists(base_path().'/'.config('dolly.public_cert')) )
            throw new InvalidConfigException('La llave pública de WebPay no existe', 1);

        if( !file_exists(base_path().'/'.config('dolly.webpay_cert')) )
            throw new InvalidConfigException('El certificado de WebPay no existe', 1);
    }

    protected function webpayCert(){
        return file_get_contents(base_path().'/'.config('dolly.webpay_cert'));
    }
}