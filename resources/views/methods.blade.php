@if (get_payment_setting('status', IPARA_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :name="IPARA_PAYMENT_METHOD_NAME"
        paymentName="iPara"
    />
@endif
