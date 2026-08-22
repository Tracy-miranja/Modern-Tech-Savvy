<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class P9Export implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function ($item) {
            $row = [
                'Employee Name' => $item['employee_name'],
                'PIN' => $item['tax_no'],
            ];
            for ($i = 1; $i <= 12; $i++) {
                $month = \DateTime::createFromFormat('!m', $i)->format('F');
                $row["{$month} Basic Salary"] = $item['monthly_data'][$i]['basic_salary'];
                $row["{$month} Benefits Non Cash"] = $item['monthly_data'][$i]['benefits_non_cash'];
                $row["{$month} Value of Quarters"] = $item['monthly_data'][$i]['value_of_quarters'];
                $row["{$month} Total Gross Pay"] = $item['monthly_data'][$i]['total_gross_pay'];
                $row["{$month} Retirement E1"] = $item['monthly_data'][$i]['retirement_e1'];
                $row["{$month} Retirement E2"] = $item['monthly_data'][$i]['retirement_e2'];
                $row["{$month} Retirement E3"] = $item['monthly_data'][$i]['retirement_e3'];
                $row["{$month} Owner Occupied Interest"] = $item['monthly_data'][$i]['owner_occupied_interest'];
                $row["{$month} Retirement Contribution"] = $item['monthly_data'][$i]['retirement_contribution'];
                $row["{$month} Chargeable Pay"] = $item['monthly_data'][$i]['chargeable_pay'];
                $row["{$month} Tax Charged"] = $item['monthly_data'][$i]['tax_charged'];
                $row["{$month} Personal Relief"] = $item['monthly_data'][$i]['personal_relief'];
                $row["{$month} Insurance Relief"] = $item['monthly_data'][$i]['insurance_relief'];
                $row["{$month} PAYE"] = $item['monthly_data'][$i]['paye'];
            }
            return array_merge($row, [
                'Total Basic Salary' => $item['totals']['basic_salary'],
                'Total Benefits Non Cash' => $item['totals']['benefits_non_cash'],
                'Total Value of Quarters' => $item['totals']['value_of_quarters'],
                'Total Gross Pay' => $item['totals']['total_gross_pay'],
                'Total Retirement E1' => $item['totals']['retirement_e1'],
                'Total Retirement E2' => $item['totals']['retirement_e2'],
                'Total Retirement E3' => $item['totals']['retirement_e3'],
                'Total Owner Occupied Interest' => $item['totals']['owner_occupied_interest'],
                'Total Retirement Contribution' => $item['totals']['retirement_contribution'],
                'Total Chargeable Pay' => $item['totals']['chargeable_pay'],
                'Total Tax Charged' => $item['totals']['tax_charged'],
                'Total Personal Relief' => $item['totals']['personal_relief'],
                'Total Insurance Relief' => $item['totals']['insurance_relief'],
                'Total PAYE' => $item['totals']['paye'],
            ]);
        }, $this->data);
    }

    public function headings(): array
    {
        $headings = ['Employee Name', 'PIN'];
        for ($i = 1; $i <= 12; $i++) {
            $month = \DateTime::createFromFormat('!m', $i)->format('F');
            $headings = array_merge($headings, [
                "{$month} Basic Salary",
                "{$month} Benefits Non Cash",
                "{$month} Value of Quarters",
                "{$month} Total Gross Pay",
                "{$month} Retirement E1",
                "{$month} Retirement E2",
                "{$month} Retirement E3",
                "{$month} Owner Occupied Interest",
                "{$month} Retirement Contribution",
                "{$month} Chargeable Pay",
                "{$month} Tax Charged",
                "{$month} Personal Relief",
                "{$month} Insurance Relief",
                "{$month} PAYE",
            ]);
        }
        return array_merge($headings, [
            'Total Basic Salary',
            'Total Benefits Non Cash',
            'Total Value of Quarters',
            'Total Gross Pay',
            'Total Retirement E1',
            'Total Retirement E2',
            'Total Retirement E3',
            'Total Owner Occupied Interest',
            'Total Retirement Contribution',
            'Total Chargeable Pay',
            'Total Tax Charged',
            'Total Personal Relief',
            'Total Insurance Relief',
            'Total PAYE',
        ]);
    }
}
