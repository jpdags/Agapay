<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure SSL for Guzzle (used by Socialite) via environment variables
        // This is the most reliable way to configure SSL for Socialite
        $this->configureGuzzleSSL();
    }

    /**
     * Configure Guzzle SSL settings via environment
     */
    private function configureGuzzleSSL(): void
    {
        // Check if certificate file exists at configured path
        $caBundle = ini_get('curl.cainfo');
        $certExists = $caBundle && file_exists($caBundle);
        
        // If not found at configured path, try to find it and update ini_set
        if (!$certExists) {
            $possiblePaths = [
                'C:\Program Files\php-8.4.15\extras\ssl\cacert.pem',
                'C:\php\extras\ssl\cacert.pem',
                base_path('cacert.pem'),
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    // Update php.ini setting for this request
                    ini_set('curl.cainfo', $path);
                    $certExists = true;
                    Log::info('SSL certificate found and configured at: ' . $path);
                    break;
                }
            }
        } else {
            Log::info('SSL certificate configured at: ' . $caBundle);
        }
        
        // Only disable SSL in debug mode if certificate truly doesn't exist
        // This should be a last resort
        if (!$certExists && config('app.debug', false)) {
            Log::warning('SSL certificate not found. SSL verification may fail. Please update php.ini curl.cainfo to point to: C:\Program Files\php-8.4.15\extras\ssl\cacert.pem');
        }
    }
}
