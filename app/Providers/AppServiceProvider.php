<?php

namespace App\Providers;

use App\Services\BureaucratDecoderService;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BureaucratDecoderService::class, function ($app) {
            return new BureaucratDecoderService(
                $app->make(HttpClient::class),
                config('services.yagpt.folder_id') ?? env('YAGPT_FOLDER_ID'),
                config('services.yagpt.api_key') ?? env('YAGPT_API_KEY'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
