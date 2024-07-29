<?php

namespace Modules\Gateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Gateways\Traits\ShippingGateway;

class ShippingController extends Controller
{
    use ShippingGateway;

    public function shipOrder(Request $request)
    {
        $this->validate($request, [
            'shipping_gateway' => 'required|string',
            'shipment_details' => 'required|array',
        ]);

        $gateway = $request->input('shipping_gateway');
        $shipmentDetails = $request->input('shipment_details');

        $result = self::sendShipment($gateway, $shipmentDetails);

        return response()->json(['message' => $result]);
    }
}
