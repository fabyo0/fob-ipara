<?php

namespace Botble\Ipara\Services\Gateways;

use Botble\Ipara\Models\Currency;
use Botble\Ipara\Services\Abstracts\IparaPaymentAbstract;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IparaPaymentService extends IparaPaymentAbstract
{

    public function makePayment(Request $request)
    {
        $data = [
            'error' => false,
            'message' => '',
            'amount' => 0,
            'currency' => '',
            'type' => IPARA_PAYMENT_METHOD_NAME,
            'charge_id' => null,
        ];

        try {
            $paymentData = $request->input('payment');

            $orderIds = $paymentData['order_id'];
            $amount = $paymentData['amount'];
            $currency = strtoupper($paymentData['currency']);
            $name = $paymentData['name'];
            $email = Arr::get($paymentData, 'email');
            $callbackUrl = $paymentData['callback_url'];


            $chargeId = Str::random(20);
            $data['charge_id'] = $chargeId;

            $publicKey = get_payment_setting('public_key', IPARA_PAYMENT_METHOD_NAME);
            $privateKey = get_payment_setting('private_key', IPARA_PAYMENT_METHOD_NAME);
            $mode = get_payment_setting('mode', IPARA_PAYMENT_METHOD_NAME) ? 'T' : 'P';


            Log::info('iPara storeLocalPayment METADATA', [
                'checkout_token' => Arr::get($paymentData, 'checkout_token', 'NONE'),
                'metadata' => ['checkout_token' => Arr::get($paymentData, 'checkout_token')]
            ]);

            $orderIds = (array)$orderIds;
            $this->storeLocalPayment([
                'amount' => $amount,
                'currency' => $currency,
                'charge_id' => $chargeId,
                'order_id' => $orderIds,
                'customer_id' => Arr::get($paymentData, 'customer_id'),
                'customer_type' => Arr::get($paymentData, 'customer_type'),
                'payment_channel' => IPARA_PAYMENT_METHOD_NAME,
                'status' => PaymentStatusEnum::PENDING,
            ]);

            return $data;
        } catch (Exception $exception) {
            Log::error('iPara makePayment error: ' . $exception->getMessage());

            $data['error'] = true;
            $data['message'] = $exception->getMessage();

            return $data;
        }
    }


    public function afterMakePayment(Request $request)
    {
        try {
            $chargeId = $request->input('chargeId');
            $status = $request->input('status');

            $payment = Payment::where('charge_id', $chargeId)->first();

            if (!$payment) {
                Log::error('iPara afterMakePayment: Payment not found with charge_id: ' . $chargeId);
                return false;
            }

            if ($status === 'success') {
                $payment->status = PaymentStatusEnum::COMPLETED;
            } else {
                $payment->status = PaymentStatusEnum::FAILED;
            }

            $payment->save();

            return true;
        } catch (Exception $exception) {
            Log::error('iPara afterMakePayment error: ' . $exception->getMessage());
            return false;
        }
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            Currency::TL,
            Currency::TRY
        ];
    }

    private function storeLocalPayment(array $data): void
    {
        try {
            $chargeId = $data['charge_id'];
            $orderIds = $data['order_id'];
            $metadata = Arr::get($data, 'metadata', []);

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'charge_id' => $chargeId,
                'order_id' => $orderIds,
                'customer_id' => Arr::get($data, 'customer_id'),
                'customer_type' => Arr::get($data, 'customer_type', null),
                'payment_channel' => IPARA_PAYMENT_METHOD_NAME,
                'status' => Arr::get($data, 'status', PaymentStatusEnum::PENDING),
                'metadata' => $metadata,
            ]);

            return;
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);
            return;
        }
    }
}
