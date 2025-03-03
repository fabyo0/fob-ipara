<?php

namespace Botble\Ipara\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class IparaServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/ipara')
            ->loadHelpers()
            ->loadAndPublishViews()
            ->publishAssets()
            ->loadAndPublishTranslations()
            ->loadRoutes();

        $this->app->register(HookServiceProvider::class);
    }
}
