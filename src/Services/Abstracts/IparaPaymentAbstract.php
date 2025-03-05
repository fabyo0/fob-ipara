<?php

namespace Botble\Ipara\Services\Abstracts;

use Botble\Payment\Models\Payment;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;

abstract class IparaPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected bool $supportRefundOnline;

    public function __construct()
    {
        $this->supportRefundOnline = false;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function getPaymentDetails($paymentId)
    {
        try {
            $response = Payment::query()->where('charge_id', '=', $paymentId)->first();
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    public function refundOrder($paymentId, $amount, array $options = []): array
    {
        try {
            return [
                'error' => true,
                'message' => 'iPara refund işlemi henüz desteklenmiyor.',
            ];

        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getRefundDetails($refundId): void
    {

    }

    public function execute(Request $request): bool
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}
