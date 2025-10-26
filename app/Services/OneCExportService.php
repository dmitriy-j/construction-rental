<?php

namespace App\Services;

use App\Models\Upd;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OneCExportService
{
    public function exportUpd(Upd $upd, string $format = 'xml'): string
    {
        try {
            $data = $upd->to1CFormat();

            if ($format === 'xml') {
                return $this->generateXml($data);
            } else {
                return $this->generateJson($data);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта УПД в формат 1С', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function generateXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Документ></Документ>');
        $xml->addAttribute('Дата', $data['document']['date']);
        $xml->addAttribute('Номер', $data['document']['number']);
        $xml->addAttribute('Время', now()->format('H:i:s'));
        $xml->addAttribute('Сумма', $data['amounts']['total']);

        // Заголовок документа
        $header = $xml->addChild('Заголовок');
        $header->addChild('Номер', $data['document']['number']);
        $header->addChild('Дата', $data['document']['date']);
        $header->addChild('ВидОперации', $data['document']['operation_type']);
        $header->addChild('Валюта', $data['document']['currency']);
        $header->addChild('Курс', $data['document']['currency_rate']);
        $header->addChild('СуммаВклНДС', $data['document']['vat_included'] ? 'true' : 'false');

        // Контрагенты
        $this->addCounterparty($xml, 'Поставщик', $data['seller']);
        $this->addCounterparty($xml, 'Покупатель', $data['buyer']);

        // Табличная часть
        $table = $xml->addChild('Товары');
        foreach ($data['items'] as $item) {
            $itemNode = $table->addChild('Товар');
            $itemNode->addChild('Наименование', htmlspecialchars($item['name']));
            $itemNode->addChild('Количество', $item['quantity']);
            $itemNode->addChild('Единица', $item['unit']);
            $itemNode->addChild('Цена', $item['price']);
            $itemNode->addChild('Сумма', $item['amount']);
            $itemNode->addChild('СтавкаНДС', $item['vat_rate']);
            $itemNode->addChild('СуммаНДС', $item['vat_amount']);
            $itemNode->addChild('СчетУчета', $item['accounting_info']['income_account']);
            $itemNode->addChild('СчетНДС', $item['accounting_info']['vat_account']);
        }

        // Суммы
        $amounts = $xml->addChild('Суммы');
        $amounts->addChild('ВсегоБезНДС', $data['amounts']['without_tax']);
        $amounts->addChild('ВсегоНДС', $data['amounts']['tax']);
        $amounts->addChild('ВсегоСНДС', $data['amounts']['total']);

        return $xml->asXML();
    }

    protected function addCounterparty(\SimpleXMLElement $xml, string $role, array $companyData): void
    {
        $node = $xml->addChild($role);
        $node->addChild('Наименование', htmlspecialchars($companyData['name']));
        $node->addChild('ИНН', $companyData['inn']);
        $node->addChild('КПП', $companyData['kpp']);
        $node->addChild('ОГРН', $companyData['ogrn']);
        $node->addChild('Адрес', htmlspecialchars($companyData['address']));

        $bank = $node->addChild('Банк');
        $bank->addChild('Наименование', htmlspecialchars($companyData['bank_name']));
        $bank->addChild('РасчетныйСчет', $companyData['bank_account']);
        $bank->addChild('БИК', $companyData['bik']);
        $bank->addChild('КорреспондентскийСчет', $companyData['correspondent_account']);

        if (isset($companyData['signature'])) {
            $signature = $node->addChild('Подпись');
            $signature->addChild('Должность', $companyData['signature']['position']);
            $signature->addChild('Имя', $companyData['signature']['name']);
            $signature->addChild('Дата', $companyData['signature']['date']);
        }
    }

    protected function generateJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function saveExportFile(Upd $upd, string $content, string $format = 'xml'): string
    {
        $fileName = "upd_export_{$upd->number}_{$upd->issue_date->format('Ymd')}.{$format}";
        $filePath = "upd_exports/{$fileName}";

        Storage::disk('local')->put($filePath, $content);

        return $filePath;
    }
}
