<?php

namespace App\Services;

use App\Models\Upd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneCIntegrationService
{
    protected $baseUrl;

    protected $login;

    protected $password;

    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.1c.base_url');
        $this->login = config('services.1c.login');
        $this->password = config('services.1c.password');
        $this->timeout = config('services.1c.timeout', 30);
    }

    public function exportUpd(Upd $upd): array
    {
        try {
            $data = $upd->to1CFormat();

            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl.'/upd', $data);

            if ($response->successful()) {
                $responseData = $response->json();

                // Сохраняем идентификаторы из 1С
                $upd->update([
                    '1c_guid' => $responseData['guid'],
                    '1c_number' => $responseData['number'],
                    '1c_date' => Carbon::parse($responseData['date']),
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            } else {
                Log::error('Ошибка экспорта УПД в 1С', [
                    'upd_id' => $upd->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Ошибка экспорта в 1С: '.$response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Исключение при экспорте УПД в 1С', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Исключение: '.$e->getMessage(),
            ];
        }
    }

    public function getUpdStatus(string $guid): array
    {
        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout($this->timeout)
                ->get($this->baseUrl.'/upd/status/'.$guid);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json()['status'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Ошибка получения статуса: '.$response->status(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Исключение: '.$e->getMessage(),
            ];
        }
    }

    public function syncCompanies(): void
    {
        // Метод для синхронизации контрагентов с 1С
        $companies = Company::whereNull('1c_guid')->get();

        foreach ($companies as $company) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout($this->timeout)
                    ->post($this->baseUrl.'/counterparty', $company->get1CData());

                if ($response->successful()) {
                    $responseData = $response->json();

                    $company->update([
                        '1c_guid' => $responseData['guid'],
                        '1c_code' => $responseData['code'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Ошибка синхронизации компании с 1С', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
