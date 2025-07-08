<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Company;
use App\Models\Platform;
use Barryvdh\DomPDF\Facade\Pdf;

class UPDPdfGenerator
{
    protected $platform;

    public function __construct()
    {
        $this->platform = Platform::first() ?? new Platform();
    }

    public function generateForLessor(Order $order)
    {
        return $this->generateDocument(
            $order,
            $order->lessorCompany,
            'УПД_арендодатель'
        );
    }

    public function generateForLessee(Order $order)
    {
        return $this->generateDocument(
            $order,
            $order->lesseeCompany,
            'УПД_арендатор'
        );
    }

    protected function generateDocument(Order $order, Company $counterparty, string $type)
    {
        $pdf = Pdf::loadView('pdf.upd', [
            'order' => $order,
            'platform' => $this->platform,
            'counterparty' => $counterparty,
            'type' => $type
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isPhpEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download("УПД_{$order->id}_{$type}.pdf");
    }
}
