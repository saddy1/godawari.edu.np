<?php

namespace Tests\Unit;

use App\Http\Controllers\Hajiri\DeviceController;
use App\Models\Hajiri\AttendanceLogs;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class HajiriDeviceSyncTest extends TestCase
{
    public function test_sync_rejects_an_invalid_token(): void
    {
        config(['services.hajiri_sync.token' => 'correct-token']);

        $request = $this->syncRequest('wrong-token', ['machineInfo' => []]);
        $controller = new DeviceController(Mockery::mock(AttendanceLogs::class));

        $response = $controller->sync_api($request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_sync_inserts_valid_attendance_and_reports_duplicates(): void
    {
        config(['services.hajiri_sync.token' => 'correct-token']);

        $payload = [
            'machineInfo' => [
                ['indRegID' => 101, 'dateTimeRecord' => '2026-07-24 07:30:00'],
                ['indRegID' => 101, 'dateTimeRecord' => '2026-07-24 07:30:00'],
                ['indRegID' => 102, 'dateTimeRecord' => '2026-07-24 07:31:00'],
            ],
        ];

        $query = Mockery::mock();
        $query->shouldReceive('insertOrIgnore')
            ->once()
            ->with(Mockery::on(fn (array $records) => count($records) === 2))
            ->andReturn(1);

        $attendanceLogs = Mockery::mock(AttendanceLogs::class);
        $attendanceLogs->shouldReceive('newQuery')->once()->andReturn($query);

        $controller = new DeviceController($attendanceLogs);
        $response = $controller->sync_api($this->syncRequest('correct-token', $payload));
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame(3, $data['received']);
        $this->assertSame(1, $data['inserted']);
        $this->assertSame(2, $data['duplicates']);
    }

    private function syncRequest(string $token, array $payload): Request
    {
        return Request::create(
            '/api/hajiri/attendance/sync',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
