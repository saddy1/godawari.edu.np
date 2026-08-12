# Godawari Hajiri Sync desktop application

## Build the Windows EXE

Windows must build the Windows executable; PyInstaller does not cross-compile
a Windows EXE from macOS.

1. Install 64-bit Python 3.11 or 3.12 from python.org. Enable the installer
   option **Add Python to PATH**.
2. Copy the complete `hajiri sync` folder to the Windows computer.
3. Confirm that `config.yaml` contains the correct endpoint, token, and device.
4. Double-click `build_windows.bat`.
5. Find the completed application in `dist\HajiriSync.exe`.

Keep `config.yaml` beside `HajiriSync.exe`. The application stores its local
backup and diagnostic log in:

```text
%LOCALAPPDATA%\GodawariHajiriSync
```

## Use the application

1. Ensure the computer is connected to the biometric device's network.
2. Open `HajiriSync.exe`.
3. Select **Sync Attendance**.
4. Follow each connection, reading, backup, and server-upload step in the
   progress window.

The application retries temporary server/network failures three times. Existing
attendance records are safely treated as duplicates by the server.
