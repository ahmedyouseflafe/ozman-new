@php($catalogCheckoutConfig = ['url' => route('catalog.orders.store', $shop), 'csrf' => csrf_token(), 'csrfUrl' => route('csrf.refresh')])
<script>
window.OzmanCatalogCheckout = @json($catalogCheckoutConfig);
</script>
<script>{!! file_get_contents(public_path('catalog-checkout.js')) !!}</script>
