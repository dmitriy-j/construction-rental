<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        DeliveryNoteSigned::class => [
            UpdateEquipmentLocation::class,
        ],
        PlatformDeliveryRequested::class => [
            PlatformDeliveryHandler::class,
        ],
        OperatorMissing::class => [
            HandleOperatorMissing::class,
        ],

        OrderConfirmed::class => [
            CreateInvoiceOnOrderConfirmation::class,
        ],

         NewProposalReceived::class => [
            SendProposalNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
