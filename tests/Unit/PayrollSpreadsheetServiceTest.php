<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PayrollSpreadsheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PayrollSpreadsheetServiceTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();
        });
        User::create(['name' => 'Sushma Rai', 'email' => 'sushma@example.test', 'password' => 'password', 'device_id' => '104']);
        $this->file = tempnam(sys_get_temp_dir(), 'payroll-test-');
    }

    protected function tearDown(): void
    {
        if (isset($this->file) && file_exists($this->file)) {
            unlink($this->file);
        }
        parent::tearDown();
    }

    public function test_it_reads_the_supplied_salary_headings_and_matches_hajiri_staff_id(): void
    {
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['SUSHMA + GODAWARI'],
            ['SALARY FOR THE MONTH OF Shrawan-2083'],
            ['S.N.', 'Staff ID', 'NAME:', 'Bank Code', 'Account no:', 'PAN no:', 'Position', 'previous Dues+ Extra', 'Current Salary', 'meeting allowance + Extra allowances', 'no. of abs. day', 'abs. amount', 'taxable income', 'advance', 'Providend fund', 'salary before tax', 'tax@ 1%', 'net amount (salary after tax)'],
            [1, 104, 'Sushma Rai', 'PRABHU', '12345', '9876', 'Teacher', 500, 30000, 1000, 1, 1000, 30000, 0, 3000, 27500, 275, 27225],
        ]);
        (new Xlsx($sheet))->save($this->file);

        $result = app(PayrollSpreadsheetService::class)->parse($this->file);

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('104', $result['rows'][0]['staff_id']);
        $this->assertSame('Sushma Rai', $result['rows'][0]['matched_name']);
        $this->assertSame(27225.0, $result['rows'][0]['net_amount']);
    }
}
