<?php

namespace App\Providers;

use App\Mail\SendGridApiTransport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;

class SendGridApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Mail::extend('sendgrid_api', function (array $config) {
            return new SendGridApiTransport($config['api_key']);
        });
    }
}