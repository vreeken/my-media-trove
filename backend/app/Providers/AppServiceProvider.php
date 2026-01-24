<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Media\MusicBrainzService;
use App\Services\Media\OmdbService;
use App\Services\Media\StreamingAvailabilityService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OmdbService::class, function ($app) {
            return new OmdbService(
                config('services.omdb.api_key'),
                config('services.omdb.base_url')
            );
        });

        $this->app->singleton(MusicBrainzService::class, function ($app) {
            return new MusicBrainzService(
                config('services.musicbrainz.base_url'),
                config('services.musicbrainz.user_agent')
            );
        });

        $this->app->singleton(StreamingAvailabilityService::class, function ($app) {
            return new StreamingAvailabilityService();
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
