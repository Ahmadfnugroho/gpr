<?php

namespace App\Http\Services;

use Exception;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetsServices
{
    public $client, $service, $spreadsheetId, $range;


    public function __construct()
    {
        $this->client = $this->importUsers();
        // Log::info('Importing users from Google Sheets spreadsheet');

        // Cek apakah spreadsheet_id terbaca
        $this->spreadsheetId = config('google.spreadsheet_id');
        if (!$this->spreadsheetId) {
            Log::error('Spreadsheet ID is missing. Check your .env file.');
            throw new Exception('Spreadsheet ID is missing.');
        }

        // Log::info('Using spreadsheet ID: ' . $this->spreadsheetId);
        // Log::info('Using range: A:N');

        $this->service = new Sheets($this->client);
        $this->range = 'A:N';
    }
    public function importUsers()
    {
        $client = new Client();
        $client->setApplicationName(config('google.application_name'));
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(config('google.credentials_path'));
        $client->setAccessType('offline');

        return $client;
    }

    public function readSheet()
    {
        $doc = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
        return $doc;
    }
}
