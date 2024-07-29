<?php

namespace Modules\Gateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Gateways\Traits\Processor;

class BostaShippingController extends Controller
{
    use Processor;

    private mixed $config_values;
    private string $config_mode;

    public function __construct()
    {
        $config = $this->payment_config('bosta', 'shipping_config');
        
        if (!is_null($config)) {
            $this->config_values = json_decode($config->mode == 'test' ? $config->test_values : $config->live_values);
            $this->config_mode = $config->mode == 'test' ? 'test' : 'live';
        }
    }

    /**
     * Ship the shipment with Bosta
     *
     * @param Request $request
     * @return mixed
     */
    public function ship($order)
    {
        // dd($order['order_details']);
        $curl_url   = 'https://app.bosta.co/api/v2/deliveries';    
        
        $authorizeArr = [
            'Content-Type: application/json',
            'Authorization: '.$this->config_values->api_key,
        ];

        //get package details iteams / descreption
        $itemsCount = 0;
        $description = "";

        foreach ($order['order_details'] as $item) {
            $product_details = json_decode($item["product_details"], true);
            $product_name = $product_details["name"];
            
            $itemsCount += $item["qty"];
            
            $description .= $product_name . " (" . $item["qty"] . ") + ";
        }
        $description = rtrim($description, " + ");

        $request_body = json_encode([
            'type' => 10,
            'cod' => ($order['payment_method'] == 'cash_on_delivery')?$order['order_amount']:0, // cash on delivery
            'specs' => [
                'packageType' => 'Parcel',
                'packageDetails' => [
                    'itemsCount' => $itemsCount,
                    'description' => $description
                ]
            ],
            'allowToOpenPackage' => true,
            'dropOffAddress' => [
                'firstLine' => $order['shipping_address_data']['address'],
                'city' => $order['shipping_address_data']['city'],
                // 'districtId' => from api,
                'geoLocation' => [$order['shipping_address_data']['latitude'], $order['shipping_address_data']['longitude']]
            ],
            'receiver' => [
                'firstName' => $order['shipping_address_data']['contact_person_name'],
                'phone' => $order['shipping_address_data']['phone'],
            ]
        ]);
// print_r($request_body);die();
        // Curl call to the api
        $cSession = curl_init(); 
        curl_setopt($cSession,CURLOPT_URL,$curl_url);
        curl_setopt($cSession,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($cSession,CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($cSession,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cSession,CURLOPT_HTTPHEADER, $authorizeArr);
        curl_setopt($cSession,CURLOPT_ENCODING, "");
        curl_setopt($cSession,CURLOPT_MAXREDIRS, 10);
        curl_setopt($cSession,CURLOPT_TIMEOUT, 30);
        $result=curl_exec($cSession);
        
        if (curl_errno($cSession)) {
            echo 'Error: '. curl_error($cSession);
        }
        curl_close($cSession);

        //handle curl result
        
        $response_body = json_decode($result,TRUE);
        if($response_body['success']){
            $trackingNumber = $response_body["data"]["trackingNumber"];
            $message = $response_body["data"]["message"];
            $response = [
                'status' => true,
                'message' => $message,
                'trackingNumber' => $trackingNumber
            ];
        }else{
            $response = [
                'status' => false,
                'message' => 'Failed to place the order.'
            ];
        }

        return $response;
    }

}
