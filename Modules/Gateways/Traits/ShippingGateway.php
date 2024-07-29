<?php

namespace Modules\Gateways\Traits;

use Modules\Gateways\Http\Controllers\BostaShippingController;

trait  ShippingGateway
{
	public static function sendShipment($gateway, $shipmentDetails)
    {
        switch ($gateway) {
            case 'bosta':
                return self::shipWithBosta($shipmentDetails);
                break;
            default:
                return 'Shipping Gateway not supported';
        }
    }

    private static function shipWithBosta($shipmentDetails)
    {
        $bostaShippingController = new BostaShippingController();
        return $bostaShippingController->ship($shipmentDetails);
    }

}