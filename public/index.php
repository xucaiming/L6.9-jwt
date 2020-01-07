<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$response->send();
$kernel->terminate($request, $response);
exit;

define('API_URL', 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/3.0/geschaeftskundenversand-api-3.0.wsdl');
define('DHL_SANDBOX_URL', 'https://cig.dhl.de/services/sandbox/soap');
define('DHL_PRODUCTION_URL', 'https://cig.dhl.de/services/production/soap');

class SoapClientDebug extends SoapClient
{
    public $sendRequest = true;
    public $printRequest = false;
    public $formatXML = true;

    public function __doRequest($request, $location, $action, $version, $one_way=0) {
        if ( $this->printRequest ) {
            if ( !$this->formatXML ) {
                $out = $request;
            }
            else {
                $doc = new DOMDocument;
                $doc->preserveWhiteSpace = false;
                $doc->loadxml($request);
                $doc->formatOutput = true;
                $out = $doc->savexml();
            }
            echo $out;
        }

        if ( $this->sendRequest ) {
            return parent::__doRequest($request, $location, $action, $version, $one_way);
        }
        else {
            return '';
        }
    }
}

class DHL
{
    private $credentials;
    public $from;
    public $to;
    public $xml;
    private $client;
    public $errors;
    protected $sandbox;
    public $product;


    function __construct($credentials, $sandbox = FALSE)
    {
        $this->sandbox = $sandbox;
        $this->credentials = $credentials;
        $this->errors = array();
    }

    private function buildClient()
    {
        $header = $this->buildAuthHeader();
        if ($this->sandbox) {
            $location = DHL_SANDBOX_URL;
        } else {
            $location = DHL_PRODUCTION_URL;
        }
//        echo $location; exit;
        $auth_params = array(
            'login' => $this->credentials['api_user'],
            'password' => $this->credentials['api_signature'],
            'location' => $location,
            'trace' => 1
        );

//        print_r($auth_params); exit;

        $this->client = new SoapClientDebug(API_URL, $auth_params);
        $this->client->__setSoapHeaders($header);
    }

    function createLabel()
    {
        $this->buildClient();
        $shipment = array();

        $shipmentDetails = [
            'product' => $this->product,
            'accountNumber' => $this->credentials['ekp'],
            'shipmentDate' => date('Y-m-d'),
            'ShipmentItem' => [
                "weightInKG" => 10,
                "lengthInCM" => 10,
                "widthInCM" => 10,
                "heightInCM" => 10
            ]
        ];
        $shipper = [
            'Name' => [
                'name1' => $this->from['name1']
            ],
            'Address' => [
                "streetName" => $this->from['streetName'],
                "streetNumber" => $this->from['streetNumber'],
                "addressAddition" => "0",
                "zip" => $this->from['zip'],
                "city" => $this->from['city'],
                "Origin" => array(
                    "countryISOCode"=>$this->from['country']
                )
            ]
        ];
        $receiver = [
            'name1' =>  $this->to['name1'],
            'Address' => [
                "streetName" => $this->to['streetName'],
                "streetNumber" => $this->to['streetNumber'],
                "addressAddition" => "0",
                "zip" => $this->to['zip'],
                "city" => $this->to['city'],
                "Origin" => array(
                    "countryISOCode"=>$this->to['country']
                )
            ]
        ];
        $shipment['Version'] = ['majorRelease' => '3', 'minorRelease' => '0'];
        $shipment['ShipmentOrder'] = [
            "sequenceNumber" => "01",
            "Shipment" => [
                "ShipmentDetails" => $shipmentDetails,
                "Shipper" => $shipper,
                "Receiver" => $receiver,
                "ReturnReceiver" => $shipper
            ],
            "PrintOnlyIfCodeable" => array("active"=>1)
        ];
        $shipment['labelResponseType'] = "URL";
        $shipment['groupProfileName'] = "Test_China";
        $response = $this->client->createShipmentOrder($shipment);
        return json_encode($response);
    }


    function getVersion() {
        $this->buildClient();
        $response = $this->client->getVersion(array('majorRelease' => '3', 'minorRelease' => '0'));
    }

    private function buildAuthHeader()
    {
        $auth_params = array(
            'user' => $this->credentials['user'],
            'signature' => $this->credentials['signature'],
        );

//        print_r($auth_params); exit;

        return new SoapHeader('http://dhl.de/webservice/cisbase', 'Authentification', $auth_params);
    }
}

$credentials = array(
    'api_user' => 'salcar_2',
    'api_signature' => 'UnuhWrQD95vFlejdMJmzs7EXI9Fiua',
    'user' => 'test-salcar',
    'signature' => 'T1234est!',
    'ekp' => '62219991355303',
    'log' => true
);
$test_credentials = array(
    'api_user' => 'salcar_2',
    'api_signature' => 'UnuhWrQD95vFlejdMJmzs7EXI9Fiua',
    'user' => '2222222222_01',
    'signature' => 'pass',
    'ekp' => '22222222225303',
    'log' => true
);
$from = array(
    'name1' => 'DHLGmbH',
    'name2' => 'GmbH',
    'streetName' => 'An der Hebemärchte',
    'streetNumber' => '6',
    'zip' => '04316',
    'city' => 'Leipzig',
    'country' => 'DE',
);
//DHL Paket Product and Address
$product = "V01PAK";
$to = array(
    'name1' => 'DHLGmbH2',
    'name2' => 'GmbH2',
    'streetName' => 'Charles de Gaulle Str',
    'streetNumber' => '20',
    'zip' => '53113',
    'city' => 'Bonn',
    'country' => 'DE',
);
//DHL Paket International Product and Address
$product = "V53WPAK";
$to = array(
    'name1' => 'TestName',
    'name2' => 'GmbH2',
    'streetName' => 'RUE DE L\'ENCLOS SAINT MAUR',
    'streetNumber' => '34',
    'zip' => '59700',
    'city' => 'Marcq-en-Barul',
    'country' => 'FR',
    'countryISOCode' => 'FR',
);

$sandbox = false;
if($sandbox){
    $credentials = $test_credentials;
}
$dhl = new DHL($credentials,$sandbox);
$dhl->from = $from;
$dhl->to = $to;
$dhl->product = $product;
$response = $dhl->createLabel();
echo $response;
