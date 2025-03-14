<?php

use Illuminate\Support\Facades\Route; // laravel/framework
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentActionController;
use App\Http\Controllers\Api\DocumentHistoryController;
use App\Http\Controllers\Api\MetadataController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\LossController;
use App\Http\Controllers\Api\ClaimantController;
use App\Http\Controllers\Api\ProducerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () { // Laravel v9+

    // Document Routes
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('documents.index'); // Get a paginated list of documents with optional filtering
        Route::get('/{id}', [DocumentController::class, 'show'])->name('documents.show')->middleware('validate.document.access'); // Get a specific document by ID
        Route::put('/{id}', [DocumentController::class, 'update'])->name('documents.update')->middleware('validate.document.access'); // Update a document's metadata
        Route::get('/{id}/file', [DocumentController::class, 'file'])->name('documents.file')->middleware('validate.document.access'); // Get the file associated with a document
        Route::post('/{id}/process', [DocumentActionController::class, 'process'])->name('documents.process')->middleware('validate.document.access'); // Mark a document as processed or unprocessed
        Route::post('/{id}/trash', [DocumentActionController::class, 'trash'])->name('documents.trash')->middleware('validate.document.access'); // Move a document to the trash
        Route::get('/{id}/history', [DocumentHistoryController::class, 'index'])->name('documents.history')->middleware('validate.document.access'); // Get the history of actions performed on a document
        Route::get('/{id}/history/last-edited', [DocumentHistoryController::class, 'lastEdited'])->name('documents.history.last-edited')->middleware('validate.document.access'); // Get information about the last edit made to a document
        Route::get('/{id}/history/action-types', [DocumentHistoryController::class, 'getActionTypes'])->name('documents.history.action-types')->middleware('validate.document.access'); // Get a list of action types used in a document's history
        Route::get('/{id}/history/filter', [DocumentHistoryController::class, 'filterByActionType'])->name('documents.history.filter')->middleware('validate.document.access'); // Filter document history by action type
    });

    // Metadata Routes
    Route::prefix('metadata')->group(function () {
        Route::get('/documents/{documentId}', [MetadataController::class, 'show'])->name('metadata.show')->middleware('validate.document.access'); // Get metadata for a specific document
        Route::put('/documents/{documentId}', [MetadataController::class, 'update'])->name('metadata.update')->middleware('validate.document.access'); // Update metadata for a specific document
        Route::get('/options/policies', [MetadataController::class, 'getPolicyOptions'])->name('metadata.options.policies'); // Get policy options for dropdown selection
        Route::get('/options/losses/{policyId}', [MetadataController::class, 'getLossOptions'])->name('metadata.options.losses'); // Get loss options for a specific policy
        Route::get('/options/claimants/{lossId}', [MetadataController::class, 'getClaimantOptions'])->name('metadata.options.claimants'); // Get claimant options for a specific loss
        Route::get('/options/producers', [MetadataController::class, 'getProducerOptions'])->name('metadata.options.producers'); // Get producer options for dropdown selection
        Route::get('/options/users', [MetadataController::class, 'getUserOptions'])->name('metadata.options.users'); // Get user options for assignment dropdown
        Route::get('/options/user-groups', [MetadataController::class, 'getUserGroupOptions'])->name('metadata.options.user-groups'); // Get user group options for assignment dropdown
    });

    // Policy Routes
    Route::prefix('policies')->group(function () {
        Route::get('/', [PolicyController::class, 'index'])->name('policies.index'); // Get a paginated list of policies with optional filtering
        Route::get('/{id}', [PolicyController::class, 'show'])->name('policies.show'); // Get a specific policy by ID
        Route::get('/options', [PolicyController::class, 'options'])->name('policies.options'); // Get policy options for dropdown selection
        Route::get('/producer/{producerId}', [PolicyController::class, 'producerPolicies'])->name('policies.producer'); // Get policies associated with a specific producer
        Route::get('/{policyId}/losses', [PolicyController::class, 'losses'])->name('policies.losses'); // Get losses associated with a specific policy
        Route::get('/{id}/url', [PolicyController::class, 'getUrl'])->name('policies.url'); // Get the URL for the policy view page
    });

    // Loss Routes
    Route::prefix('losses')->group(function () {
        Route::get('/', [LossController::class, 'index'])->name('losses.index'); // Get a paginated list of losses with optional filtering
        Route::get('/{id}', [LossController::class, 'show'])->name('losses.show'); // Get a specific loss by ID
        Route::get('/policy/{policyId}', [LossController::class, 'forPolicy'])->name('losses.policy'); // Get losses associated with a specific policy
        Route::get('/search', [LossController::class, 'search'])->name('losses.search'); // Search for losses by name or other attributes
    });

    // Claimant Routes
    Route::prefix('claimants')->group(function () {
        Route::get('/', [ClaimantController::class, 'index'])->name('claimants.index'); // Get a paginated list of claimants with optional filtering
        Route::get('/{id}', [ClaimantController::class, 'show'])->name('claimants.show'); // Get a specific claimant by ID
        Route::get('/loss/{lossId}', [ClaimantController::class, 'forLoss'])->name('claimants.loss'); // Get claimants associated with a specific loss
        Route::get('/search', [ClaimantController::class, 'search'])->name('claimants.search'); // Search for claimants by name or other attributes
         Route::get('/{id}/url', [ClaimantController::class, 'getUrl'])->name('claimants.url'); // Get the URL for the claimant view page
    });

    // Producer Routes
    Route::prefix('producers')->group(function () {
        Route::get('/', [ProducerController::class, 'index'])->name('producers.index'); // Get a paginated list of producers with optional filtering
        Route::get('/{id}', [ProducerController::class, 'show'])->name('producers.show'); // Get a specific producer by ID
        Route::get('/search', [ProducerController::class, 'search'])->name('producers.search'); // Search for producers by name or other attributes
        Route::get('/{id}/url', [ProducerController::class, 'getUrl'])->name('producers.url'); // Get the URL for the producer view page
    });
});