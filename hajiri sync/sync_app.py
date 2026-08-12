import logging
import queue
import threading
import tkinter as tk
from datetime import datetime
from tkinter import messagebox, scrolledtext, ttk

from connect import CONFIG_FILE, DATA_DIR, LOG_FILE, configure_logging, load_config, sync_devices


class HajiriSyncApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('Godawari Hajiri Sync')
        self.geometry('760x520')
        self.minsize(650, 430)
        self.protocol('WM_DELETE_WINDOW', self.close_window)

        self.events = queue.Queue()
        self.running = False
        self._build_ui()
        self.after(100, self.process_events)

    def _build_ui(self):
        container = ttk.Frame(self, padding=18)
        container.pack(fill='both', expand=True)

        ttk.Label(
            container,
            text='Godawari Hajiri Sync',
            font=('Segoe UI', 18, 'bold'),
        ).pack(anchor='w')
        ttk.Label(
            container,
            text='Read biometric attendance and send it securely to the ERP server.',
        ).pack(anchor='w', pady=(2, 16))

        self.status = tk.StringVar(value='Ready to synchronize.')
        ttk.Label(container, textvariable=self.status).pack(anchor='w')

        self.progress = ttk.Progressbar(
            container,
            orient='horizontal',
            mode='determinate',
            maximum=100,
        )
        self.progress.pack(fill='x', pady=(7, 14))

        self.log_view = scrolledtext.ScrolledText(
            container,
            height=18,
            state='disabled',
            wrap='word',
            font=('Consolas', 10),
        )
        self.log_view.pack(fill='both', expand=True)
        self.log_view.tag_configure('error', foreground='#b42318')
        self.log_view.tag_configure('warning', foreground='#a15c00')
        self.log_view.tag_configure('info', foreground='#1f2937')

        footer = ttk.Frame(container)
        footer.pack(fill='x', pady=(14, 0))
        ttk.Label(footer, text=f'Logs: {LOG_FILE}').pack(side='left')

        self.sync_button = ttk.Button(
            footer,
            text='Sync Attendance',
            command=self.start_sync,
        )
        self.sync_button.pack(side='right')

    def append_log(self, message, level='info'):
        timestamp = datetime.now().strftime('%H:%M:%S')
        self.log_view.configure(state='normal')
        self.log_view.insert('end', f'{timestamp}  {message}\n', level)
        self.log_view.see('end')
        self.log_view.configure(state='disabled')

    def start_sync(self):
        if self.running:
            return

        self.running = True
        self.progress['value'] = 0
        self.status.set('Starting synchronization...')
        self.sync_button.configure(state='disabled')
        self.append_log(f'Using configuration: {CONFIG_FILE}')
        threading.Thread(target=self.run_sync, daemon=True).start()

    def run_sync(self):
        try:
            config = load_config()
            summary = sync_devices(config, self.queue_progress)
            self.events.put(('done', summary))
        except Exception as error:
            logging.exception('Synchronization could not start')
            self.events.put(('fatal', str(error)))

    def queue_progress(self, percent, message, level):
        self.events.put(('progress', percent, message, level))

    def process_events(self):
        try:
            while True:
                event = self.events.get_nowait()
                event_type = event[0]

                if event_type == 'progress':
                    _, percent, message, level = event
                    self.progress['value'] = percent
                    self.status.set(message)
                    self.append_log(message, level)
                elif event_type == 'done':
                    self.finish_sync(event[1])
                elif event_type == 'fatal':
                    self.finish_with_error(event[1])
        except queue.Empty:
            pass
        self.after(100, self.process_events)

    def finish_sync(self, summary):
        self.running = False
        self.sync_button.configure(state='normal')
        self.progress['value'] = 100

        if summary['failed_devices']:
            message = (
                f"Completed with {summary['failed_devices']} failed device(s).\n\n"
                f"New records: {summary['inserted']}\n"
                f"See the progress window or log file for details."
            )
            self.status.set('Synchronization completed with errors.')
            messagebox.showwarning('Hajiri Sync', message)
        else:
            message = (
                'Synchronization completed successfully.\n\n'
                f"New records: {summary['inserted']}\n"
                f"Existing records: {summary['duplicates']}"
            )
            self.status.set('Synchronization completed successfully.')
            messagebox.showinfo('Hajiri Sync', message)

    def finish_with_error(self, error):
        self.running = False
        self.sync_button.configure(state='normal')
        self.status.set('Synchronization could not start.')
        self.append_log(error, 'error')
        messagebox.showerror(
            'Hajiri Sync Error',
            f'{error}\n\nApplication data folder:\n{DATA_DIR}',
        )

    def close_window(self):
        if self.running and not messagebox.askyesno(
            'Exit Hajiri Sync',
            'Synchronization is still running. Do you want to close the application?',
        ):
            return
        self.destroy()


if __name__ == '__main__':
    configure_logging()
    app = HajiriSyncApp()
    app.mainloop()
