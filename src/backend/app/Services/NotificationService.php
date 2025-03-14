<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Mail; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Illuminate\Support\Facades\Config; // ^10.0

/**
 * Service responsible for sending notifications to users about document-related events in the Insure Pilot system.
 */
class NotificationService
{
    /**
     * Creates a new NotificationService instance
     */
    public function __construct()
    {
        // Initialize the service with default configuration
    }

    /**
     * Sends a notification when a document is marked as processed
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentProcessedNotification(int $documentId, int $userId): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send processed notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send processed notification: User with ID {$userId} not found");
                return false;
            }

            $recipients = $this->getNotificationRecipients($document);
            $template = Config::get('mail.templates.document_processed', 'emails.document.processed');
            $subject = Config::get('mail.subjects.document_processed', 'Document Marked as Processed');
            
            $data = $this->formatNotificationData($document, $actionUser);
            
            $success = true;
            foreach ($recipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send processed notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document processed notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document processed notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sends a notification when a document is marked as unprocessed
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentUnprocessedNotification(int $documentId, int $userId): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send unprocessed notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send unprocessed notification: User with ID {$userId} not found");
                return false;
            }

            $recipients = $this->getNotificationRecipients($document);
            $template = Config::get('mail.templates.document_unprocessed', 'emails.document.unprocessed');
            $subject = Config::get('mail.subjects.document_unprocessed', 'Document Marked as Unprocessed');
            
            $data = $this->formatNotificationData($document, $actionUser);
            
            $success = true;
            foreach ($recipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send unprocessed notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document unprocessed notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document unprocessed notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sends a notification when a document is moved to trash
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentTrashedNotification(int $documentId, int $userId): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send trashed notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send trashed notification: User with ID {$userId} not found");
                return false;
            }

            $recipients = $this->getNotificationRecipients($document);
            $template = Config::get('mail.templates.document_trashed', 'emails.document.trashed');
            $subject = Config::get('mail.subjects.document_trashed', 'Document Moved to Trash');
            
            $data = $this->formatNotificationData($document, $actionUser);
            
            $success = true;
            foreach ($recipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send trashed notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document trashed notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document trashed notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sends a notification when a document's metadata is updated
     *
     * @param int $documentId
     * @param int $userId
     * @param array $changes
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentUpdatedNotification(int $documentId, int $userId, array $changes): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send updated notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send updated notification: User with ID {$userId} not found");
                return false;
            }

            $recipients = $this->getNotificationRecipients($document);
            $template = Config::get('mail.templates.document_updated', 'emails.document.updated');
            $subject = Config::get('mail.subjects.document_updated', 'Document Updated');
            
            $data = $this->formatNotificationData($document, $actionUser, ['changes' => $changes]);
            
            $success = true;
            foreach ($recipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send updated notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document updated notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document updated notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'changes' => $changes,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sends a notification when a new document is created
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentCreatedNotification(int $documentId, int $userId): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send created notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send created notification: User with ID {$userId} not found");
                return false;
            }

            $recipients = $this->getNotificationRecipients($document);
            $template = Config::get('mail.templates.document_created', 'emails.document.created');
            $subject = Config::get('mail.subjects.document_created', 'New Document Created');
            
            $data = $this->formatNotificationData($document, $actionUser);
            
            $success = true;
            foreach ($recipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send created notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document created notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document created notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sends a notification when a document is assigned to a user or group
     *
     * @param int $documentId
     * @param int $userId
     * @param array $assignedTo
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function sendDocumentAssignedNotification(int $documentId, int $userId, array $assignedTo): bool
    {
        try {
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("Failed to send assigned notification: Document with ID {$documentId} not found");
                return false;
            }

            $actionUser = User::find($userId);
            if (!$actionUser) {
                Log::error("Failed to send assigned notification: User with ID {$userId} not found");
                return false;
            }

            $template = Config::get('mail.templates.document_assigned', 'emails.document.assigned');
            $subject = Config::get('mail.subjects.document_assigned', 'Document Assigned to You');
            
            $data = $this->formatNotificationData($document, $actionUser);
            
            $success = true;
            
            // Find recipients from the assignedTo parameter
            $recipients = [];
            foreach ($assignedTo as $item) {
                if (isset($item['type']) && isset($item['id'])) {
                    if ($item['type'] === 'user') {
                        $user = User::find($item['id']);
                        if ($user) {
                            $recipients[] = $user;
                        }
                    } elseif ($item['type'] === 'group') {
                        $group = \App\Models\UserGroup::find($item['id']);
                        if ($group) {
                            $groupUsers = $group->users;
                            $recipients = array_merge($recipients, $groupUsers->all());
                        }
                    }
                }
            }
            
            // Remove duplicates
            $uniqueRecipients = collect($recipients)->unique('id')->all();
            
            foreach ($uniqueRecipients as $recipient) {
                $emailResult = $this->sendEmail($recipient->email, $subject, $template, $data);
                if (!$emailResult) {
                    $success = false;
                    Log::warning("Failed to send assigned notification to {$recipient->email} for document {$documentId}");
                }
            }
            
            Log::info("Document assigned notification sent for document {$documentId} by user {$userId}");
            return $success;
        } catch (\Exception $e) {
            Log::error("Error sending document assigned notification: " . $e->getMessage(), [
                'document_id' => $documentId,
                'user_id' => $userId,
                'assigned_to' => $assignedTo,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Gets the list of users who should receive notifications for a document
     *
     * @param Document $document
     * @return array Array of user objects who should receive notifications
     */
    private function getNotificationRecipients(Document $document): array
    {
        // Initialize empty recipients array
        $recipients = [];
        
        // Add directly assigned users from document->users relationship
        $document->load('users');
        foreach ($document->users as $user) {
            $recipients[$user->id] = $user;
        }
        
        // Get users from assigned user groups through document->userGroups relationship
        $document->load('userGroups.users');
        foreach ($document->userGroups as $userGroup) {
            foreach ($userGroup->users as $user) {
                $recipients[$user->id] = $user;
            }
        }
        
        // Add document creator if different from assigned users
        if ($document->created_by && !isset($recipients[$document->created_by])) {
            $creator = User::find($document->created_by);
            if ($creator) {
                $recipients[$creator->id] = $creator;
            }
        }
        
        // Add document updater if different from assigned users
        if ($document->updated_by && !isset($recipients[$document->updated_by])) {
            $updater = User::find($document->updated_by);
            if ($updater) {
                $recipients[$updater->id] = $updater;
            }
        }
        
        // Remove duplicate recipients
        return array_values($recipients);
    }

    /**
     * Formats document data for use in notification templates
     *
     * @param Document $document
     * @param User $actionUser
     * @param array $additionalData
     * @return array Formatted data for notification templates
     */
    private function formatNotificationData(Document $document, User $actionUser, array $additionalData = []): array
    {
        // Create base data array with document properties (id, name, description)
        $data = [
            'document' => [
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'file_url' => $document->file_url,
                'policy_number' => $document->policy_number,
                'loss_sequence' => $document->loss_sequence,
                'claimant_name' => $document->claimant_name,
                'producer_number' => $document->producer_number,
                'is_processed' => $document->is_processed,
                'assigned_to' => $document->assigned_to,
            ],
            'user' => [
                'id' => $actionUser->id,
                'name' => $actionUser->full_name,
                'email' => $actionUser->email,
                'role' => $actionUser->role_name,
            ],
            'app_name' => Config::get('app.name', 'Insure Pilot'),
            'app_url' => Config::get('app.url', ''),
            'document_url' => Config::get('app.url', '') . '/documents/view/' . $document->id,
        ];
        
        // Merge with any additional data provided
        return array_merge($data, $additionalData);
    }

    /**
     * Sends an email notification using the specified template and data
     *
     * @param string $email
     * @param string $subject
     * @param string $template
     * @param array $data
     * @return bool True if email was sent successfully, false otherwise
     */
    private function sendEmail(string $email, string $subject, string $template, array $data): bool
    {
        // Validate email address format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning("Invalid email address for notification: {$email}");
            return false;
        }
        
        try {
            // Replace placeholders in subject with actual data
            $processedSubject = $subject;
            if (isset($data['document']['name'])) {
                $processedSubject = str_replace('{document_name}', $data['document']['name'], $processedSubject);
            }
            
            // Use Mail facade to send email with template and data
            Mail::send($template, $data, function ($message) use ($email, $processedSubject) {
                $message->to($email)
                    ->subject($processedSubject);
            });
            
            return true;
        } catch (\Exception $e) {
            // Catch and log any exceptions during sending
            Log::error("Error sending email notification: " . $e->getMessage(), [
                'email' => $email,
                'template' => $template,
                'exception' => $e
            ]);
            return false;
        }
    }
}