@if (isset($shipping_gateways_list) && !$shipping_gateways_list->isEmpty())
    <select class="form-control text-capitalize" name="shipping_gateway" id="choose_shipping_gateway">
        <option value="0">
            {{translate('choose_shipping_gateway')}}
        </option>
        @foreach($shipping_gateways_list as $shipping_gateway)
            <option
                value="{{$shipping_gateway->key_name}}" {{$order->delivery_service_name==$shipping_gateway->key_name?'selected':''}}>
                {{json_decode($shipping_gateway->additional_data)->gateway_title}}
            </option>
        @endforeach
    </select>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('choose_shipping_gateway').addEventListener('change', function() {
        var gateway = this.value;
        if (gateway !== '0') {
            document.getElementsByName('delivery_service_name')[0].value = gateway;
            // Fetch shipment details, assuming this is available globally or through some other means
            var shipmentDetails = @json($order); // Adjust as necessary to get actual shipment details

            fetch('{{ route('shipping.ship.order') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    shipping_gateway: gateway,
                    shipment_details: shipmentDetails
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message.status) {
                    alert(data.message.message);
                    console.log('Shipment processed:', data.message);
                    // You can also save the tracking number here
                    document.getElementsByName('third_party_delivery_tracking_id')[0].value = data.message.trackingNumber;
                } else {
                    alert(data.message.message);
                    console.error('Error processing shipment');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});
</script>