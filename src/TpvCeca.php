<?php

namespace Azulae\Ceca;
use Exception;

class TpvCeca
{
    /**  
     * @var integer $_merchantID Requerido 9 Identifica al comercio. Facilitado por la caja en el proceso de alta
     */
    private $_merchantID;
    
    /**
     * @var integer $_acquirerBIN Requerido 10 Identifica la caja. Facilitado por la caja en el proceso de alta.
     */
    private $_acquirerBIN;

    /**
     * @var integer $_terminalID Requerido 8 Identifica al terminal. Facilitado por la caja en el proceso de alta.
     */
    private $_terminalID;

    /**
     * @var string $_num_operacion Requerido 50
     */
    private $_num_operacion;

    /**  
     * @var float $_importe Requerido 12 Importe de la operación sin formatear. Siempre será un número entero donde los dos últimos dígitos serán los céntimos de Euro.
     */
    private $_importe;

    /**
     * @var string $_tipoMoneda Requerido 3
     */
    private $_tipoMoneda;

    /**
     * @var integer $_tipoMoneda Requerido 1 Actualmente siempre será 2
     */
    private $_exponente;

    /**
     * @var string $_url_ok Requerido 500 URL completa. 
     */
    private $_url_ok;

    /**
     * 
     * @var string $_url_nok Requerido 500 URL completa. 
     */
    private $_url_nok;

    /**
     * @var string $_firma Requerido 256 Es una cadena de caracteres calculada por el comercio.
     */
    private $_firma;

    /**
     * @var string $_cadena Es la cadena para firmar.
     */
    private $_cadena;
    
    /**
     * @var string $_cifrado Requerido 4 Valor fijo SHA1.
     */
    private $_cifrado;

    /**
     * @var integer $_idioma Opcional 1 Código de idioma.
     * 
     * 1.- Español 2.- Catalán 3.- Euskera 4.- Gallego 5.- Valenciano
     * 6.- Inglés 7.- Francés 8.- Alemán 9.- Portugués 10.- Italiano 
     * 11.- Sueco 12.- Danés 13.- Ruso 14.- Holandés 15.- Noruego
     */
    private $_idioma;

    /**
     * @var string $_pago_soportado Requerido 3 Valor fijo SSL.
     */
    private $_pago_soportado;

    /**
     * @var string $_descripcion Opcional 1000 Campo reservado para mostrar información extra en la página de pago.
     */
    private $_descripcion;
    /**
     * 
     * @var string $_pago_elegido Opcional Dependiendo de quien solicite los datos de la tarjeta. Si los solicita el comercio será SSL. Si los solicita el TPV será vacío o no viajará.
     */
    private $_pago_elegido;
    
    /**
     * @var integer $_pan Opcional 19 Nº de tarjeta del cliente. Este campo tendrá contenido sólo en el caso de que la caja haya autorizado al comercio a solicitar este  tipo de datos. En caso contrario dejarlo sin contenido.
     */
    private $_pan;

    /**
     * @var string $_caducidad Opcional 6 Fecha de Caducidad. Formato AAAAMM. Este campo tendrá  contenido sólo en el caso de que la caja haya autorizado al comercio a solicitar este tipo de datos. En caso contrario dejarlo sin contenido.
     */
    private $_caducidad;

    /**
     * @var integer $_cvv2 Opcional CVC2 de la tarjeta. Este campo tendrá contenido sólo en el caso  de que la caja haya autorizado al comercio a solicitar este tipo de datos. En caso contrario dejarlo sin contenido.
     */
    private $_cvv2;

    /**
     * @var string Referencia Opcional 30 Si el comercio está realizando el pago de una compra el campo  viajará sin contenido. Si el comercio está realizando la anulación de una operación, se informará con el valor correspondiente.
     */
    private $_referencia;
    
    /**
     * Pasarela final a la que se conectara
     * @var string $_urlPasarela
     */
    private $_urlPasarela;

    /**
     * URL para conectarse a la TPV en produccion
     * @var string
     */
    private $_urlPasarelaproduccion;

    /**
     * URL para conectarse a la TPV en desarrollo
     * @var string
     */
    private $_urlPasareladesarrollo;

    /**
     * URL para conectarse a la TPV en produccion
     * @var string
     */
    private $_urlAnulacionPasarelaproduccion;

    /**
     * URL para conectarse a la TPV en desarrollo
     * @var string
     */
    private $_urlAnulacionPasareladesarrollo;

    /**
     * Clave de encriptacion
     * @var string $_clave_encriptacion Cualquier valor
     */
    private $_clave_encriptacion;

    private $_modo;

    private $_nameForm;
    private $_idForm;
    private $_submit;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_exponente = 2;
        $this->_cifrado = $this->config('cifrado', 'SHA2');
        $this->_idioma = 1; //Por defecto español
        $this->_pago_soportado = 'SSL';
        $this->_urlPasarelaproduccion = $this->config('url_pasarela_produccion', 'https://pgw.ceca.es/tpvweb/tpv/compra.action');
        $this->_urlPasareladesarrollo = $this->config('url_pasarela_desarrollo', 'https://tpv.ceca.es/tpvweb/tpv/compra.action');
        $this->_urlAnulacionPasarelaproduccion = 'https://comercios.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS?modo=anularOperacionExt';
        $this->_urlAnulacionPasareladesarrollo = 'https://democonsolatpvvirtual.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS?modo=anularOperacionExt';
        $this->_urlPasarela = $this->_urlPasareladesarrollo;
        $this->_nameForm = 'ceca_form';
        $this->_idForm = 'ceca_form';
        $this->_submit = '';
        $this->_clave_encriptacion = '';
        $this->_tipoMoneda = "978";
        $this->_terminalID = '00000003';
        $this->_modo = '';
    }

    /**
     * Leer un valor de la configuración del paquete (config/ceca.php) si
     * Laravel está disponible, manteniendo la clase usable de forma aislada.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function config($key, $default = null)
    {
        if (function_exists('config')) {
            $value = config('ceca.' . $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Asignar código comercio 
     * @param integer $merchantid Código identificativo del comercio (Proporcionado por el Comercio).
     */ 
    public function setMerchantID($merchantid='')
    {
        if(strlen(trim($merchantid)) > 0)
        {
            $this->_merchantID = $merchantid;
        }
        else
        {
            throw new \Exception('Falta agregar MerchantID proporcionada por el comercio, Obligatorio');
        }
        
    }

    /**
     * Asignar código entidad
     * @param string $acquirerbin Código identificativo de su Caja (Proporcionado por el Comercio).
     */
    public function setAcquirerBIN($acquirerbin='')
    {
        if(strlen(trim($acquirerbin)) > 0)
        {
            $this->_acquirerBIN = $acquirerbin;
        }
        else
        {
            throw new \Exception('Falta agregar AcquirerBIN proporcionada por el comercio, Obligatorio');
        }
        
    }

    /**
     * Asignar url_ok
     * @param string $urlok Es la URL determinada por el comercio a la que Cecabank devolverá el control en el caso de que la  operación finalice correctamente.
     *                      Esta URL no deberá utilizarse  para actualizar la operación como pagada en el servidor del  comercio.
     */
    public function setUrlOk($urlok='')
    {
        if(strlen(trim($urlok)) > 0)
        {
            $this->_url_ok = $urlok;
        }
        else
        {
            throw new \Exception('Falta agregar Url Ok de respuesta tras la compra, Obligatorio');
        }
        
    }

    /**
     * Asignar url_nok
     * @param string $urlnok Es la URL determinada por el comercio a  la que Cecabank devolverá el control en el caso de que la  operación no pueda realizarse por algún motivo.
     */
    public function setUrlNok($urlnok='')
    {
        if(strlen(trim($urlnok)) > 0)
        {
            $this->_url_nok = $urlnok;
        }
        else
        {
            throw new \Exception('Falta agregar Url NOk de respuesta cuando se produce un error, Obligatorio');
        }
        
    }

    /**
     * Asignar número de pedido
     * @param string $numoperacion Identifica para el comercio la operación, nº de pedido, factura,  albaran, etc.… Puede ser alfanumérico pero están prohibidos los caracteres extraños típicos como ¿,?,%,&,*,etc.
     */
    public function setNumOperacion($numoperacion='')
    {
        if(strlen(trim($numoperacion)) > 0)
        {
            $this->_num_operacion = $numoperacion;
        }
        else
        {
            throw new \Exception('Falta agregar Numero de operacion (Num. de factura, pedido, etc), Obligatorio');
        }
        
    }

    /**
     * Asignar referencia
     * @param string $referencia Es el único valor devuelto por la Pasarela SET/SEP. Este dato es imprescindible para realizar cualquier tipo de reclamación y/o anulación de la compra.
     */
    public function setReferencia($referencia='')
    {
        if(strlen(trim($referencia)) > 0)
        {
            $this->_referencia = $referencia;
        }
        else
        {
            throw new \Exception('Falta agregar Referencia, Obligatorio en anulaciones');
        }
        
    }

    /**
     * Asignar modo
     * @param string $modo Para compras debe ir vacio, en anulaciones se enviará el metodo anularOperacionExt.
     */
    public function setModo($modo='')
    {
        if(strlen(trim($modo)) > 0)
        {
            $this->_modo = $modo;
        }
        else
        {
            throw new \Exception('Falta agregar Modo, Obligatorio en anulaciones');
        }
        
    }

    /**
     * Asignar importe a pagar
     * @param string $importe Importe a pagar, se puede especificar con comas y puntos (Ejm. 10,67 / 10.32)
     */
    public function setImporte($importe='')
    {
        if(strlen(trim($importe)) > 0)
        {
            $importe = $this->priceToSQL($importe);
        
            // Siempre será un número entero donde los dos últimos dígitos serán los céntimos de Euro.
            // Se usa aritmética de cadenas para evitar errores de redondeo en punto flotante.
            $this->_importe = $this->priceToCents($importe);
        }
        else
        {
            throw new \Exception('Falta agregar Importe, Obligatorio');
        }
        
    }

    /**
     * Convertir un importe decimal en cadena ("10.67") a entero de céntimos (1067)
     * sin pasar por punto flotante, evitando errores tipo intval(10.67*100) == 1066.
     *
     * @param string $price
     * @return integer Importe en céntimos.
     */
    private function priceToCents($price)
    {
        $price = (string) $price;

        if (strpos($price, '.') !== false) {
            list($euros, $centimos) = explode('.', $price);

            return (int) ($euros . str_pad($centimos, 2, '0', STR_PAD_RIGHT));
        }

        return (int) $price * 100;
    }

    /**
     * Asignar el tipo de moneda
     * @param integer $tipomoneda Es el código ISO-4217 correspondiente a la moneda en la que se  efectúa el pago. Contendrá el valor 978 para Euros.
     */
    public function setTipoMoneda($tipomoneda)
    {
        $this->_tipoMoneda = $tipomoneda;
    }

    /**
     * Asignar el ID del terminal
     * @param string $terminalid Código identificativo de un terminal dentro de un comercio. Por defecto es 00000003
     */
    public function setTerminalID($terminalid='')
    {
        $this->_terminalID = $terminalid;
    }

    /**
     * Asignar URL de producción
     * @param string $urlpasarelaproduccion Url del entorno de producción
     */
    public function setUrlpasarelaproduccion($urlpasarelaproduccion='')
    {
        $this->_urlPasarelaproduccion = $urlpasarelaproduccion;
    }

    /**
     * Asignar URL de desarrollo
     * @param string $urlpasareladesarrollo Url del entorno de desarrollo
     */
    public function setUrlpasareladesarrollo($urlpasareladesarrollo='')
    {
        $this->_urlPasareladesarrollo = $urlpasareladesarrollo;
    }

    /**
     * Asignar entorno
     * @param string $entorno Asignar el tipo de entorno que usaremos para comunicarnos con la TPV (por defecto modo desarrollo)
     */
    public function setEntorno($entorno='desarrollo')
    {
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion")
        {
            if(strtolower(trim($entorno)) == 'produccion'){
                //produccion
                $this->_urlPasarela=$this->_urlAnulacionPasarelaproduccion;
            }
            elseif(strtolower(trim($entorno)) == 'desarrollo'){
                //desarrollo
                $this->_urlPasarela = $this->_urlAnulacionPasareladesarrollo;
            } 
        }
        else
        {
            if(strtolower(trim($entorno)) == 'produccion'){
                //produccion
                $this->_urlPasarela=$this->_urlPasarelaproduccion;
            }
            elseif(strtolower(trim($entorno)) == 'desarrollo'){
                //desarrollo
                $this->_urlPasarela = $this->_urlPasareladesarrollo;
            } 
        }
    }

    /**
     * Clave de encriptación
     * @param string $claveencriptacion Utilizada para firmar las llamadas realizadas al TPV. Las claves son distintas en pruebas y en real. (Proporcionado por el Comercio).
     */
    public function setClaveEncriptacion($claveencriptacion='')
    {
        if(strlen(trim($claveencriptacion)) > 0)
        {
            $this->_clave_encriptacion = $claveencriptacion;
        }
        else
        {
            throw new \Exception('Falta agregar la clave de encriptacion proporcionada por el comercio, Obligatorio');
        }
    }

    /**
     * Asignar el sistema de cifrado de la firma
     * @param string $cifrado 'SHA2' (por defecto, compatibilidad) o 'HMAC' (nuevo sistema obligatorio de Cecabank, fecha límite 31/12/2026).
     *                        Con HMAC la clave de encriptación debe ser la nueva clave de 32 caracteres (Base64, 24 bytes).
     */
    public function setCifrado($cifrado = 'SHA2')
    {
        $this->_cifrado = $cifrado;
    }

    /**
     * Generar la firma
     * @return string Este método construye la firma con los parámetros anteriormente asignados
     */
    private function firma(){                
        $this->_cadena =  $this->_clave_encriptacion . $this->_merchantID . $this->_acquirerBIN . $this->_terminalID . $this->_num_operacion . $this->_importe . $this->_tipoMoneda . $this->_exponente . $this->_cifrado . $this->_url_ok . $this->_url_nok;
        if(strlen(trim($this->_clave_encriptacion)) > 0){
            $this->_firma = $this->makeSignature($this->_cadena);
        }
        else{
            throw new Exception('Falta agregar la clave de encriptacion, Obligatorio');
        }
    }
    /**
     * Generar la firma para anulaciones
     * @return string Este método construye la firma con los parámetros anteriormente asignados
     */
    private function firmaAnulacion(){
        $this->_cadena = $this->_clave_encriptacion . $this->_merchantID . $this->_acquirerBIN . $this->_terminalID . $this->_num_operacion . $this->_importe . $this->_tipoMoneda . $this->_exponente . $this->_referencia . $this->_cifrado;
        if(strlen(trim($this->_clave_encriptacion)) > 0){
            if (strtoupper(trim($this->_cifrado)) === 'SHA1') {
                // Cálculo del SHA1
                $this->_firma = strtolower(hash('sha1', $this->_cadena));
            } else {
                $this->_firma = $this->makeSignature($this->_cadena);
            }
        }
        else{
            throw new Exception('Falta agregar la firma de anulacion, Obligatorio');
        }
    }

    /**
     * Calcular la firma según el sistema de cifrado configurado.
     *
     * @param string $cadena Cadena a firmar (incluye la clave de encriptación como prefijo).
     * @return string Firma en minúsculas-hex (SHA2) o Base64 (HMAC).
     */
    private function makeSignature($cadena, $numOperacion = null)
    {
        if (strtoupper(trim($this->_cifrado)) === 'HMAC') {
            return $this->makeHmacSignature($cadena, $numOperacion);
        }

        // Cálculo del SHA256
        return strtolower(hash('sha256', $cadena));
    }

    /**
     * Firma HMAC-SHA256: nuevo sistema de firma de Cecabank
     * (Boletín de seguridad, cambio obligatorio antes del 31/12/2026).
     *
     * La clave HMAC se obtiene cifrando el número de operación con 3DES-CBC
     * (relleno de ceros a múltiplo de 8 bytes e IV de ceros) usando la clave
     * del comercio decodificada de Base64 (24 bytes), igual que el plugin
     * oficial de Cecabank.
     *
     * @param string $cadena
     * @return string Firma en Base64
     */
    private function makeHmacSignature($cadena, $numOperacion = null)
    {
        if ($numOperacion === null) {
            $numOperacion = $this->_num_operacion;
        }

        $hmacKey = $this->deriveHmacSha256Key($this->_clave_encriptacion, $numOperacion);

        return base64_encode(hash_hmac('sha256', $cadena, $hmacKey, true));
    }

    /**
     * Derivar la clave HMAC-SHA256 a partir de la clave del comercio y el número de operación.
     *
     * @param string $claveComercio Clave Base64 de 24 bytes (32 caracteres) del Portal de Administración.
     * @param string $numOperacion
     * @return string Clave binaria para HMAC.
     */
    private function deriveHmacSha256Key($claveComercio, $numOperacion)
    {
        if (empty($numOperacion)) {
            throw new Exception('Num_operacion es obligatorio para calcular la firma HMAC');
        }

        // La clave Base64 del comercio es la clave 3DES de 24 bytes.
        $key3des = base64_decode($claveComercio, true);

        if ($key3des === false || strlen($key3des) !== 24) {
            throw new Exception('Para la firma HMAC la clave de encriptacion debe ser la nueva clave Base64 de 32 caracteres (24 bytes) del Portal de Administracion de Cecabank');
        }

        // 3DES-CBC del número de operación con relleno manual de ceros a
        // múltiplo de 8 bytes e IV de ceros. La salida es la clave HMAC.
        $blockSize = 8;
        $remainder = strlen($numOperacion) % $blockSize;
        $padded = $numOperacion;
        if ($remainder !== 0) {
            $padded .= str_repeat("\0", $blockSize - $remainder);
        }

        $iv = str_repeat("\0", 8);

        $encrypted = openssl_encrypt(
            $padded,
            'des-ede3-cbc',
            $key3des,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception('No se ha podido derivar la clave HMAC con 3DES');
        }

        return $encrypted;
    }

    /**
     * Comprobar la firma recibida en la notificación de Cecabank
     * (compatible con SHA2 y HMAC según el cifrado configurado).
     *
     * La cadena de la notificación NO incluye el campo Cifrado:
     * clave + MerchantID + AcquirerBIN + TerminalID + Num_operacion + Importe + TipoMoneda + Exponente + Referencia
     *
     * @param array $post Campos recibidos en la notificación ($_POST o $request->all()).
     * @return bool true si la firma recibida es válida.
     */
    public function checkNotificationSignature(array $post)
    {
        $campos = array('Firma', 'MerchantID', 'AcquirerBIN', 'TerminalID', 'Num_operacion', 'Importe', 'TipoMoneda', 'Exponente', 'Referencia');

        foreach ($campos as $campo) {
            if (!isset($post[$campo]) || ($campo === 'Firma' && $post[$campo] === '')) {
                return false;
            }
        }

        if (empty($this->_clave_encriptacion)) {
            return false;
        }

        $cadena = $this->_clave_encriptacion
            . $post['MerchantID']
            . $post['AcquirerBIN']
            . $post['TerminalID']
            . $post['Num_operacion']
            . $post['Importe']
            . $post['TipoMoneda']
            . $post['Exponente']
            . $post['Referencia'];

        $firma = $this->makeSignature($cadena, $post['Num_operacion']);

        return hash_equals($firma, $post['Firma']);
    }


    /**
     * Asignar el nombre del formulario
     * @param string nombre Nombre del formulario
     */

    public function setNameform($nombre = 'form_tpv')
    {
        $this->_nameForm = $nombre;
    }

    /**
     * Asignar el id del formulario
     * @param string idform ID del formulario
     */

    public function setIdform($idform = 'id_tpv')
    {
        $this->_idForm = $idform;
    }

    /**
    * Generar boton submit
    * @param string nombre Nombre y ID del botón submit
    * @param string texto Texto que se mostrara en el botón
    */

    public function setSubmit($nombre = 'submitceca',$texto='Enviar')
    {
        if(strlen(trim($nombre))==0)
            throw new Exception('Asigne nombre al boton submit');

        $btnsubmit = '<input type="submit" name="'.$nombre.'" id="'.$nombre.'" value="'.$texto.'" />';
        $this->_submit = $btnsubmit;
    }

    /**
     * Iniciar redirección automática
     * @return string Se ejecuta la redicción automática por javascript
     */
    public function launchRedirection() {
            echo $this->create_form();
            //exit;
            echo '<script>document.forms["'.$this->_nameForm.'"].submit();</script>';
    }

    /**
     * Creacion del formulario
     * @return string Retorna el formulario para la TPV
     */
    public function create_form(){

        // Si es anulacion, se calcula la firma de otro modo
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion"){
            $this->firmaAnulacion();
        }else{
            $this->firma();
        }

        $formulario='
        <form action="'.$this->escape($this->_urlPasarela).'" method="post" id="'.$this->escape($this->_idForm).'" name="'.$this->escape($this->_nameForm).'" enctype="application/x-www-form-urlencoded" >
            <input type="hidden" name="MerchantID" value="'.$this->escape($this->_merchantID).'" />
            <input type="hidden" name="AcquirerBIN" value="'.$this->escape($this->_acquirerBIN).'" />
            <input type="hidden" name="TerminalID" value="'.$this->escape($this->_terminalID).'" />
            <input type="hidden" name="URL_OK" value="'.$this->escape($this->_url_ok).'" />
            <input type="hidden" name="URL_NOK" value="'.$this->escape($this->_url_nok).'" />
            <input type="hidden" name="Firma" value="'.$this->escape($this->_firma).'" />
            <input type="hidden" name="Cadena" value="'.$this->escape($this->_cadena).'" />
            <input type="hidden" name="Cifrado" value="'.$this->escape($this->_cifrado).'" />
            <input type="hidden" name="Num_operacion" value="'.$this->escape($this->_num_operacion).'" />
            <input type="hidden" name="Importe" value="'.$this->escape($this->_importe).'" />
            <input type="hidden" name="TipoMoneda" value="'.$this->escape($this->_tipoMoneda).'" />
            <input type="hidden" name="Exponente" value="'.$this->escape($this->_exponente).'" />
            <input type="hidden" name="Pago_soportado" value="'.$this->escape($this->_pago_soportado).'" />
            <input type="hidden" name="Idioma" value="'.$this->escape($this->_idioma).'" />
        ';


        // Si es anulacion, se incluye la referencia
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion")
        {
            $formulario.='
                <input type="hidden" name="Referencia" value="'.$this->escape($this->_referencia).'" />
                <input type="hidden" name="modo" value="anularOperacionExt" />


            ';
                // https://democonsolatpvvirtual.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS?modo=anularOperacionExt&MerchantID=086401247&AcquirerBIN=0000554027&TerminalID=00000003&Num_operacion=100000003752&Importe=2500&TipoMoneda=978&Exponente=2&Referencia=12005238811803201324076007000&Firma=443d38f0d02de5eb8b59fd748a7afcb96d3abbe278727feb8099d01e2a0f7880&Cifrado=SHA2&Idioma=1
        }

            $formulario.=$this->_submit;
            $formulario.='
        </form>        
        ';

        /*$formulario.="<br><span>Esperado:7PE9C1B60864012470000554027000000031000000037522500978212005238811803201324076007000SHA2"."</span>";
        $formulario.="<br><span>Calculado:".$this->_cadena."</span>";

        $formulario.="<br><span>FirmaEsperada:443d38f0d02de5eb8b59fd748a7afcb96d3abbe278727feb8099d01e2a0f7880"."</span>";
        $formulario.="<br><span>FirmaCalculada:".$this->_firma."</span>";
        $formulario.="<br><span>FirmaCalculada2:".hash('sha256', $this->_cadena)."</span>";*/

        return $formulario;
    }


    /**
     * Creacion del formulario
     * @return string Retorna el formulario para la TPV
     */
    public function getParametersArray(){

        // Si es anulacion, se calcula la firma de otro modo
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion"){
            $this->firmaAnulacion();
        }else{
            $this->firma();
        }


        $parametrosArray =  [
                                "UrlPasarela" => $this->_urlPasarela,
                                "MerchantID" => $this->_merchantID,
                                "AcquirerBIN" => $this->_acquirerBIN,
                                "TerminalID" => $this->_terminalID,
                                "URL_OK" => $this->_url_ok,
                                "URL_NOK" => $this->_url_nok,
                                "Firma" => $this->_firma,
                                "Cadena" => $this->_cadena,
                                "Cifrado" => $this->_cifrado,
                                "Num_operacion" => $this->_num_operacion,
                                "Importe" => $this->_importe,
                                "TipoMoneda" => $this->_tipoMoneda,
                                "Exponente" => $this->_exponente,
                                "Pago_soportado" => $this->_pago_soportado,
                                "Idioma" => $this->_idioma,
                            ];


        // Si es anulacion, se incluye la referencia
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion")
        {
            $parametrosArray["Referencia"] = $this->_referencia;
            $parametrosArray["modo"] = "anularOperacionExt";
        }

        return $parametrosArray;
    }


    /**
     * Creacion de la url para la devolucion
     * @return string Retorna el formulario para la TPV
     */
    public function getCancellationUrl(){

        // Si es anulacion, se calcula la firma de otro modo
        if(strlen(trim($this->_modo)) > 0 && trim($this->_modo) == "Anulacion"){
            $this->firmaAnulacion();
        }else{
            $this->firma();
        }

        $cancellationUrl =  $this->_urlPasarela."&MerchantID=".$this->_merchantID.
                                                "&AcquirerBIN=".$this->_acquirerBIN.
                                                "&TerminalID=".$this->_terminalID.
                                                "&Num_operacion=".$this->_num_operacion.
                                                "&Importe=".$this->_importe.
                                                "&TipoMoneda=".$this->_tipoMoneda.
                                                "&Exponente=".$this->_exponente.
                                                "&Referencia=".$this->_referencia.
                                                "&Firma=".$this->_firma.
                                                "&Cifrado=".$this->_cifrado.
                                                "&Idioma=".$this->_idioma;

        return $cancellationUrl;
    }

    //Utilidades
    //http://stackoverflow.com/a/9111049/444225
    private function priceToSQL($price)
    {
        $price = preg_replace('/[^0-9\.,]*/i', '', $price);
        $price = str_replace(',', '.', $price);

        // El último punto actúa como separador decimal (1 o 2 decimales);
        // el resto se consideran separadores de miles y se eliminan.
        if (substr($price, -3, 1) == '.' || substr($price, -2, 1) == '.')
        {
            $price = explode('.', $price);
            $last = array_pop($price);
            $price = join('',$price).'.'.$last;
        }
        else
        {
            $price = str_replace('.', '', $price);
        }

        return $price;
    }

    /**
     * Escapar un valor para su uso seguro en atributos HTML del formulario.
     *
     * @param mixed $value
     * @return string
     */
    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

}
