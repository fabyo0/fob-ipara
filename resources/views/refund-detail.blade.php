@if ($refund)
    @php
        $refundTransactionId = Arr::get($refund, 'transactionId');
    @endphp
    <div class="alert alert-warning" role="alert">
        <div class="d-flex justify-content-between">
            <p>{{ trans('plugins/payment::payment.refunds.id') }}: <strong>{{ $refundTransactionId }}</strong></p>
            @if ($refundTransactionId)
                <a class="get-refund-detail d-block"
                   data-element="#{{ $refundTransactionId }}"
                   data-url="{{ route('payment.refund-detail', [$paymentModel->id, $refundTransactionId]) }}">
                    <i class="fas fa-sync-alt"></i>
                </a>
            @endif
        </div>
        <p>{{ trans('plugins/payment::payment.amount') }}: {{ Arr::get($refund, 'amount') / 100 }}
            {{ Arr::get($refund, 'currency') }}</p>
        <p>{{ trans('plugins/payment::payment.refunds.status') }}: {{ Arr::get($refund, 'status') }}</p>

        @if (Arr::has($refund, 'createdDate'))
            <p>{{ trans('core/base::tables.created_at') }}:
                {{ Carbon\Carbon::now()->parse(Arr::get($refund, 'createdDate')) }}</p>
        @endif

        @if ($errorMessage = Arr::get($refund, 'errorMessage'))
            <p class="text-danger">{{ trans('plugins/payment::payment.refunds.error_message') }}: {{ $errorMessage }}</p>
        @endif
    </div>
    <br />
@endif
