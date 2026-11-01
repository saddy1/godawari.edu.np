<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Action needed</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f6f8f7; color: #1f2937; font: 16px/1.55 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        section { width: min(760px, 100%); overflow: hidden; background: #fff; border: 1px solid #dce5df; border-radius: 18px; box-shadow: 0 18px 45px rgba(20, 53, 34, .09); }
        header { padding: 28px 32px; color: #fff; background: #14532d; }
        header p { margin: 0 0 6px; font-size: 12px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: #bbf7d0; }
        h1 { margin: 0; font-size: 26px; line-height: 1.2; }
        .content { padding: 28px 32px 32px; }
        .intro { margin: 0 0 22px; color: #4b5563; }
        .error { margin: 0; padding: 16px; overflow-wrap: anywhere; border: 1px solid #fecaca; border-radius: 10px; background: #fef2f2; color: #991b1b; font: 14px/1.55 ui-monospace, SFMono-Regular, Menlo, monospace; }
        dl { display: grid; grid-template-columns: 110px 1fr; gap: 7px 16px; margin: 22px 0; font-size: 14px; }
        dt { font-weight: 700; color: #6b7280; }
        dd { margin: 0; overflow-wrap: anywhere; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }
        a { display: inline-block; padding: 10px 15px; border: 1px solid #cbd5d1; border-radius: 8px; color: #14532d; font-weight: 700; text-decoration: none; }
        a.primary { border-color: #14532d; background: #14532d; color: #fff; }
    </style>
</head>
<body>
    <main>
        <section role="alert">
            <header>
                <p>Administrator diagnostic · HTTP 500</p>
                <h1>This page needs attention</h1>
            </header>
            <div class="content">
                <p class="intro">The error details below are visible only to signed-in administrators. They are also recorded in the application log.</p>
                <p class="error">{{ $message }}</p>
                <dl>
                    <dt>Exception</dt><dd>{{ $exception }}</dd>
                    <dt>Location</dt><dd>{{ $file }}:{{ $line }}</dd>
                </dl>
                <div class="actions">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}">Go back</a>
                    <a class="primary" href="{{ route('admin.dashboard') }}">Open dashboard</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
