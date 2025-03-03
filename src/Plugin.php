<?php

namespace Botble\Ipara;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_ipara_name',
            'payment_ipara_description',
            'payment_ipara_public_key',
            'payment_ipara_private_key',
            'payment_ipara_mode',
            'payment_ipara_status',
        ]);
    }
}
