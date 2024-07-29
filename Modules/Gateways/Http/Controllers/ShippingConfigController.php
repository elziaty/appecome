<?php

namespace Modules\Gateways\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Entities\Setting;
use Modules\Gateways\Traits\AddonActivationClass;
use Modules\Gateways\Traits\Processor;

class ShippingConfigController extends Controller
{
    use Processor, AddonActivationClass;

    private $config_values;
    private $merchant_key;
    private $config_mode;
    private Setting $shipping_setting;

    public function __construct(Setting $shipping_setting)
    {
        $this->shipping_setting = $shipping_setting;
    }

    public function shipping_config_get()
    {
        $data_values = $this->shipping_setting->whereIn('settings_type', ['shipping_config'])->get();
        if (base64_decode(env('SOFTWARE_ID')) == '40224772') {
            return view('Gateways::shipping-config.demandium-shipping-config', compact('data_values'));
        } else {
            return view('Gateways::shipping-config.shipping-config', compact('data_values'));
        }
    }

     /**
     * Display a listing of the resource.
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function shipping_config_set(Request $request): RedirectResponse
    {
        collect(['status'])->each(fn($item, $key) => $request[$item] = $request->has($item) ? (int)$request[$item] : 0);
        $validation = [
            'gateway' => 'required|in:bosta',
            'mode' => 'required|in:live,test'
        ];

        $additional_data = [];

        if ($request['gateway'] == 'bosta') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
            ];
        }

        $request->validate(array_merge($validation, $additional_data));

        $settings = $this->shipping_setting->where('key_name', $request['gateway'])->where('settings_type', 'shipping_config')->first();

        $additional_data_image = $settings['additional_data'] != null ? json_decode($settings['additional_data']) : null;

        if ($request->has('gateway_image')) {
            $gateway_image = $this->file_uploader('payment_modules/gateway_image/', 'png', $request['gateway_image'], $additional_data_image != null ? $additional_data_image->gateway_image : '');
        } else {
            $gateway_image = $additional_data_image != null ? $additional_data_image->gateway_image : '';
        }

        $shipping_additional_data = [
            'gateway_title' => $request['gateway_title'],
            'gateway_image' => $gateway_image,
        ];

        $validator = Validator::make($request->all(), array_merge($validation, $additional_data));

        $this->shipping_setting->updateOrCreate(['key_name' => $request['gateway'], 'settings_type' => 'shipping_config'], [
            'key_name' => $request['gateway'],
            'live_values' => $validator->validate(),
            'test_values' => $validator->validate(),
            'settings_type' => 'shipping_config',
            'mode' => $request['mode'],
            'is_active' => $request['status'],
            'additional_data' => json_encode($shipping_additional_data),
        ]);

        Toastr::success(GATEWAYS_DEFAULT_UPDATE_200['message']);
        return back();
    }
}
