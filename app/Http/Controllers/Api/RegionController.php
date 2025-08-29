<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RegionController extends Controller
{
    private $baseUrl = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    public function getProvinces()
    {
        try {
            $response = Http::get($this->baseUrl . '/provinces.json');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch provinces'], 500);
        }
    }

    public function getRegencies($provinceId)
    {
        try {
            $response = Http::get($this->baseUrl . '/regencies/' . $provinceId . '.json');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch regencies'], 500);
        }
    }

    public function getDistricts($regencyId)
    {
        try {
            $response = Http::get($this->baseUrl . '/districts/' . $regencyId . '.json');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch districts'], 500);
        }
    }

    public function getVillages($districtId)
    {
        try {
            $response = Http::get($this->baseUrl . '/villages/' . $districtId . '.json');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch villages'], 500);
        }
    }
}
