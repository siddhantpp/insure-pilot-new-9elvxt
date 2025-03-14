<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; // laravel/framework ^10.0
use Illuminate\Support\Facades\DB; // illuminate/support/facades ^10.0
use App\Models\PolicyPrefix;

/**
 * Seeder class responsible for populating the policy_prefixes table with predefined
 * insurance policy prefix codes used throughout the Documents View feature.
 */
class PolicyPrefixSeeder extends Seeder
{
    /**
     * Executes the database seeding process for policy prefixes.
     *
     * Creates standardized policy prefix codes used throughout the Documents View feature
     * for categorizing different types of insurance policies.
     *
     * @return void
     */
    public function run()
    {
        // Begin a database transaction to ensure all policy prefixes are created atomically
        DB::beginTransaction();
        
        try {
            // System admin user ID - adjust as needed for your system
            $adminUserId = 1;
            
            // Create standard policy prefixes
            PolicyPrefix::create([
                'name' => 'PLCY',
                'description' => 'Standard insurance policy',
                'status_id' => 1, // Assuming 1 = active
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'AUTO',
                'description' => 'Automobile insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'HOME',
                'description' => 'Homeowner\'s insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'LIFE',
                'description' => 'Life insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'HLTH',
                'description' => 'Health insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'COMM',
                'description' => 'Commercial insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'UMBR',
                'description' => 'Umbrella insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            PolicyPrefix::create([
                'name' => 'LIAB',
                'description' => 'Liability insurance policy',
                'status_id' => 1,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);
            
            // Commit the transaction if all prefixes are created successfully
            DB::commit();
        } catch (\Exception $e) {
            // Roll back the transaction if any errors occur during the seeding process
            DB::rollBack();
            
            // Re-throw the exception to show the error
            throw $e;
        }
    }
}