import json
import logging
import os
import sys
from datetime import date, timedelta
from pathlib import Path
from typing import Callable, Dict, Optional

import requests
import yaml
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from zk import ZK
from zk.exception import ZKErrorConnection


ProgressCallback = Callable[[int, str, str], None]


def application_dir() -> Path:
    if getattr(sys, 'frozen', False):
        return Path(sys.executable).resolve().parent
    return Path(__file__).resolve().parent


APP_DIR = application_dir()
CONFIG_FILE = APP_DIR / 'config.yaml'

if sys.platform == 'win32' and os.getenv('LOCALAPPDATA'):
    DATA_DIR = Path(os.environ['LOCALAPPDATA']) / 'GodawariHajiriSync'
else:
    DATA_DIR = APP_DIR

DATA_DIR.mkdir(parents=True, exist_ok=True)
DATA_FILE = DATA_DIR / 'data.json'
LOG_FILE = DATA_DIR / 'hajiri-sync.log'


def configure_logging() -> None:
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(LOG_FILE, encoding='utf-8'),
            logging.StreamHandler(),
        ],
        force=True,
    )


def emit(callback: Optional[ProgressCallback], percent: int, message: str, level: str = 'info') -> None:
    getattr(logging, level, logging.info)(message)
    if callback:
        callback(max(0, min(100, percent)), message, level)


def load_config() -> dict:
    if not CONFIG_FILE.exists():
        raise FileNotFoundError(
            f'Configuration file not found: {CONFIG_FILE}. '
            'Place config.yaml beside HajiriSync.exe.'
        )

    with CONFIG_FILE.open('r', encoding='utf-8') as file:
        config = yaml.safe_load(file) or {}

    if not config.get('endpoint'):
        raise ValueError('The endpoint is missing from config.yaml.')
    if not config.get('token'):
        raise ValueError('The token is missing from config.yaml.')
    if not isinstance(config.get('devices'), dict) or not config['devices']:
        raise ValueError('At least one device is required in config.yaml.')

    return config


class ZkConnect:
    def __init__(self, host: str, port: int, timeout: int = 10):
        self.host = host
        self.port = int(port)
        self.timeout = int(timeout)
        self.connection = None

    def connect(self) -> None:
        zk = ZK(
            ip=self.host,
            port=self.port,
            timeout=self.timeout,
            force_udp=False,
            ommit_ping=False,
            verbose=False,
        )
        self.connection = zk.connect()

    def fetch_attendance_data(self, days: int = 90):
        if not self.connection:
            raise ZKErrorConnection('Connection is not established.')

        cutoff = date.today() - timedelta(days=days)
        return [
            log
            for log in self.connection.get_attendance()
            if log.timestamp and log.timestamp.date() >= cutoff
        ]

    def disconnect(self) -> None:
        if self.connection:
            try:
                self.connection.disconnect()
            finally:
                self.connection = None


def create_data(attendance_data) -> dict:
    machine_info = [
        {
            'indRegID': log.user_id,
            'dateTimeRecord': log.timestamp.strftime('%Y-%m-%d %H:%M:%S'),
        }
        for log in attendance_data
    ]
    return {'machineInfo': machine_info}


def save_data(data: dict) -> None:
    temporary_file = DATA_FILE.with_suffix('.json.tmp')
    with temporary_file.open('w', encoding='utf-8') as file:
        json.dump(data, file, ensure_ascii=False)
    temporary_file.replace(DATA_FILE)


def http_session() -> requests.Session:
    retry = Retry(
        total=3,
        connect=3,
        read=3,
        backoff_factor=1,
        status_forcelist=(429, 500, 502, 503, 504),
        allowed_methods=frozenset({'POST'}),
    )
    session = requests.Session()
    session.mount('https://', HTTPAdapter(max_retries=retry))
    session.mount('http://', HTTPAdapter(max_retries=retry))
    return session


def send_data(endpoint: str, token: str, data: dict) -> dict:
    headers = {
        'Accept': 'application/json',
        'Authorization': f'Bearer {token}',
    }

    try:
        with http_session() as session:
            response = session.post(
                endpoint,
                json=data,
                headers=headers,
                timeout=(10, 60),
            )
    except requests.RequestException as error:
        raise RuntimeError(f'Could not reach the Hajiri server: {error}') from error

    try:
        response_data = response.json()
    except ValueError as error:
        preview = response.text[:200].strip()
        raise RuntimeError(
            f'Server returned HTTP {response.status_code} instead of JSON. {preview}'
        ) from error

    if not response.ok or not response_data.get('success'):
        message = response_data.get('message', str(response_data))
        raise RuntimeError(f'Server returned HTTP {response.status_code}: {message}')

    return response_data


def sync_devices(
    config: dict,
    callback: Optional[ProgressCallback] = None,
) -> Dict[str, int]:
    devices = config['devices']
    endpoint = config['endpoint']
    token = config['token']
    days = int(config.get('attendance_days', 90))
    timeout = int(config.get('device_timeout', 10))
    total_steps = max(1, len(devices) * 4)
    completed_steps = 0
    summary = {
        'devices': len(devices),
        'successful_devices': 0,
        'failed_devices': 0,
        'received': 0,
        'inserted': 0,
        'duplicates': 0,
    }

    def advance(message: str, level: str = 'info') -> None:
        nonlocal completed_steps
        completed_steps += 1
        emit(callback, round(completed_steps / total_steps * 100), message, level)

    for device_name, device_info in devices.items():
        host = str(device_info.get('host', '')).strip()
        port = int(device_info.get('port', 4370))
        device = ZkConnect(host, port, timeout)

        try:
            if not host:
                raise ValueError(f'Device {device_name} has no host address.')

            emit(callback, round(completed_steps / total_steps * 100), f'[{device_name}] Connecting to {host}:{port}...')
            device.connect()
            advance(f'[{device_name}] Connected successfully.')

            emit(callback, round(completed_steps / total_steps * 100), f'[{device_name}] Reading attendance records...')
            attendance_data = device.fetch_attendance_data(days)
            advance(f'[{device_name}] Read {len(attendance_data)} records from the last {days} days.')

            data = create_data(attendance_data)
            save_data(data)
            advance(f'[{device_name}] Saved a local backup to {DATA_FILE}.')

            emit(callback, round(completed_steps / total_steps * 100), f'[{device_name}] Sending records to the server...')
            result = send_data(endpoint, token, data)
            summary['successful_devices'] += 1
            summary['received'] += int(result.get('received', 0))
            summary['inserted'] += int(result.get('inserted', 0))
            summary['duplicates'] += int(result.get('duplicates', 0))
            advance(
                f"[{device_name}] Synchronized: "
                f"received={result.get('received', 0)}, "
                f"inserted={result.get('inserted', 0)}, "
                f"duplicates={result.get('duplicates', 0)}."
            )
        except Exception as error:
            summary['failed_devices'] += 1
            remaining_device_steps = 4 - (completed_steps % 4)
            completed_steps += remaining_device_steps
            emit(
                callback,
                round(completed_steps / total_steps * 100),
                f'[{device_name}] Failed: {error}',
                'error',
            )
            logging.exception('Device synchronization failed for %s', device_name)
        finally:
            try:
                device.disconnect()
            except Exception as error:
                emit(
                    callback,
                    round(completed_steps / total_steps * 100),
                    f'[{device_name}] Warning: could not close the device connection: {error}',
                    'warning',
                )

    level = 'info' if summary['failed_devices'] == 0 else 'warning'
    emit(
        callback,
        100,
        'Synchronization finished: '
        f"{summary['successful_devices']} successful, "
        f"{summary['failed_devices']} failed, "
        f"{summary['inserted']} new records.",
        level,
    )
    return summary


def main() -> int:
    configure_logging()
    try:
        config = load_config()
        summary = sync_devices(config)
        return 0 if summary['failed_devices'] == 0 else 1
    except Exception as error:
        logging.exception('Hajiri synchronization could not start: %s', error)
        return 1


if __name__ == '__main__':
    raise SystemExit(main())
