# :rabbit: Dolly

Dolly nace de la necesidad de tener un package rápido para integrar el **servicio SOAP** de WebPay en cualquier comercio que se encuentre construido con Laravel. **Este package no es oficial de TransBank** y de momento solo es soportado la transancción normal.

Compatible desde la **versión 5.4 de Laravel**. (No he probado versiones antigua de L5.x)


## Instalación

Instalar via composer 

```bash
composer require devswert/dolly
```

Agregar nuestro ServiceProvider

```php
// config/app.php
'providers' => [
    ...
    Devswert\Dolly\DollyServiceProvider::class,
];
```

Luego, publicar la configuración y migración necesaria para que Dolly funcione:

```bash
php artisan vendor:publish --provider="Devswert\Dolly\DollyServiceProvider"
```

Se recomienda ejecutar `php artisan migrate` para que se instale la tabla necesaria por Dolly.

En el archivo de configuración (config/dolly.php) pueden indicar las rutas a las llaves necesarias para realizar las pruebas a WebPay como también el código de comercio. **Las llaves deben ser archivos físicos**, y en los campos de configuración se debe indicar la ruta, como base Dolly considera la función *base_path()* y le añade un slash, por lo que si dejaran sus certificados en `storage/app/your_certificate.crt` deben poner la tuya tal cual en la variable de entorno.


## Uso

El flujo de WebPay es ir y venir entre el sitio, los pasos para poder integrar WebPay serían:

1. Crear dos rutas basadas en *POST*. **Dejar estas rutas omitidas en el middleware VerifyCsrfToken**. Una debe ser *result* y otra de *end*, eres libre de poner la ruta que quieras.
2. Crear una vista sencilla que usaremos para redireccionar a WebPay, abajo entenderan por que.
3. Al momento de procesar el carro de compras y si el método de pagos fue WebPay se debe instanciar los metodos de la siguiente manera:

```php
<?php

use Devswert\Dolly\WebPayServicies\WebPayNormal;

...
$total = 12990; // Total a pagar
$session_id = null; // Utilicen el valor que necesiten
$url_return = route('your-webpay-result-route');
$url_final = route('your-webpay-end-route');

$webpay = new WebPayNormal();
$result = $webpay->start($total, $purchase_code, $session_id, $url_return, $url_final);

if (empty($result->token) || !isset($result->token) && true){
	// La autenticacion en WebPay fallo, se recomienda retornar al checkout con un mensaje de error correspondiente
}

// En caso correcto, guardar todos los datos necesarios en session para generar la boleta u orden de compra
session()->put('oc_details', [
    'purchase_code' => $purchase_code,
    'total' => $total,
    // ...
]);

return view('webpay.redirect',[
    'token' => $result->token,
    'url' => $result->url
]);
```

Al final de este método se carga una vista con un formulario básico, lo que ha pasado hasta ahora es enviar un request a Transbank informando que haremos una compra, a ello, Transbank nos da un token que dura cierto tiempo y una URL a la cual debe redireccionar via POST. Es por ello que necesitamos un formulario como el siguiente:

```html
<form action="{{ $url }}" method="POST" id="webpay-redirect">
	<input type="hidden" name="token_ws" value="{{ $token }}">
</form>
<script>
	document.getElementById('webpay-redirect').submit();
</script>
```

4. Crear un controlador que recepcione ambos resultados que nos pueda dar WebPay. *(Recuerden que WebPay retorna y se comunica con el comercio mediante POST)* La función de resultado deberia quedar de la siguiente manera

```php
<?php

use Devswert\Dolly\WebPayServicies\WebPayNormal;

...

public function yourResultFunctionName(Request $request){
	$token = $request->get('token_ws');
	$webpay = new WebPayNormal();
	$result = $webpay->result($token);

	if ($result->passes()){
		// Almacenar la OC, pueden usar los datos de session
		
		session()->put('webpay_status', true); // Recomendable para la funcion de end, ya que el token en ese instante ya no existe para WebPay, entonces con esta variable sabemos que el proceso paso correcto

		// Como último pase se redirecciona (si, de nuevo) a WebPay para que genere el boucher de pago
		return view('webpay.redirect',[
            'token' => $token,
            'url' => $result->urlRedirection
        ]);
	}
	else{
		// Algo paso y el resultado no paso, pudo ser rechazo de la transacción o tarjetas sin saldo. Pueden obtener el error con $result->error_message();
		return redirect()->route('your-cart-route');
	}
}

public function youEndFunctionName(Request $request){
        if( session()->has('webpay_status') ){
            session()->forget('webpay_status');
            return view('your.cart.success.payment');
        }

        // Si no pasa, es muy probable que el usuario presiono el boton anular, enviar el mensaje correspondiente y redireccionar al Checkout (o donde sea en su caso)
        return redirect()->route('your.checkout.route');
    }
```


> Cada transacción sea exitosa o no almacena un log en la tabla **webpay_logs**.
> PRs son muy bienvenidos

**Happy Coding!**