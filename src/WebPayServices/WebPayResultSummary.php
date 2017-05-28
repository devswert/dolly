<?php

namespace Devswert\Dolly;

use Devswert\Dolly\Model\WebPayLog;

class WebPayResultSummary{
	protected $buy_order;
	protected $card_number;
	protected $card_expiration_date;
	protected $authorization_code;
	protected $payment_type;
	protected $payment_type_code;
	protected $code;
	protected $shares_number = 0;
	protected $amount;
	protected $result_description;
	protected $result_code;
	protected $commerce_code;
	protected $VCI;
	protected $VCI_name;
	protected $status = false;
	public $urlRedirection;

	private $results_codes = [
	    '0' => 'Transacción aprobada',
	    '-1' => 'Rechazo de transacción',
	    '-2' => 'Transacción debe reintentarse',
	    '-3' => 'Error en transacción',
	    '-4' => 'Rechazo de transacción',
	    '-5' => 'Rechazo por error de tasa',
	    '-6' => 'Excede cupo máximo mensual',
	    '-7' => 'Excede límite diario por transacción',
	    '-8' => 'Rubro no autorizado',
	];
	private $payment_name = [
	    'VD' => 'Venta Debito',
		'VN' => 'Venta Normal',
		'VC' => 'Venta en cuotas',
		'SI' => '3 cuotas sin interés',
		'S2' => '2 cuotas sin interés',
		'NC' => 'N Cuotas sin interés'
	];
	private $VCI_status = [
		'TSY' => 'Autenticación exitosa',
		'TSN' => 'Autenticación fallida.',
		'TO' => 'Tiempo máximo excedido para autenticación.',
		'ABO' => 'Autenticación abortada por tarjetahabiente.',
		'U3' => 'Error interno en la autenticación.'
	];

	public function __construct($transaction_result){
		$this->urlRedirection = $transaction_result->urlRedirection;
		$this->buy_order = $transaction_result->buyOrder;
		$this->card_number = $transaction_result->cardDetail->cardNumber;
		$this->card_expiration_date = $transaction_result->cardDetail->cardExpirationDate;
		$this->authorization_code = $transaction_result->detailOutput->authorizationCode;
		$this->payment_type = $this->payment_name[$transaction_result->detailOutput->paymentTypeCode];
		$this->payment_type_code = $transaction_result->detailOutput->paymentTypeCode;
		$this->result_code = $transaction_result->detailOutput->responseCode;
		$this->result_description = $this->results_codes[$this->result_code];
		$this->shares_number = $transaction_result->detailOutput->sharesNumber;
		$this->amount = $transaction_result->detailOutput->amount;
		$this->commerce_code = $transaction_result->detailOutput->commerceCode;
		$this->VCI = $transaction_result->VCI;
		$this->VCI_name = ($transaction_result->VCI != '') ? $this->VCI_status[$this->VCI] : 'No Autenticado';
		$this->status = ( ($transaction_result->VCI == "TSY" || $transaction_result->VCI == "") && $this->result_code == 0);
		$this->log($transaction_result);
	}

	public function error_message(){
		return $this->result_description;
	}

	public function passes(){
		return $this->status;
	}

	public function fails(){
		return !$this->status;
	}

	public function log($transaction_result){
		$log = new WebPayLog();
		$log->accounting_date = $transaction_result->accountingDate;
		$log->buy_order = $this->buy_order;
		$log->card_number = $this->card_number;
		$log->card_expiration_date = $this->card_expiration_date;
		$log->authorization_code = $this->authorization_code;
		$log->payment_type = $this->payment_type;
		$log->payment_type_code = $this->payment_type_code;
		$log->result_code = $this->result_code;
		$log->result_description = $this->result_description;
		$log->shares_number = $this->shares_number;
		$log->amount = $this->amount;
		$log->commerce_code = $this->commerce_code;
		$log->session_id = $transaction_result->sessionId;
		$log->transaction_date = $transaction_result->transactionDate;
		$log->VCI = $this->VCI;
		$log->VCI_name = $this->VCI_name;
		$log->status = $this->status;
		$log->save();
	}
}