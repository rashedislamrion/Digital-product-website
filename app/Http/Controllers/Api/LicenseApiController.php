<?php

namespace App\Http\Controllers\Api;

use App\Domain\Licensing\Services\LicenseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseApiController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

    /**
     * Activate an instance against a license key.
     * POST /api/v1/licenses/activate
     */
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string'],
            'instance_fingerprint' => ['required', 'string'],
            'hostname' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->licenseService->activate(
            rawKey: $validated['license_key'],
            instanceFingerprint: $validated['instance_fingerprint'],
            hostname: $validated['hostname'] ?? null,
            ipAddress: $request->ip(),
        );

        if (! $result['success']) {
            return response()->json([
                'valid' => false,
                'error' => $result['error'],
            ], $result['code'] ?? 422);
        }

        return response()->json($result['data'], 200);
    }

    /**
     * Validate an active instance license.
     * POST /api/v1/licenses/validate
     */
    public function validateLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string'],
            'instance_fingerprint' => ['required', 'string'],
        ]);

        $result = $this->licenseService->validate(
            rawKey: $validated['license_key'],
            instanceFingerprint: $validated['instance_fingerprint'],
        );

        if (! $result['success']) {
            return response()->json([
                'valid' => false,
                'error' => $result['error'],
            ], $result['code'] ?? 422);
        }

        return response()->json($result['data'], 200);
    }

    /**
     * Deactivate an instance, freeing up a seat.
     * POST /api/v1/licenses/deactivate
     */
    public function deactivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string'],
            'instance_fingerprint' => ['required', 'string'],
        ]);

        $result = $this->licenseService->deactivate(
            rawKey: $validated['license_key'],
            instanceFingerprint: $validated['instance_fingerprint'],
        );

        if (! $result['success']) {
            return response()->json([
                'deactivated' => false,
                'error' => $result['error'],
            ], $result['code'] ?? 422);
        }

        return response()->json($result['data'], 200);
    }
}
