<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ActionType;

class ActionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds for action types used in document operations.
     * 
     * This seeder populates the action_type table with predefined action categories
     * that are used throughout the Documents View feature, particularly for the
     * Document History & Audit Trail functionality.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {
            try {
                // Define the active status ID and system user ID
                $activeStatusId = config('constants.status.active', 1);
                $systemUserId = config('constants.users.system', 1);
                
                // Create action type for document viewing
                ActionType::create([
                    'name' => 'view',
                    'description' => 'Document was viewed',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for document metadata editing
                ActionType::create([
                    'name' => 'edit',
                    'description' => 'Document metadata was edited',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for marking documents as processed
                ActionType::create([
                    'name' => 'process',
                    'description' => 'Document was marked as processed',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for reverting documents to editable state
                ActionType::create([
                    'name' => 'unprocess',
                    'description' => 'Document was marked as unprocessed',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for moving documents to trash
                ActionType::create([
                    'name' => 'trash',
                    'description' => 'Document was moved to trash',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for restoring documents from trash
                ActionType::create([
                    'name' => 'restore',
                    'description' => 'Document was restored from trash',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for document upload
                ActionType::create([
                    'name' => 'upload',
                    'description' => 'Document was uploaded',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for document download
                ActionType::create([
                    'name' => 'download',
                    'description' => 'Document was downloaded',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for document sharing
                ActionType::create([
                    'name' => 'share',
                    'description' => 'Document was shared',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
                
                // Create action type for document commenting
                ActionType::create([
                    'name' => 'comment',
                    'description' => 'Comment was added to document',
                    'status_id' => $activeStatusId,
                    'created_by' => $systemUserId,
                    'updated_by' => $systemUserId,
                ]);
            } catch (\Exception $e) {
                // Roll back the transaction if any errors occur
                DB::rollBack();
                throw $e;
            }
        });
    }
}