<?php

namespace App\Exports;
use App\Models\SalesReturn;
use Illuminate\Http\Request as HttpRequest;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SaleReturnsExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    private $fileName = 'sale-returns.xlsx';

    protected $saleReturns;
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function headings(): array
    {
        return [
            'Date', 
            'Return Invoice ID', 
            'Sale Invoice ID', 
            'Customer', 
            'Amount Before Return', 
            'Return Amount', 
            'Amount After Return', 
        ];
    }

    public function map($saleReturn): array
    {
        $date  = Carbon::parse($saleReturn->date);
        return [
            $date->format('d M Y'), 
            $saleReturn->id, 
            $saleReturn->sale->id, 
            $saleReturn->sale->account->name, 
            $saleReturn->sale_amount_before_return, 
            $saleReturn->sale_return_amount, 
            $saleReturn->sale_amount_after_return, 
        ];
    }

        /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $saleReturns = SalesReturn::query()->indexQuery()->get();
        return $saleReturns;
    }
}
