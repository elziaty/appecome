<?php

namespace Modules\Gateways\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class InjectShippingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if the request URI matches specific patterns
        if ($response instanceof \Illuminate\Http\Response && 
            strpos($response->headers->get('Content-Type'), 'text/html') !== false && 
            $this->shouldInject($request)) {

            // Add options to the new select element from the shipping gateways
            $selectHtml = view('Gateways::shipping.options', [
                    'shipping_gateways_list' => $this->shipping_gateways(),
                    'order' => $response->original->getData()['order'],
                ])->render();
            
            if($selectHtml){
                $content = $response->getContent();
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();

                $xpath = new \DOMXPath($dom);

                // Locate the div where you want to inject the new select and label
                $targetDiv = $xpath->query('//div[@id="third_party_delivery_service_modal"]//div[@class="card-body"]')->item(0);
                if ($targetDiv) {
                    // Create a new label element
                    $newLabel = $dom->createElement('label', 'Shipping Gateway');
                    $newLabel->setAttribute('for', 'shipping_gateway');

                    $tempDom = new \DOMDocument();
                    @$tempDom->loadHTML('<?xml encoding="utf-8" ?>' . $selectHtml);
                    $shippingGatewaySelect = $dom->importNode($tempDom->documentElement, true);

                    // Create a new div to wrap the label and select
                    $newDiv = $dom->createElement('div');
                    $newDiv->setAttribute('class', 'form-group');

                    // Append the label and select to the new div
                    $newDiv->appendChild($newLabel);
                    $newDiv->appendChild($shippingGatewaySelect);

                    // Inject the new div into the target div
                    $targetDiv->insertBefore($newDiv, $targetDiv->firstChild);
                }

            $content = $dom->saveHTML();
            $response->setContent($content);
            }
        }

        return $response;
    }

    /**
     * Determine if the injection should be performed based on the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function shouldInject($request)
    {
        // Define your condition here
        // Example: return true for specific routes
        return in_array($request->route()->getName(), ['admin.orders.details']);
    }

    public function shipping_gateways()
    {
        return $shipping_gateways_list = Setting::whereIn('settings_type', ['shipping_config'])->where('is_active', 1)->get();
    }
}
