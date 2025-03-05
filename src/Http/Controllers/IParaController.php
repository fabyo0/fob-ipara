<?php

namespace Botble\Ipara\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Models\Order;
use Botble\Hotel\Models\Booking;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper as PaymentHelperSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IparaController extends BaseController
{
    public function webhook(Request $request)
    {
        Log::info('iPara Webhook', $request->all());

        $publicKey = get_payment_setting('public_key', IPARA_PAYMENT_METHOD_NAME);
        $privateKey = get_payment_setting('private_key', IPARA_PAYMENT_METHOD_NAME);

        try {
            $data = $request->all();

            if (isset($data['orderId']) && isset($data['result'])) {
                $orderId = $data['orderId'];
                $result = $data['result'];

                if ($result === 'success' || $result === '1') {
                    $payment = Payment::where('charge_id', $orderId)->first();

                    if ($payment) {
                        $payment->status = PaymentStatusEnum::COMPLETED;
                        $payment->save();

                        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, $payment);

                        return response()->json(['message' => 'Payment confirmed successfully']);
                    }
                } else {
                    $payment = Payment::query()->where('charge_id', $orderId)->first();

                    if ($payment) {
                        $payment->status = PaymentStatusEnum::FAILED;
                        $payment->save();

                        return response()->json(['message' => 'Payment failed']);
                    }
                }
            }

            return response('OK', 200);
        } catch (Exception $exception) {
            Log::error('iPara Webhook Error: ' . $exception->getMessage());

            return response()->json([
                'error' => true,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info('iPara Callback Received', [
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'all_params' => $request->all(),
            'headers' => $request->header(),
            'path' => $request->path(),
            'body' => $request->getContent(),
        ]);

        $orderId = $request->input('orderId') ?? $request->input('ORDER_ID') ?? '';
        $result = $request->input('result') ?? $request->input('RESULT') ?? '';
        $checkoutToken = $request->input('checkout_token');

        $echo = $request->input('ECHO');
        $hash = $request->input('HASH');
        $errorCode = $request->input('ERROR_CODE');
        $errorMessage = $request->input('ERROR_MESSAGE');

        Log::info('iPara Callback Parameters', [
            'orderId' => $orderId,
            'result' => $result,
            'echo' => $echo,
            'hash' => $hash,
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
            'checkoutToken' => $checkoutToken,
        ]);

        if (empty($orderId)) {
            Log::error('iPara Callback: OrderId not found');

            return redirect(PaymentHelperSupport::getCancelURL() ?: route('public.index'))
                ->with('error_msg', __('Payment failed: Order ID not found!'));
        }

        $isSuccess = ($result === 'success' || $result === '1' || $result == 1);

        Log::info('iPara Success Status', ['isSuccess' => $isSuccess, 'result' => $result]);

        if ($isSuccess) {

            $payment = Payment::query()->where('charge_id', $request->input('charge_id'))->first();

            if ($payment) {
                Log::info('iPara Payment Found', [
                    'id' => $payment->id,
                    'current_status' => $payment->status,
                    'metadata' => $payment->metadata,
                ]);

                $payment->status = PaymentStatusEnum::COMPLETED;
                $payment->save();

             //   do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, $payment);

                Log::info('iPara Payment Successfully Updated', ['new_status' => $payment->status]);

                if (empty($checkoutToken) && $payment->metadata && isset($payment->metadata['checkout_token'])) {
                    $checkoutToken = $payment->metadata['checkout_token'];
                }

                if ($checkoutToken) {
                    Log::info('iPara Redirecting with checkout token', ['token' => $checkoutToken]);

                    return redirect(url("checkout/{$checkoutToken}/success"))
                        ->with('success_msg', __('Checkout successfully!'));
                }

                return redirect(PaymentHelperSupport::getRedirectURL() ?: route('public.index'))
                    ->with('success_msg', __('Checkout successfully!'));
            } else {
                Log::error('iPara Payment Not Found for OrderId: ' . $orderId);
            }
        }

        if ($checkoutToken) {
            return redirect(url("checkout/{$checkoutToken}/error"))
                ->with('error_msg', __('Payment failed!'));
        }

        return redirect(PaymentHelperSupport::getCancelURL() ?: route('public.index'))
            ->with('error_msg', __('Payment failed!'));
    }


    public function process(Request $request)
    {
        Log::info('-----------Process starting-----------');

        Log::info($request->all());

        try {
            $publicKey = get_payment_setting('public_key', IPARA_PAYMENT_METHOD_NAME);
            $privateKey = get_payment_setting('private_key', IPARA_PAYMENT_METHOD_NAME);
            $mode = get_payment_setting('mode', IPARA_PAYMENT_METHOD_NAME) ? 'T' : 'P';

            $orderId = $request->input('order_id');
            $amount = $request->input('amount');
            $successUrl = $request->input('success_url');
            $failUrl = $request->input('fail_url');
            $chargeId = $request->input('charge_id');

            $cardOwnerName = $request->input('cardOwnerName');
            $cardNumber = str_replace(' ', '', $request->input('cardNumber'));
            $cardExpireMonth = $request->input('cardExpireMonth');
            $cardExpireYear = $request->input('cardExpireYear');
            $cardCvc = $request->input('cardCvc');
            $installment = $request->input('installment', 1);

            $payment = Payment::query()->where('charge_id', $chargeId)->first();
            if (! $payment) {
                throw new Exception('Payment record not found');
            }

            $order = null;
            $customer = null;

            if (is_plugin_active('ecommerce')) {
                $order = Order::with(['user', 'address', 'shippingAddress'])->find($payment->order_id);
                if ($order) {
                    $customer = $order->user;
                }
            } elseif (is_plugin_active('hotel')) {
                $booking = Booking::with(['customer', 'address'])->find($payment->order_id);
                if ($booking) {
                    $customer = $booking->customer;
                }
            }

            $customerName = $customer->name ?? 'Customer';
            if (str_contains($customerName, ' ')) {
                $nameParts = explode(' ', $customerName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
            } else {
                $firstName = $customerName;
                $lastName = 'Customer';
            }

            $address = $order->address ?? null;
            $shippingAddress = $order->shippingAddress ?? $address;

            $customerEmail = $address ? $address->email : ($customer->email ?? 'customer@example.com');
            $customerPhone = $address ? $address->phone : ($customer->phone ?? '5551112233');

            $products = [];
            if ($order && isset($order->products) && $order->products->count() > 0) {
                foreach ($order->products as $item) {
                    $products[] = [
                        'productCode' => 'PRD' . ($item->product_id ?? rand(1000, 9999)),
                        'productName' => $item->product_name ?? $item->name ?? 'Product',
                        'quantity' => $item->qty,
                        'price' => intval($item->price * 100),
                    ];
                }
            } else {
                $products[] = [
                    'productCode' => 'PRD' . rand(1000, 9999),
                    'productName' => 'Order #' . $payment->order_id,
                    'quantity' => 1,
                    'price' => intval($amount),
                ];
            }

            $transactionDate = date('Y-m-d H:i:s');

            $hashString = $privateKey . $orderId . $amount . $mode . $cardOwnerName . $cardNumber .
                $cardExpireMonth . $cardExpireYear . $cardCvc . '' . '' .
                $firstName . $lastName . $customerEmail . $transactionDate;

            $token = $publicKey . ':' . base64_encode(sha1($hashString, true));

            $checkoutToken = '';
            if ($payment->metadata && isset($payment->metadata['checkout_token'])) {
                $checkoutToken = $payment->metadata['checkout_token'];
            }

            $paymentRequest = [
                'mode' => $mode,
                'orderId' => $orderId,
                'cardOwnerName' => $cardOwnerName,
                'cardNumber' => $cardNumber,
                'cardExpireMonth' => $cardExpireMonth,
                'cardExpireYear' => $cardExpireYear,
                'cardCvc' => $cardCvc,
                'userId' => '',
                'cardId' => '',
                'checkout_token' => $checkoutToken,
                'installment' => $installment,
                'amount' => $amount,
                'echo' => 'Echo',
                'successUrl' => $successUrl . ($checkoutToken ? "&checkout_token={$checkoutToken}" : ''),
                'failureUrl' => $failUrl . ($checkoutToken ? "&checkout_token={$checkoutToken}" : ''),
                'transactionDate' => $transactionDate,
                'version' => '1.0',
                'token' => $token,
                'language' => 'tr-TR',
                'purchaser' => [
                    'name' => $firstName,
                    'surname' => $lastName,
                    'email' => $customerEmail,
                    'clientIp' => request()->ip(),
                    'birthDate' => $order->user->dob ?? null,
                    'gsmNumber' => $customerPhone,
                    'tcCertificate' => '11111111111',
                    'city' => $address ? ($address->state . ' - ' . $address->city) : 'İstanbul',
                    'address' => $address ? ($address->state . ' / ' . $address->city . ' - ' . $address->address) : 'Adres bilgisi',
                    'invoiceAddress' => [
                        'name' => $address ? $address->name : $firstName,
                        'surname' => $address ? (strpos($address->name, ' ') ? substr($address->name, strpos($address->name, ' ') + 1) : $lastName) : $lastName,
                        'address' => $address ? ($address->state . ' / ' . $address->city . ' - ' . $address->address) : 'Adres bilgisi',
                        'city' => $address ? ($address->state . ' - ' . $address->city) : 'İstanbul',
                        'country' => $address ? $address->country : 'TR',
                        'tcCertificate' => '11111111111',
                        'taxNumber' => '123456789',
                        'taxOffice' => 'Default',
                        'companyName' => '',
                        'phoneNumber' => $address ? $address->phone : $customerPhone,
                    ],
                    'shippingAddress' => [
                        'name' => $shippingAddress ? $shippingAddress->name : $firstName,
                        'surname' => $shippingAddress ? (strpos($shippingAddress->name, ' ') ? substr($shippingAddress->name, strpos($shippingAddress->name, ' ') + 1) : $lastName) : $lastName,
                        'address' => $address ? ($address->state . ' / ' . $address->city . ' - ' . $address->address) : 'Adres bilgisi',
                        'city' => $address ? ($address->state . ' - ' . $address->city) : 'İstanbul',
                        'country' => $shippingAddress ? $shippingAddress->country : 'TR',
                        'phoneNumber' => $shippingAddress ? $shippingAddress->phone : $customerPhone,
                    ],
                ],
                'products' => $products,
            ];

            $parameters = json_encode($paymentRequest);

            $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
            $html .= '<html>';
            $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head>';
            $html .= '<body>';
            $html .= '<form action="https://api.ipara.com/rest/payment/threed" method="post" id="three_d_form" >';
            $html .= '<input type="hidden" name="parameters" value="' . htmlspecialchars($parameters) . '"/>';
            $html .= '<input type="submit" value="Öde" style="display:none;"/>';
            $html .= '<noscript>';
            $html .= '<br/>';
            $html .= '<br/>';
            $html .= '<center>';
            $html .= '<h1>3D Secure Yönlendirme İşlemi</h1>';
            $html .= '<h2>Javascript internet tarayıcınızda kapatılmış veya desteklenmiyor.<br/></h2>';
            $html .= '<h3>Lütfen banka 3D Secure sayfasına yönlenmek için tıklayınız.</h3>';
            $html .= '<input type="submit" value="3D Secure Sayfasına Yönlen">';
            $html .= '</center>';
            $html .= '</noscript>';
            $html .= '</form>';
            $html .= '</body>';
            $html .= '<script>document.getElementById("three_d_form").submit();</script>';
            $html .= '</html>';

            echo $html;
            exit;

        } catch (Exception $exception) {
            Log::error('iPara Process Error: ' . $exception->getMessage());

            return redirect()->back()->withErrors(['error' => $exception->getMessage()]);
        }
    }
}
