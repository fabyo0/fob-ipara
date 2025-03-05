<?php

declare(strict_types=1);

namespace Botble\Ipara\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;

class IparaPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(IPARA_PAYMENT_METHOD_NAME)
            ->paymentName('iPara')
            ->paymentDescription(__('Customer can buy product and pay directly using Visa, Credit card via :name', ['name' => 'iPara']))
            ->paymentLogo(url('vendor/core/plugins/ipara/images/ipara.png'))
            ->paymentUrl('https://www.ipara.com/')
            ->paymentInstructions(view('plugins/ipara::instructions')->render())
            ->add(
                get_payment_setting_key('public_key', IPARA_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ipara::ipara.public_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('public_key', IPARA_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                get_payment_setting_key('private_key', IPARA_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/ipara::ipara.private_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('private_key', IPARA_PAYMENT_METHOD_NAME))
            )
            ->add(
                get_payment_setting_key('mode', IPARA_PAYMENT_METHOD_NAME),
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/ipara::ipara.mode'))
                    ->helperText(trans('plugins/ipara::ipara.mode_helper'))
                    ->disabled(BaseHelper::hasDemoModeEnabled())
                    ->value(BaseHelper::hasDemoModeEnabled() ? true : get_payment_setting('mode', IPARA_PAYMENT_METHOD_NAME))
            );
    }
}
