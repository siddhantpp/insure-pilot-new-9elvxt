<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Laravel 10.0+
use App\Models\Claimant;
use App\Models\Loss;
use App\Models\MapLossClaimant;
use App\Http\Resources\ClaimantResource;
use Illuminate\Http\Request; // illuminate/http ^10.0
use Illuminate\Http\Response; // illuminate/http ^10.0
use Illuminate\Http\JsonResponse; // illuminate/http ^10.0
use Illuminate\Http\Resources\Json\ResourceCollection; // illuminate/http ^10.0

/**
 * API controller responsible for handling claimant-related requests in the Documents View feature.
 * This controller provides endpoints for retrieving claimant data for dropdown fields, particularly
 * for the dynamic dropdown controls where Claimant options are filtered based on the selected Loss Sequence.
 */
class ClaimantController extends Controller
{
    /**
     * Retrieve a list of all active claimants.
     *
     * @param Request $request
     * @return ResourceCollection Collection of ClaimantResource objects
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        
        $query = Claimant::active();
        
        // Apply search filter if provided
        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }
        
        $claimants = $query->orderBy('created_at')
                          ->paginate($perPage);
        
        return ClaimantResource::collection($claimants);
    }

    /**
     * Retrieve a specific claimant by ID.
     *
     * @param int $id
     * @return ClaimantResource|JsonResponse Single ClaimantResource object
     */
    public function show($id)
    {
        $claimant = Claimant::find($id);
        
        if (!$claimant) {
            return response()->json(['message' => 'Claimant not found'], 404);
        }
        
        return new ClaimantResource($claimant);
    }

    /**
     * Retrieve claimants associated with a specific loss for dropdown selection.
     *
     * @param Request $request
     * @param int $lossId
     * @return ResourceCollection|JsonResponse Collection of ClaimantResource objects
     */
    public function forLoss(Request $request, $lossId)
    {
        // Validate that the loss exists and is active
        $loss = Loss::active()->find($lossId);
        
        if (!$loss) {
            return response()->json(['message' => 'Loss not found or inactive'], 404);
        }
        
        // Get claimants for this loss using the forLoss scope
        $claimants = Claimant::active()
                          ->forLoss($lossId)
                          ->orderBy('map_loss_claimant.created_at') // Order by creation date for proper sequence
                          ->get();
        
        // Load the losses relationship for each claimant to ensure proper display_name generation
        foreach ($claimants as $claimant) {
            $claimant->load(['losses' => function ($query) use ($lossId) {
                $query->where('loss.id', $lossId);
            }]);
        }
        
        return ClaimantResource::collection($claimants);
    }

    /**
     * Search for claimants by name or other attributes.
     *
     * @param Request $request
     * @return ResourceCollection Collection of ClaimantResource objects
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        $perPage = $request->input('per_page', 15);
        
        $query = Claimant::active();
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
                // If there were a direct way to search by name, we would add it here
            });
        }
        
        $claimants = $query->orderBy('created_at')
                          ->paginate($perPage);
        
        return ClaimantResource::collection($claimants);
    }
}