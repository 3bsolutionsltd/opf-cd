<?php

namespace App\Http\Controllers;

use App\Services\LeadQualificationService;
use Illuminate\Http\JsonResponse;

/**
 * Lead Qualification Controller
 *
 * Thin pass-through controller — NO transformations, NO calculations.
 * Calls ONE service: LeadQualificationService.
 *
 * User ID is injected by InjectAuthenticatedUserId middleware.
 */
class LeadQualificationController extends Controller
{
    private LeadQualificationService $service;

    public function __construct(LeadQualificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Calculate and return the BANT qualification score for an opportunity.
     *
     * GET /api/opportunities/{opportunityId}/qualify
     */
    public function score(int $opportunityId): JsonResponse
    {
        $result = $this->service->calculateQualificationScore($opportunityId);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Opportunity not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ], 200);
    }
}
