<?php

namespace Modules\Gateways\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RouteMatchedHandler
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $routesToInject = ['admin.orders.details'];

        if (in_array($event->route->getName(), $routesToInject)) {
            $event->route->middleware('shipping_gateways');
        }
    }
}
