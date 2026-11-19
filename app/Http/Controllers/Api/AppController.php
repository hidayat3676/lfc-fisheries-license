<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * =========================================================
     * MOBILE APP VERSION POLICY
     * =========================================================
     *
     * Public API - authentication is NOT required.
     *
     * Endpoint:
     * GET /api/app/version-policy
     * GET /api/v1/app/version-policy
     *
     * Flutter Header:
     * app-version: 29
     *
     * Purpose:
     * - Check installed mobile app version.
     * - Force update unsupported versions.
     * - Inform client about latest available version.
     * - Provide Play Store URL.
     *
     * =========================================================
     */
    public function versionPolicy(Request $request): JsonResponse
    {
        // =====================================================
        // APP VERSION CONFIGURATION
        // =====================================================
        //
        // minimumSupportedVersionCode:
        // Any installed version BELOW this value will be blocked
        // and force_update will be TRUE.
        //
        // latestVersionCode:
        // Latest version currently available on Play Store.
        //
        // Example:
        //
        // Installed = 28
        // Minimum   = 29
        // Latest    = 29
        //
        // Result:
        // force_update = true
        //
        // -----------------------------------------------------

        $minimumSupportedVersionCode = 3;

        $latestVersionCode = 3;

        // =====================================================
        // UPDATE MESSAGE
        // =====================================================

        $updateMessage =
            'A new version of Kp Fisheries E License is available. '
            .'Please update the application to continue.';

        // =====================================================
        // PLAY STORE URL
        // =====================================================

        $playStoreUrl =
            'https://play.google.com/store/apps/details'
            .'?id=com.smart.attendance.ldmis';

        // =====================================================
        // READ CLIENT APP VERSION
        // =====================================================
        //
        // Flutter must send:
        //
        // app-version: 29
        //
        // -----------------------------------------------------

        $clientVersionHeader = trim((string) $request->header('app-version', ''));

        // -----------------------------------------------------
        // Validate header
        // -----------------------------------------------------

        $clientVersion = 0;

        if ($clientVersionHeader !== '' && ctype_digit($clientVersionHeader)) {
            $clientVersion = (int) $clientVersionHeader;
        }

        // =====================================================
        // DETERMINE UPDATE STATUS
        // =====================================================

        $forceUpdate = true;

        $updateAvailable = true;

        if ($clientVersion > 0) {
            // Client is older than minimum supported version.
            if ($clientVersion < $minimumSupportedVersionCode) {
                $forceUpdate = true;
            }

            // Client is older than latest Play Store version.
            if ($clientVersion < $latestVersionCode) {
                $updateAvailable = true;
            }
        }

        // =====================================================
        // FORCE UPDATE REQUIRED
        // =====================================================

        if ($forceUpdate) {
            return response()->json([
                'success' => true,
                'code' => 'APP_UPDATE_REQUIRED',
                'message' => 'Please update to continue using Fisheries E License.',
                'data' => [
                    'current_version_code' => $clientVersion,
                    'minimum_supported_version_code' => $minimumSupportedVersionCode,
                    'latest_version_code' => $latestVersionCode,
                    'update_available' => true,
                    'force_update' => true,
                    'update_message' => $updateMessage,
                    'play_store_url' => $playStoreUrl,
                ],
            ], 200);
        }

        // =====================================================
        // SUPPORTED APP VERSION
        // =====================================================

        return response()->json([
            'success' => true,
            'code' => 'APP_VERSION_SUPPORTED',
            'message' => $updateAvailable
                ? 'A newer version of Kp Fisheries E License is available.'
                : 'Your Kp Fisheries E License application is up to date.',
            'data' => [
                'current_version_code' => $clientVersion > 0 ? $clientVersion : null,
                'minimum_supported_version_code' => $minimumSupportedVersionCode,
                'latest_version_code' => $latestVersionCode,
                'update_available' => $updateAvailable,
                'force_update' => false,
                'update_message' => $updateAvailable ? $updateMessage : null,
                'play_store_url' => $playStoreUrl,
            ],
        ], 200);
    }
}

