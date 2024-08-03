<?php
// src/Console/SetupCommand.php
namespace AhmedEbead\LaraMultiAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupCommand extends Command
{
    protected $signature = 'multiauth:setup';
    protected $description = 'Set up Laravel Passport and OTP service for LaraMultiAuth package';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Publish Laravel Passport configuration
        $this->info('Publishing Laravel Passport configuration...');
        Artisan::call('vendor:publish', ['--provider' => 'Laravel\Passport\PassportServiceProvider']);

        // Run Passport migrations
        $this->info('Running Laravel Passport migrations...');
        Artisan::call('migrate');

        // Install Laravel Passport
        $this->info('Installing Laravel Passport...');
        Artisan::call('passport:install');

        // Publish OTP package configuration
        $this->info('Publishing OTP package configuration...');
        Artisan::call('vendor:publish', ['--provider' => 'Ichtrojan\LaravelOtp\OtpServiceProvider']);

        $this->info('Setup complete!');
    }
}
