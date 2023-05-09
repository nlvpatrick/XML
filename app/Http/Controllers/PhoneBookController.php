<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PhoneBookController extends Controller
{
    //

    public function index(Request $request)
    {
        $access_token = '0af13908518f9dc57b7bbf1fba8158af';

        // $response = Http::withToken($access_token)->post(config('connectware.url').'/ns-api/?object=contact&action=read&domain=rendell_domain.re&format=json&includeDomain=yes&user=1003');

        $groupData = Http::withToken($access_token)->post(config('connectware.url').'/ns-api/?action=list&format=json&object='.$request->object.'&domain='.$request->domain.'');
        $data = $groupData->json();

        foreach ($data as $key => $value) {
            // $group =
        }

        return response(
            $data
        );
    }
}
