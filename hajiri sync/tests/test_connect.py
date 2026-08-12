import unittest
from datetime import datetime
from unittest.mock import patch

import connect


class FakeLog:
    user_id = 101
    timestamp = datetime(2026, 7, 24, 7, 30, 0)


class SuccessfulDevice:
    def __init__(self, host, port, timeout):
        self.host = host

    def connect(self):
        return None

    def fetch_attendance_data(self, days):
        return [FakeLog()]

    def disconnect(self):
        return None


class FailingDevice(SuccessfulDevice):
    def connect(self):
        raise ConnectionError('device unavailable')


class SyncDevicesTest(unittest.TestCase):
    def setUp(self):
        self.config = {
            'endpoint': 'https://example.test/api/hajiri/attendance/sync',
            'token': 'test-token',
            'attendance_days': 90,
            'device_timeout': 10,
            'devices': {
                'device1': {
                    'host': '192.0.2.10',
                    'port': 4370,
                },
            },
        }

    @patch('connect.save_data')
    @patch('connect.send_data')
    @patch('connect.ZkConnect', SuccessfulDevice)
    def test_successful_sync_reports_progress_and_counts(self, send_data, save_data):
        send_data.return_value = {
            'success': True,
            'received': 1,
            'inserted': 1,
            'duplicates': 0,
        }
        events = []

        summary = connect.sync_devices(
            self.config,
            lambda percent, message, level: events.append((percent, message, level)),
        )

        self.assertEqual(1, summary['successful_devices'])
        self.assertEqual(0, summary['failed_devices'])
        self.assertEqual(1, summary['inserted'])
        self.assertEqual(100, events[-1][0])
        save_data.assert_called_once()
        send_data.assert_called_once()

    @patch('connect.ZkConnect', FailingDevice)
    def test_device_error_is_reported_without_crashing(self):
        events = []

        summary = connect.sync_devices(
            self.config,
            lambda percent, message, level: events.append((percent, message, level)),
        )

        self.assertEqual(0, summary['successful_devices'])
        self.assertEqual(1, summary['failed_devices'])
        self.assertTrue(any(level == 'error' for _, _, level in events))
        self.assertEqual(100, events[-1][0])


if __name__ == '__main__':
    unittest.main()
