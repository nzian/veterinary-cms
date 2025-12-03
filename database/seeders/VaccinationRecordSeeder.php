<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VaccinationRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing pets and visits
        $pets = DB::table('tbl_pet')->pluck('pet_id')->toArray();
        $visits = DB::table('tbl_visit_record')->pluck('visit_id')->toArray();
        $users = DB::table('tbl_user')->pluck('user_id')->toArray();
        
        if (empty($pets) || empty($visits) || empty($users)) {
            $this->command->error('No pets, visits, or users found. Please seed those tables first.');
            return;
        }

        $vaccines = [
            'Rabies Vaccine',
            'Distemper Vaccine',
            'Parvovirus Vaccine',
            'Bordetella Vaccine',
            'Leptospirosis Vaccine',
            'Canine Influenza Vaccine',
            'Feline Leukemia Vaccine',
            'FVRCP Vaccine',
            'Lyme Disease Vaccine',
            'Hepatitis Vaccine'
        ];

        $manufacturers = [
            'Zoetis',
            'Merck Animal Health',
            'Boehringer Ingelheim',
            'Elanco',
            'Virbac',
            'Merial',
            'Intervet',
            'Fort Dodge'
        ];

        $doses = ['1st Dose', '2nd Dose', '3rd Dose', 'Booster'];
        
        $vaccinationRecords = [];
        
        for ($i = 0; $i < 100; $i++) {
            $petId = $pets[array_rand($pets)];
            $visitId = $visits[array_rand($visits)];
            $vaccineName = $vaccines[array_rand($vaccines)];
            $manufacturer = $manufacturers[array_rand($manufacturers)];
            $dose = $doses[array_rand($doses)];
            
            $dateAdministered = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $nextDueDate = Carbon::parse($dateAdministered)->addDays(rand(14, 90))->format('Y-m-d');
            
            $userName = DB::table('tbl_user')
                ->where('user_id', $users[array_rand($users)])
                ->value('user_name');
            
            $vaccinationRecords[] = [
                'pet_id' => $petId,
                'visit_id' => $visitId,
                'vaccine_name' => $vaccineName,
                'dose' => $dose,
                'manufacturer' => $manufacturer,
                'batch_no' => 'BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                'date_administered' => $dateAdministered,
                'next_due_date' => $nextDueDate,
                'administered_by' => $userName ?? 'System Admin',
                'remarks' => $this->generateRemarks(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('tbl_vaccination_record')->insert($vaccinationRecords);
        
        $this->command->info('Successfully created 100 vaccination records!');
    }

    private function generateRemarks(): string
    {
        $remarks = [
            'No adverse reactions observed',
            'Pet tolerated vaccine well',
            'Mild lethargy post-vaccination',
            'Normal vaccination procedure',
            'Owner advised to monitor for 24 hours',
            'Follow-up scheduled',
            'Routine vaccination completed',
            'Pet showed no side effects',
            'Vaccine administered successfully',
            'Owner provided aftercare instructions'
        ];
        
        return $remarks[array_rand($remarks)];
    }
}
