<?php

namespace App\Exports;

use App\Models\BookingListDetails;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Http\Request as HttpRequest;

class BookingItemsExport implements FromCollection
{
    use Exportable;

    private $fileName = 'bookings.xlsx';

    protected $items;
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function headings(): array
    {
        return [
            'ID', 
            // 'Customer Name', 
            // 'Date', 
            // 'Paid By', 
            // 'Cost', 
            // 'Delivery Charges', 
            // 'Total', 
            // 'Profit',
            // 'Status'
        ];
    }

    public function map($item): array
    {
        $date  = Carbon::parse($item->date);

        return [
            $item->id, 
            // $sale->account->name, 
            // $date->format('d M Y') . ' ' . $sale->created_at->format('h:i a'), 
            // $sale->payment_method_id === 1 ? 'Cash' : 'Bank',
            // $sale->purchase_amount, 
            // $sale->shipping_charges,
            // $sale->net_amount, 
            // $sale->net_amount - ($sale->purchase_amount + $sale->shipping_charges), 
            // $sale->status

        ];
    }


     /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $items = BookingListDetails::indexQuery()->get();
        return $items;
    }
}
