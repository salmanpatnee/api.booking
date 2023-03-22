<?php

namespace App\Exports;

use App\Models\Account;
use Illuminate\Http\Request as HttpRequest;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    private $fileName = 'Customers.xlsx';

    protected $customers;
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Total Orders',
            'Total Spending',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->phone,
            $customer->sales_count,
            $customer->sales_amount,
        ];
    }

    public function query()
    {
        return Account::query()->select('id', 'name', 'email', 'phone', 'sales_count', 'sales_amount')->where('account_type', '=', 'customer')->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $customers = Account::query()->select('id', 'name', 'email', 'phone', 'sales_count', 'sales_amount')->where('account_type', '=', 'customer')->get();
        return $customers;
    }
}
