<?php

namespace Devswert\Dolly\WebPayServicies;

use Devswert\Dolly\Exceptions\WebPayConnectionException;
use Devswert\Dolly\SOAP\SoapValidation;
use Devswert\Dolly\SOAP\WSSecuritySoapClient;
use Devswert\Dolly\WebPayResultSummary;

/*
|--------------------------------------------------------------------------
| WebPay Normal
|--------------------------------------------------------------------------
|
| Una transacción de autorización normal (o transacción normal), corresponde 
| a una solicitud de autorización financiera de un pago con tarjetas de 
| crédito o débito, en donde quién realiza el pago ingresa al sitio del 
| comercio, selecciona productos o servicio, y el ingreso asociado a los 
| datos de la tarjeta de crédito o débito lo realiza en forma segura 
| en Webpay.
|
| Respuestas WebPay: 
| TSY: Autenticación exitosa
| TSN: autenticación fallida.
| TO : Tiempo máximo excedido para autenticación.
| ABO: Autenticación abortada por tarjetahabiente.
| U3 : Error interno en la autenticación.
| Puede ser vacío si la transacción no se autentico.
|
*/

class WebPayNormal{

	private $soap_client;
    private $classmap = [
    	'getTransactionResult' => \Devswert\Dolly\SOAP\Responses\Common\getTransactionResult::class,
    	'getTransactionResultResponse' => \Devswert\Dolly\SOAP\Responses\Common\getTransactionResultResponse::class,
    	'transactionResultOutput' => \Devswert\Dolly\SOAP\Responses\Normal\transactionResultOutput::class,
    	'cardDetail' => \Devswert\Dolly\SOAP\Responses\Normal\cardDetail::class,
    	'wsTransactionDetailOutput' => \Devswert\Dolly\SOAP\Responses\Normal\wsTransactionDetailOutput::class,
    	'wsTransactionDetail' => \Devswert\Dolly\SOAP\Responses\Normal\wsTransactionDetail::class,
    	'acknowledgeTransaction' => \Devswert\Dolly\SOAP\Responses\Normal\acknowledgeTransaction::class,
    	'acknowledgeTransactionResponse' => \Devswert\Dolly\SOAP\Responses\Normal\acknowledgeTransactionResponse::class,
    	'initTransaction' => \Devswert\Dolly\SOAP\Responses\Normal\initTransaction::class,
    	'wsInitTransactionInput' => \Devswert\Dolly\SOAP\Responses\Normal\wsInitTransactionInput::class,
    	'wpmDetailInput' => \Devswert\Dolly\SOAP\Responses\Normal\wpmDetailInput::class,
    	'initTransactionResponse' => \Devswert\Dolly\SOAP\Responses\Normal\initTransactionResponse::class,
    	'wsInitTransactionOutput' => \Devswert\Dolly\SOAP\Responses\Normal\wsInitTransactionOutput::class
    ];

    public function __construct(){
    	$private_key = config('dolly.private_key');
    	$public_cert = config('dolly.public_cert');
    	$endpoint = config('dolly.wsdl_urls.'. config('dolly.environment') );
        
    	$this->soap_client = new WSSecuritySoapClient($endpoint, $private_key, $public_cert, [
    	    "classmap" => $this->classmap,
    	    "trace" => true,
    	    "exceptions" => true
    	]);
    }

    public function initTransaction($amount, $buy_order, $session_id , $url_return, $url_final){
        try{
            $inputs = new \Devswert\Dolly\SOAP\Responses\Normal\wsInitTransactionInput();
            $inputs->wSTransactionType = "TR_NORMAL_WS";
            $inputs->sessionId = $session_id;
            $inputs->buyOrder = $buy_order;
            $inputs->returnURL = $url_return;
            $inputs->finalURL = $url_final;

            $details = new \Devswert\Dolly\SOAP\Responses\Normal\wsTransactionDetail();
            $details->commerceCode = config('dolly.commerce_code');
            $details->buyOrder = $buy_order;
            $details->amount = $amount;
            $inputs->transactionDetails = $details;

            $response = $this->soap_client->initTransaction(['wsInitTransactionInput' => $inputs]);

            /** Validación de firma del requerimiento de respuesta enviado por Webpay */
            $xmlResponse = $this->soap_client->__getLastResponse();
            $soapValidation = new SoapValidation($xmlResponse, config('dolly.webpay_cert'));
            $validationResult = $soapValidation->getValidationResult();
        }
        catch(\Exception $e){
            $replaceArray = array('<!--' => '', '-->' => '');
            $message = "Error conectando a Webpay: ".str_replace(array_keys($replaceArray), array_values($replaceArray), $e->getMessage()).' en '.$e->getFile().' línea '.$e->getLine();
            throw new WebPayConnectionException($message, 1);
        }

        /** Valida conexion a Webpay. Caso correcto retorna URL y Token */
        if ($validationResult === TRUE)
            return $response->return;
        else
            throw new WebPayValidationException("Error validando conexión a Webpay (Verificar que la información del certificado sea correcta)", 1);
    }

    public function getTransactionResult($token){
        try{
            $getTransactionResult = new \Devswert\Dolly\SOAP\Responses\Common\getTransactionResult();
            $getTransactionResult->tokenInput = $token;
            $getTransactionResultResponse = $this->soap_client->getTransactionResult($getTransactionResult);

            /** Validación de firma del requerimiento de respuesta enviado por Webpay */
            $xmlResponse = $this->soap_client->__getLastResponse();
            $soapValidation = new SoapValidation($xmlResponse, config('dolly.webpay_cert'));
            $validationResult = $soapValidation->getValidationResult();
        }
        catch(\Exception $e){
            $replaceArray = array('<!--' => '', '-->' => '');
            $message = "Error conectando a Webpay: ".str_replace(array_keys($replaceArray), array_values($replaceArray), $e->getMessage()).' en '.$e->getFile().' línea '.$e->getLine();
            throw new WebPayConnectionException($message, 1);
        }

        if ($validationResult === TRUE){
            $transaction_result = $getTransactionResultResponse->return;

            // Informar a WebPay recepción de la transaccion y 
            // retornamos un Response para trabajar
            if ($this->acknowledgeTransaction($token))
                return new WebPayResultSummary($transaction_result);
            else
                throw new WebPayValidationException("Error validando conexión a Webpay (Verificar que la información del certificado sea correcta)", 1);
        }
    }

    public function acknowledgeTransaction($token) {
        $acknowledgeTransaction = new \Devswert\Dolly\SOAP\Responses\Normal\acknowledgeTransaction();
        $acknowledgeTransaction->tokenInput = $token;
        $this->soap_client->acknowledgeTransaction($acknowledgeTransaction);
        
        $xmlResponse = $this->soap_client->__getLastResponse();
        $soapValidation = new SoapValidation($xmlResponse, config('dolly.webpay_cert'));
        $validationResult = $soapValidation->getValidationResult();
        return $validationResult === TRUE;
    }
}
