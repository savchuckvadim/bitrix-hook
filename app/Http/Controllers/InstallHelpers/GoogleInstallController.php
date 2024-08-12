<?php
namespace App\Http\Controllers\InstallHelpers;

use App\Http\Controllers\Controller;
use App\Models\Portal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleInstallController extends Controller
{
    public static function getData(
 
        $token
    ) {

        $result = null;
        try {
           
            $url = 'https://script.google.com/macros/s/' . $token . '/exec';
            $response = Http::get($url);

            if ($response->successful()) {
                $googleData = $response->json();
                $result = $googleData;
            } else {
                Log::channel('telegram')->error("Failed to retrieve data from Google Sheets", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
}
