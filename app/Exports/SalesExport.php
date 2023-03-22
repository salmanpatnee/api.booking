<?php

namespace App\Exports;
use App\Models\Sale;
use Illuminate\Http\Request as HttpRequest;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    private $fileName = 'sales.xlsx';

    protected $sales;
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function headings(): array
    {
        return [
            'ID', 
            'Customer Name', 
            'Date', 
            'Paid By', 
            'Cost', 
            'Delivery Charges', 
            'Total', 
            'Profit',
            'Status'
        ];
    }

    public function map($sale): array
    {
        $date  = Carbon::parse($sale->date);

        return [
            $sale->id, 
            $sale->account->name, 
            $date->format('d M Y') . ' ' . $sale->created_at->format('h:i a'), 
            $sale->payment_method_id === 1 ? 'Cash' : 'Bank',
            $sale->purchase_amount, 
            $sale->shipping_charges,
            $sale->net_amount, 
            $sale->net_amount - ($sale->purchase_amount + $sale->shipping_charges), 
            $sale->status

        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $sales = Sale::query()->indexQuery()->get();
        return $sales;
    }
}
