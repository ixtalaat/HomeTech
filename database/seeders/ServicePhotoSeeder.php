<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServicePhotoSeeder extends Seeder
{
    /**
     * Service names mapped to seed photo fixtures.
     *
     * Fixture credits (CC BY / BY-SA, commercial use allowed — keep the
     * creator name with the file if you redistribute it):
     * faucet.jpg — “Bathroom Remodel” © Jeremy Levine Design (CC BY 2.0)
     * pipes.jpg — “Plumbing” © cobaltfish (CC BY-SA 2.0)
     * heater.jpg — “Plumber uses two wrenches to tighten a fitting” © Tomwsulcer (CC0 1.0)
     * drain.jpg — “A Stainless Steel Kitchen Sink Drain” © aqua.mech (CC BY 2.0)
     * socket.jpg — “Abstracts - a light switch” © R/DV/RS (CC BY 2.0)
     * ac-freon.jpg — “AC Manifold Gauges checking refrigerant charge” © Phyxter Home Services (CC BY 2.0)
     * ac-compressor.jpg — “Aging Condenser Unit of a Split Air Conditioning System” © Chris Hunkeler (CC BY-SA 2.0)
     * paint-room.jpg — “House painter at work” © Richard Sutcliffe (CC BY-SA 2.0)
     *
     * @var array<string, string>
     */
    private const PHOTOS = [
        'Faucet & Tap Repair' => 'faucet.jpg',
        'Pipe Leakage Detection & Fix' => 'pipes.jpg',
        'Water Heater Maintenance' => 'heater.jpg',
        'Drain Unclogging & Cleaning' => 'drain.jpg',
        'Circuit Breaker & Short Circuit Repair' => 'breaker.jpg',
        'Lighting & Chandelier Installation' => 'chandelier.jpg',
        'Wall Socket & Switch Replacement' => 'socket.jpg',
        'AC Full Seasonal Maintenance & Cleaning' => 'ac-clean.jpg',
        'AC Freon Gas Refill & Pressure Check' => 'ac-freon.jpg',
        'AC Compressor & Capacitor Diagnosis' => 'ac-compressor.jpg',
        'Single Room Wall Repainting' => 'paint-room.jpg',
        'Wall Crack Patching & Touch-up' => 'plaster.jpg',
        'Washing Machine Repair' => 'washer.jpg',
        'Refrigerator & Freezer Repair' => 'fridge.jpg',
    ];

    /**
     * Attach seed photos to services missing a cover photo.
     *
     * Existing uploads are never overwritten, so admins keep full control.
     */
    public function run(): void
    {
        foreach (self::PHOTOS as $name => $file) {
            $service = Service::where('name', $name)->first();

            if ($service === null || ! empty($service->cover_photo)) {
                continue;
            }

            $source = database_path("seeders/photos/{$file}");

            if (! is_file($source)) {
                continue;
            }

            $path = 'services/seed-'.Str::slug(pathinfo($file, PATHINFO_FILENAME)).'.'.strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            Storage::disk('public')->put($path, (string) file_get_contents($source));
            $service->update(['cover_photo' => $path]);
        }
    }
}
