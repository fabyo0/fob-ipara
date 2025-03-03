<?php

namespace Botble\Ipara\Providers;

use Botble\Base\Facades\Html;
use Botble\Ecommerce\Models\Currency as CurrencyEcommerce;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\Currency as CurrencyHotel;
use Botble\JobBoard\Models\Currency as CurrencyJobBoard;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Supports\PaymentHelper;
use Botble\RealEstate\Models\Currency as CurrencyRealEstate;
use Botble\Ipara\Forms\IparaPaymentMethodForm;
use Botble\Ipara\Services\Gateways\IparaPaymentService;
use Botble\Ipara\IparaSdk\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Log;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data) {
            PaymentMethods::method(IPARA_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/ipara::methods', $data)->render(),
            ]);

            return $html;
        }, 12, 2);

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request) {

            if (!isset($data['type']) || $data['type'] != IPARA_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $currentCurrency = get_application_currency();
            $supportedCurrencies = $this->app->make(IparaPaymentService::class)->supportedCurrencyCodes();

            $checkoutToken = $request->input('checkout_token') ?? session('checkout_token') ?? '';


            if (in_array(strtoupper($currentCurrency->title), $supportedCurrencies)) {
                $paymentData['currency'] = strtoupper($currentCurrency->title);
            } else {
                $currency = match (true) {
                    is_plugin_active('ecommerce') => CurrencyEcommerce::class,
                    is_plugin_active('job-board') => CurrencyJobBoard::class,
                    is_plugin_active('real-estate') => CurrencyRealEstate::class,
                    is_plugin_active('hotel') => CurrencyHotel::class,
                    default => null,
                };

                $supportedCurrency = $currency::query()->whereIn('title', $supportedCurrencies)->first();

                if ($supportedCurrency) {
                    $paymentData['currency'] = strtoupper($supportedCurrency->title);
                    if ($currentCurrency->is_default) {
                        $paymentData['amount'] = $paymentData['amount'] * $supportedCurrency->exchange_rate;
                    } else {
                        $paymentData['amount'] = format_price(
                            $paymentData['amount'] / $currentCurrency->exchange_rate,
                            $currentCurrency,
                            true
                        );
                    }
                } else {
                    $paymentData['currency'] = null;
                }
            }

            if (!in_array($paymentData['currency'], $supportedCurrencies)) {
                $data['error'] = true;
                $data['message'] = __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", ['name' => 'iPara', 'currency' => $data['currency'], 'currencies' => implode(', ', $supportedCurrencies)]);

                return $data;
            }

            if (empty($paymentData['address']['email'])) {
                return [
                    ...$data,
                    'error' => true,
                    'message' => __('Please enter your email address.'),
                ];
            }

            try {
                $publicKey = get_payment_setting('public_key', IPARA_PAYMENT_METHOD_NAME);
                $privateKey = get_payment_setting('private_key', IPARA_PAYMENT_METHOD_NAME);
                $mode = get_payment_setting('mode', IPARA_PAYMENT_METHOD_NAME) ? 'T' : 'P';


                $amount = intval($paymentData['amount'] * 100);
                $orderId = sprintf(
                    'IPARA%s000OR%sCUSID%s',
                    Str::upper(Str::random(6)),
                    $paymentData['order_id'][0],
                    $paymentData['customer_id'] ?? 0,
                );

                $paymentService = new IparaPaymentService();

                $paymentRequestData = [
                    'payment' => [
                        'amount' => $paymentData['amount'],
                        'currency' => $paymentData['currency'],
                        'order_id' => [$paymentData['order_id'][0]],
                        'customer_id' => $paymentData['customer_id'] ?? 0,
                        'customer_type' => $paymentData['customer_type'] ?? null,
                        'name' => $paymentData['address']['name'] ?? 'Customer',
                        'email' => $paymentData['address']['email'] ?? '',
                        'address' => $paymentData['address']['address'],
                        'checkout_token' => $checkoutToken,
                        'city' => $paymentData['address']['city'],
                        'callback_url' => route('payments.ipara.callback'),
                        'metadata' => json_encode([
                            'order_id' => $paymentData['order_id'],
                            'customer_id' => $paymentData['customer_id'],
                            'customer_type' => $paymentData['customer_type'],
                        ]),
                    ],
                ];

                $result = $paymentService->makePayment(new Request($paymentRequestData));

                if ($result['error']) {
                    $data['error'] = true;
                    $data['message'] = $result['message'];
                    return $data;
                }

                $userIp = $request->ip();
                $name = $paymentData['address']['name'] ?? 'Customer';
                $surname = $paymentData['address']['name'] ?? 'Customer';
                $email = $paymentData['address']['email'] ?? '';
                $phone = $paymentData['address']['phone'] ?? '5551112233';
                $address = $paymentData['address']['address'] ?? 'Adres bilgisi';

                $products = [];
                foreach ($paymentData['products'] as $product) {
                    $products[] = [
                        'productCode' => 'URN' . ($product['id'] ?? '0'),
                        'productName' => $product['name'],
                        'quantity' => $product['qty'],
                        'price' => intval($product['price'] * 100)
                    ];
                }

                $successUrl = route('payments.ipara.callback', [
                    'result' => 'success',
                    'orderId' => $orderId,
                    'ORDER_ID' => $orderId,
                    'RESULT' => '1',
                    'checkout_token' => $checkoutToken
                ]);

                $failUrl = route('payments.ipara.callback', [
                    'result' => 'fail',
                    'orderId' => $orderId,
                    'ORDER_ID' => $orderId,
                    'RESULT' => '0',
                    'checkout_token' => $checkoutToken
                ]);


                Log::info('iPara Payment URLs', [
                    'successUrl' => $successUrl,
                    'failUrl' => $failUrl,
                    'checkout_token' => $request->input('checkout_token')
                ]);

                echo view('plugins/ipara::ipara', [
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                    'mode' => $mode,
                    'orderId' => $orderId,
                    'amount' => $amount,
                    'products' => $products,
                    'successUrl' => $successUrl,
                    'failUrl' => $failUrl,
                    'userIp' => $userIp,
                    'email' => $email,
                    'name' => $name,
                    'surname' => $surname,
                    'phone' => $phone,
                    'address' => $address,
                    'charge_id' => $result['charge_id'],
                    'paymentData' => $paymentData
                ])->render();
                exit;

            } catch (Throwable $exception) {
                Log::error('iPara Payment Error: ' . $exception->getMessage());

                $data['error'] = true;
                $data['message'] = $exception->getMessage();
            }

            return $data;
        }, 12, 2);

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $html) {
            return $html . IparaPaymentMethodForm::create()->renderForm();
        }, 92);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class === PaymentMethodEnum::class) {
                $values['IPARA'] = IPARA_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 19, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == IPARA_PAYMENT_METHOD_NAME) {
                $value = 'iPara';
            }

            return $value;
        }, 19, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == IPARA_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 19, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == IPARA_PAYMENT_METHOD_NAME) {
                $data = IparaPaymentService::class;
            }

            return $data;
        }, 19, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == IPARA_PAYMENT_METHOD_NAME) {
                $paymentService = new IparaPaymentService();
                $paymentDetail = $paymentService->getPaymentDetails($payment->charge_id);

                if ($paymentDetail) {
                    $data = view('plugins/ipara::detail', ['payment' => $paymentDetail, 'paymentModel' => $payment])->render();
                }
            }

            return $data;
        }, 19, 2);

        add_filter(PAYMENT_FILTER_GET_REFUND_DETAIL, function ($data, $payment, $refundId) {
            return $data;
        }, 19, 3);
    }
}
