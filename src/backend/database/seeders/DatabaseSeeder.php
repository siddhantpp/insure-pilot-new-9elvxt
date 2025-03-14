<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; // laravel/framework ^10.0

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This method executes all database seeders in the correct order to ensure
     * proper data dependencies. It populates the database with initial data required
     * for the Documents View feature, including action types, policy prefixes,
     * users, and user groups.
     *
     * @return void
     */
    public function run()
    {
        // Seed reference data first
        // Action types for document operations and audit logging
        $this->call(ActionTypeSeeder::class);
        
        // Policy prefixes for policy number fields
        $this->call(PolicyPrefixSeeder::class);
        
        // Create user accounts with different roles
        // This seeder also creates user groups
        $this->call(UserSeeder::class);
        
        // Note: Additional seeders can be added here as the system evolves
        // Make sure to maintain proper order of dependencies
    }
}