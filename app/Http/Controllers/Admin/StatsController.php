<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LocalAnalytics;
use App\Services\UmamiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function __invoke(Request $request, LocalAnalytics $local, UmamiClient $umami): View
    {
        $range = (string) $request->query('range', '7d');
        $localData = $local->available() ? $local->dashboard($range) : ['ok' => false];

        // Optional Umami enrichment when API token exists
        $umamiData = $umami->configured() ? $umami->dashboard($range) : ['ok' => false, 'error' => 'no_token'];

        return view('admin.stats', [
            'local' => $localData,
            'umami' => $umamiData,
            'range' => $range,
            'umamiConfigured' => $umami->configured(),
            'umamiUrl' => $umami->baseUrl() ?: 'https://umami.sudoshz.ir',
            'websiteId' => $umami->websiteId(),
        ]);
    }
}
