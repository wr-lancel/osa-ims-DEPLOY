<?php

namespace Database\Seeders;

use App\Models\OrgPosition;
use App\Models\StudentOrganization;
use Illuminate\Database\Seeder;

class OrgPositionsSeeder extends Seeder
{
    /**
     * Seed org_positions for existing organizations.
     */
    public function run(): void
    {
        $positions = [
            'President',
            'Vice President',
            'Secretary',
            'Treasurer',
            'Auditor',
            'PIO',
            'Business Manager',
            'Sergeant-at-Arms',
        ];

        StudentOrganization::where('status', 'active')->each(function (StudentOrganization $org) use ($positions) {
            foreach ($positions as $name) {
                OrgPosition::firstOrCreate(
                    [
                        'org_id' => $org->org_id,
                        'position_name' => $name,
                    ],
                    [
                        'description' => null,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
