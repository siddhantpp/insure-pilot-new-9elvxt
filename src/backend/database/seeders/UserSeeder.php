<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // laravel/framework ^10.0
use App\Models\User;
use App\Models\UserGroup;

class UserSeeder extends Seeder
{
    /**
     * Executes the database seeding process for users and user groups.
     *
     * @return void
     */
    public function run()
    {
        // Create user groups
        $groups = $this->createUserGroups();
        
        // Create admin user first so we can reference the ID for created_by fields
        $admin = $this->createAdminUser();
        
        // Update user groups with the admin user ID
        foreach ($groups as $group) {
            $group->created_by = $admin->id;
            $group->updated_by = $admin->id;
            $group->save();
        }
        
        // Create users with different roles
        $manager = $this->createManagerUser($admin->id, $groups[0]); // Claims Department manager
        $adjusters = $this->createAdjusters($admin->id, $groups[0]); // Claims Department adjusters
        $underwriters = $this->createUnderwriters($admin->id, $groups[1]); // Underwriting Department underwriters
        $supportStaff = $this->createSupportStaff($admin->id, $groups[2]); // Support Team staff
        $readOnlyUsers = $this->createReadOnlyUsers($admin->id, $groups[0]); // Read-only users in Claims Department
    }
    
    /**
     * Creates default user groups for the application.
     *
     * @return array Array of created user group models
     */
    private function createUserGroups()
    {
        $groups = [];
        
        // Claims Department
        $groups[] = UserGroup::create([
            'name' => 'Claims Department',
            'description' => 'Department responsible for processing insurance claims',
            'status_id' => 1, // Active
        ]);
        
        // Underwriting Department
        $groups[] = UserGroup::create([
            'name' => 'Underwriting Department',
            'description' => 'Department responsible for evaluating insurance risks',
            'status_id' => 1, // Active
        ]);
        
        // Support Team
        $groups[] = UserGroup::create([
            'name' => 'Support Team',
            'description' => 'Team responsible for providing technical and operational support',
            'status_id' => 1, // Active
        ]);
        
        return $groups;
    }
    
    /**
     * Creates an administrator user with full system access.
     *
     * @return \App\Models\User The created admin user model
     */
    private function createAdminUser()
    {
        return User::create([
            'username' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@insurepilot.com',
            'password' => Hash::make('Admin@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_ADMIN,
            'description' => 'System administrator with full access',
            'status_id' => 1, // Active
        ]);
    }
    
    /**
     * Creates a manager user with department oversight capabilities.
     *
     * @param int $adminId The ID of the admin user for referencing
     * @param \App\Models\UserGroup $group The user group to assign the manager to
     * @return \App\Models\User The created manager user model
     */
    private function createManagerUser($adminId, $group)
    {
        return User::create([
            'username' => 'manager',
            'first_name' => 'Department',
            'last_name' => 'Manager',
            'email' => 'manager@insurepilot.com',
            'password' => Hash::make('Manager@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_MANAGER,
            'user_group_id' => $group->id,
            'description' => 'Department manager with oversight responsibilities',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }
    
    /**
     * Creates multiple adjuster users for claims processing.
     *
     * @param int $adminId The ID of the admin user for referencing
     * @param \App\Models\UserGroup $group The user group to assign the adjusters to
     * @return array Array of created adjuster user models
     */
    private function createAdjusters($adminId, $group)
    {
        $adjusters = [];
        
        $adjusters[] = User::create([
            'username' => 'adjuster1',
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'email' => 'sarah.johnson@insurepilot.com',
            'password' => Hash::make('Adjuster@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_ADJUSTER,
            'user_group_id' => $group->id,
            'description' => 'Senior claims adjuster',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        $adjusters[] = User::create([
            'username' => 'adjuster2',
            'first_name' => 'Michael',
            'last_name' => 'Smith',
            'email' => 'michael.smith@insurepilot.com',
            'password' => Hash::make('Adjuster@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_ADJUSTER,
            'user_group_id' => $group->id,
            'description' => 'Claims adjuster specializing in auto claims',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        $adjusters[] = User::create([
            'username' => 'adjuster3',
            'first_name' => 'Jessica',
            'last_name' => 'Williams',
            'email' => 'jessica.williams@insurepilot.com',
            'password' => Hash::make('Adjuster@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_ADJUSTER,
            'user_group_id' => $group->id,
            'description' => 'Claims adjuster specializing in property claims',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        return $adjusters;
    }
    
    /**
     * Creates multiple underwriter users for policy management.
     *
     * @param int $adminId The ID of the admin user for referencing
     * @param \App\Models\UserGroup $group The user group to assign the underwriters to
     * @return array Array of created underwriter user models
     */
    private function createUnderwriters($adminId, $group)
    {
        $underwriters = [];
        
        $underwriters[] = User::create([
            'username' => 'underwriter1',
            'first_name' => 'Robert',
            'last_name' => 'Davis',
            'email' => 'robert.davis@insurepilot.com',
            'password' => Hash::make('Underwriter@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_UNDERWRITER,
            'user_group_id' => $group->id,
            'description' => 'Senior underwriter',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        $underwriters[] = User::create([
            'username' => 'underwriter2',
            'first_name' => 'Jennifer',
            'last_name' => 'Martinez',
            'email' => 'jennifer.martinez@insurepilot.com',
            'password' => Hash::make('Underwriter@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_UNDERWRITER,
            'user_group_id' => $group->id,
            'description' => 'Underwriter specializing in commercial policies',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        return $underwriters;
    }
    
    /**
     * Creates support staff users for basic document handling.
     *
     * @param int $adminId The ID of the admin user for referencing
     * @param \App\Models\UserGroup $group The user group to assign the support staff to
     * @return array Array of created support staff user models
     */
    private function createSupportStaff($adminId, $group)
    {
        $supportStaff = [];
        
        $supportStaff[] = User::create([
            'username' => 'support1',
            'first_name' => 'David',
            'last_name' => 'Wilson',
            'email' => 'david.wilson@insurepilot.com',
            'password' => Hash::make('Support@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_SUPPORT,
            'user_group_id' => $group->id,
            'description' => 'Technical support specialist',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        $supportStaff[] = User::create([
            'username' => 'support2',
            'first_name' => 'Lisa',
            'last_name' => 'Thompson',
            'email' => 'lisa.thompson@insurepilot.com',
            'password' => Hash::make('Support@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_SUPPORT,
            'user_group_id' => $group->id,
            'description' => 'Document processing specialist',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        return $supportStaff;
    }
    
    /**
     * Creates read-only users for limited access scenarios.
     *
     * @param int $adminId The ID of the admin user for referencing
     * @param \App\Models\UserGroup $group The user group to assign the read-only users to
     * @return array Array of created read-only user models
     */
    private function createReadOnlyUsers($adminId, $group)
    {
        $readOnlyUsers = [];
        
        $readOnlyUsers[] = User::create([
            'username' => 'readonly1',
            'first_name' => 'James',
            'last_name' => 'Brown',
            'email' => 'james.brown@insurepilot.com',
            'password' => Hash::make('ReadOnly@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_READONLY,
            'user_group_id' => $group->id,
            'description' => 'Auditor with read-only access',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        $readOnlyUsers[] = User::create([
            'username' => 'readonly2',
            'first_name' => 'Emily',
            'last_name' => 'Garcia',
            'email' => 'emily.garcia@insurepilot.com',
            'password' => Hash::make('ReadOnly@123!'), // Secure password for demo
            'user_type_id' => User::ROLE_READONLY,
            'user_group_id' => $group->id,
            'description' => 'Compliance officer with read-only access',
            'status_id' => 1, // Active
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        
        return $readOnlyUsers;
    }
}