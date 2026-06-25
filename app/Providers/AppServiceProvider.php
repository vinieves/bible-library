<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        View::composer(['layouts.*', 'public.*', 'pages.*', 'members.*', 'auth.*', 'components.members.*'], function ($view) {
            $view->with('siteSettings', [
                'name' => Setting::get('site_name', 'Biblioteca Bíblica Digital'),
                'tagline' => Setting::get('site_tagline', 'Todos los LIBROS DE LA BIBLIA explicados versículo por versículo'),
                'support_email' => Setting::get('support_email', 'soporte@biblioteca.test'),
                'footer_text' => Setting::get('footer_text', '© Biblioteca Bíblica Digital'),
                'primary_color' => Setting::get('primary_color', '#6E4C2C'),
            ]);
        });
    }
}
