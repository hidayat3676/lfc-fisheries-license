<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Office;
use App\Models\Reservoir;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservoirMasterSeeder extends Seeder
{
    public function run(): void
    {
        $path = config('rflms.seed_csv_path');
        if (! is_string($path) || ! is_file($path)) {
            $this->command?->warn('Seed CSV not found: '.$path);

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command?->warn('Unable to open seed CSV.');

            return;
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return;
        }

        $header = array_map(static fn ($h) => trim((string) $h), $header);
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($handle, $header, &$created, &$updated) {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 6) {
                    continue;
                }

                $data = array_combine($header, array_pad($row, count($header), null));
                if ($data === false) {
                    continue;
                }

                $districtName = trim((string) ($data['district_name'] ?? ''));
                $waterName = trim((string) ($data['water_body_name'] ?? ''));
                if ($districtName === '' || $waterName === '') {
                    continue;
                }

                $district = District::query()->where('name', $districtName)->first();
                if (! $district) {
                    $district = District::query()->create([
                        'name' => $districtName,
                        'code' => strtoupper(str_replace(['.', ' ', '-'], '', $districtName)),
                        'is_active' => true,
                    ]);
                }

                $officeName = trim((string) ($data['office_name'] ?? ''));
                if ($officeName === '') {
                    $officeName = 'District Officer Fisheries '.$district->name;
                }

                $office = Office::query()->updateOrCreate(
                    [
                        'district_id' => $district->id,
                        'name' => $officeName,
                    ],
                    [
                        'phone' => $this->nullIfBlank($data['office_phone'] ?? null),
                        'email' => $this->nullIfBlank($data['office_email'] ?? null),
                        'is_active' => true,
                    ]
                );

                $seedId = $this->nullIfBlank($data['seed_id'] ?? null);
                $payload = [
                    'district_id' => $district->id,
                    'office_id' => $office->id,
                    'name' => $waterName,
                    'water_body_type' => $this->normalizeType($data['water_body_type'] ?? 'other'),
                    'trout_type' => $this->normalizeTrout($data['trout_type'] ?? 'unknown'),
                    'description' => $this->nullIfBlank($data['description'] ?? null),
                    'latitude' => $this->toFloat($data['latitude'] ?? null),
                    'longitude' => $this->toFloat($data['longitude'] ?? null),
                    'start_lat' => $this->toFloat($data['start_lat'] ?? null),
                    'start_lng' => $this->toFloat($data['start_lng'] ?? null),
                    'end_lat' => $this->toFloat($data['end_lat'] ?? null),
                    'end_lng' => $this->toFloat($data['end_lng'] ?? null),
                    'trout_stretch_notes' => $this->nullIfBlank($data['trout_stretch_notes'] ?? null),
                    'reserve_area_notes' => $this->nullIfBlank($data['reserve_area_notes'] ?? null),
                    'lease_notes' => $this->nullIfBlank($data['lease_notes'] ?? null),
                    'length_km_notes' => $this->nullIfBlank($data['length_km_notes'] ?? null),
                    'species_notes' => $this->nullIfBlank($data['species_notes'] ?? null),
                    'coordinates_raw' => $this->nullIfBlank($data['coordinates_raw'] ?? null),
                    'is_open_for_licensing' => $this->toNullableBool($data['e_licence_eligible'] ?? null),
                    'is_active' => strtolower((string) ($data['is_active_suggested'] ?? 'yes')) !== 'no',
                    'needs_review' => strtolower((string) ($data['needs_review'] ?? 'no')) === 'yes',
                    'source_file' => $this->nullIfBlank($data['source_file'] ?? null),
                ];

                if ($payload['latitude'] !== null && $payload['longitude'] !== null) {
                    $payload['directions_url'] = 'https://www.google.com/maps?q='.$payload['latitude'].','.$payload['longitude'];
                }

                if ($seedId) {
                    $existing = Reservoir::query()->where('seed_id', $seedId)->first();
                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        Reservoir::query()->create(['seed_id' => $seedId] + $payload);
                        $created++;
                    }
                } else {
                    Reservoir::query()->updateOrCreate(
                        [
                            'district_id' => $district->id,
                            'name' => $waterName,
                        ],
                        $payload
                    );
                    $created++;
                }
            }
        });

        fclose($handle);
        $this->command?->info("Reservoirs seeded: created={$created}, updated={$updated}");
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toFloat(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toNullableBool(mixed $value): ?bool
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        return in_array($value, ['1', 'yes', 'true', 'y'], true);
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['dam', 'river', 'stream', 'canal', 'headworks', 'other'], true)
            ? $type
            : 'other';
    }

    private function normalizeTrout(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['trout', 'non_trout', 'mixed', 'unknown'], true)
            ? $type
            : 'unknown';
    }
}
