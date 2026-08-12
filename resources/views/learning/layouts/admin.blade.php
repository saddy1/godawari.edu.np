<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'E-Learning') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- KaTeX for LaTeX math rendering --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"></script>
    <script>
        var _katexOpts = {
            delimiters: [
                { left: '$$', right: '$$', display: true  },
                { left: '$',  right: '$',  display: false },
                { left: '\\(', right: '\\)', display: false },
                { left: '\\[', right: '\\]', display: true  }
            ],
            throwOnError: false
        };
        function sanitizeLessonHtml(container) {
            container.querySelectorAll('script, style, object, embed').forEach(function (node) {
                node.remove();
            });
            container.querySelectorAll('*').forEach(function (node) {
                Array.from(node.attributes).forEach(function (attr) {
                    var name = attr.name.toLowerCase();
                    var value = attr.value.trim().toLowerCase();
                    if (name.indexOf('on') === 0 || ((name === 'href' || name === 'src') && value.indexOf('javascript:') === 0)) {
                        node.removeAttribute(attr.name);
                    }
                });
            });
        }
        function renderTrustedHtmlBlocks(root) {
            root.querySelectorAll('.lesson-body pre').forEach(function (pre) {
                var html = pre.textContent.trim();
                if (!/^<(div|section|article|figure)\b[^>]*class=["'][^"']*\blesson-/i.test(html)) return;

                var template = document.createElement('template');
                template.innerHTML = html;
                sanitizeLessonHtml(template.content);
                pre.replaceWith(template.content.cloneNode(true));
            });
        }
        function normalizeLessonMarkdown(root) {
            root.querySelectorAll('.lesson-body').forEach(function (body) {
                Array.from(body.children).forEach(function (node) {
                    if ((node.tagName === 'P' || node.tagName === 'DIV') && !node.textContent.trim()) {
                        node.remove();
                    }
                });

                Array.from(body.querySelectorAll('p')).forEach(function (p) {
                    var text = p.textContent.trim();
                    var heading = text.match(/^(#{1,4})\s+(.+)$/);
                    var boldLine = text.match(/^\*\*(.+)\*\*$/);
                    var replacement = null;

                    if (heading) {
                        replacement = document.createElement('h' + Math.min(heading[1].length, 4));
                        replacement.textContent = heading[2];
                    } else if (boldLine) {
                        replacement = document.createElement('h3');
                        replacement.textContent = boldLine[1];
                    }

                    if (replacement) {
                        p.replaceWith(replacement);
                    }
                });

                var nodes = Array.from(body.children);
                for (var i = 0; i < nodes.length; i++) {
                    if (!nodes[i] || nodes[i].tagName !== 'P') continue;

                    var first = nodes[i].textContent.trim().match(/^([a-z])[\).]\s+(.+)$/i);
                    if (!first) continue;

                    var ol = document.createElement('ol');
                    ol.type = 'a';

                    for (var j = i; j < nodes.length; j++) {
                        if (!nodes[j] || nodes[j].tagName !== 'P') break;
                        var item = nodes[j].textContent.trim().match(/^([a-z])[\).]\s+(.+)$/i);
                        if (!item) break;

                        var li = document.createElement('li');
                        li.textContent = item[2];
                        ol.appendChild(li);
                    }

                    nodes[i].replaceWith(ol);
                    for (var k = i + 1; k < j; k++) {
                        nodes[k].remove();
                    }
                    nodes = Array.from(body.children);
                }
            });
        }
        function normalizeQuillDisplayMath(root) {
            root.querySelectorAll('.lesson-body').forEach(function (body) {
                var nodes = Array.from(body.children);
                for (var i = 0; i < nodes.length; i++) {
                    var start = nodes[i];
                    if (!start || start.dataset.mathNormalized === '1' || start.textContent.trim() !== '$$') continue;

                    var parts = [];
                    var end = null;
                    for (var j = i + 1; j < nodes.length; j++) {
                        if (nodes[j].textContent.trim() === '$$') {
                            end = nodes[j];
                            break;
                        }
                        parts.push(nodes[j].textContent);
                    }
                    if (!end || parts.length === 0) continue;

                    var math = document.createElement('div');
                    math.className = 'lesson-display-math';
                    math.textContent = '$$' + parts.join('\n') + '$$';
                    start.replaceWith(math);
                    parts.forEach(function (_, offset) {
                        nodes[i + 1 + offset].remove();
                    });
                    end.remove();
                    nodes = Array.from(body.children);
                    i--;
                }
            });
        }
        // DOMContentLoaded fires after all defer scripts — renderMathInElement is available by then
        document.addEventListener('DOMContentLoaded', function () {
            renderTrustedHtmlBlocks(document);
            normalizeLessonMarkdown(document);
            normalizeQuillDisplayMath(document);
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(document.body, _katexOpts);
            }
        });
        // Re-render a specific element.
        function katexRender(el) {
            renderTrustedHtmlBlocks(el);
            normalizeLessonMarkdown(el);
            normalizeQuillDisplayMath(el);
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(el, _katexOpts);
            } else {
                window.setTimeout(function () {
                    katexRender(el);
                }, 150);
            }
        }
    </script>

    @php
        $primary      = $siteSettings->get('primary_color', '#1a5632');
        $primaryLight = $siteSettings->get('primary_light_color', '#237042');
        $secondary    = $siteSettings->get('secondary_color', '#e2a024');
        $dark         = $siteSettings->get('dark_color', '#0b2415');
        $sidebarEnd   = $siteSettings->get('sidebar_gradient_end', '#050f09');
    @endphp
    <style>
        :root {
            --theme-primary: {{ $primary }};
            --theme-primary-light: {{ $primaryLight }};
            --theme-secondary: {{ $secondary }};
            --theme-dark: {{ $dark }};
            --theme-sidebar-bg: {{ $dark }};
            --theme-sidebar-gradient-end: {{ $sidebarEnd }};
        }
        .bg-\[\#1a5632\] { background-color: var(--theme-primary) !important; }
        .text-\[\#1a5632\] { color: var(--theme-primary) !important; }
        .border-\[\#1a5632\] { border-color: var(--theme-primary) !important; }
        .ring-\[\#1a5632\] { --tw-ring-color: var(--theme-primary) !important; }
        .focus\:ring-\[\#1a5632\]\/15:focus { --tw-ring-color: color-mix(in srgb, var(--theme-primary) 15%, transparent) !important; }
        .bg-\[\#0b2415\] { background-color: var(--theme-dark) !important; }
        .hover\:bg-\[\#0b2415\]:hover { background-color: var(--theme-dark) !important; }
        .hover\:bg-\[\#1a5632\]:hover { background-color: var(--theme-primary) !important; }
        .from-\[\#0b2415\] { --tw-gradient-from: var(--theme-dark) !important; }
        .to-\[\#1a5632\] { --tw-gradient-to: var(--theme-primary) !important; }
        .focus\:border-\[\#1a5632\]:focus { border-color: var(--theme-primary) !important; }
        .focus\:ring-\[\#1a5632\]\/10:focus { --tw-ring-color: color-mix(in srgb, var(--theme-primary) 10%, transparent) !important; }
        main { min-width: 0; }
        .lesson-body table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .95em; }
        .lesson-body th, .lesson-body td { border: 1px solid #e5e7eb; padding: .65rem .75rem; text-align: left; vertical-align: top; }
        .lesson-body th { background: #f9fafb; color: #111827; font-weight: 800; }
        .lesson-callout { border-left: 4px solid var(--theme-primary); background: #f8fafc; border-radius: 0 .75rem .75rem 0; padding: .85rem 1rem; margin: 1rem 0; }
        .lesson-callout.warning { border-left-color: #d97706; background: #fffbeb; }
        .lesson-callout.success { border-left-color: #059669; background: #ecfdf5; }
        .lesson-figure { margin: 1.25rem 0; text-align: center; }
        .lesson-figure img, .lesson-figure svg { max-width: 100%; height: auto; display: inline-block; }
        .lesson-figure figcaption { margin-top: .5rem; color: #6b7280; font-size: .875rem; }
        .lesson-media-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; margin: 1rem 0; }
        .lesson-body ol[type="a"] { list-style-type: lower-alpha; }
        .lesson-body ol[type="A"] { list-style-type: upper-alpha; }
        .lesson-display-math {
            margin: .85rem 0;
            overflow-x: auto;
            overflow-y: hidden;
            padding: .25rem 0;
        }
        .lesson-body .katex-display {
            margin: .65rem 0;
        }
        .lesson-venn-layout {
            display: flow-root;
            margin: 1.25rem 0;
        }
        .lesson-venn-layout ol {
            list-style-type: lower-alpha;
            padding-left: 1.5rem;
            margin: 0;
        }
        .lesson-venn-layout li {
            padding-left: .25rem;
        }
        .lesson-venn-layout figure {
            float: right;
            width: min(42%, 30rem);
            min-width: 22rem;
            margin: .1rem 0 1rem 1.75rem;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            background: #fff;
            padding: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .lesson-venn-layout svg {
            max-width: 100%;
            height: auto;
            display: block;
        }
        @media (max-width: 860px) {
            .lesson-venn-layout figure {
                float: none;
                width: 100%;
                min-width: 0;
                max-width: 30rem;
                margin: 0 0 1rem;
            }
        }
    </style>
    {{-- Quill rich text editor --}}
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="flex h-dvh overflow-hidden">
        @include('learning.partials.sidebar')

        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            @include('backend.partials.module-header')

            <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                @if(session('success'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
                @endif
            </div>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <footer class="shrink-0 px-6 py-3 border-t border-gray-100 bg-white">
                <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', 'School') }} — E-Learning</p>
            </footer>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
    var _quills = {};
    var _previewTimers = {};
    function previewIdForInput(inputId) {
        if (inputId.indexOf('qi-edit-') === 0) return inputId.replace('qi-edit-', 'prev-edit-');
        if (inputId.indexOf('qi-new-') === 0) return inputId.replace('qi-new-', 'prev-new-');
        return null;
    }
    function sourceIdForInput(inputId) {
        if (inputId.indexOf('qi-edit-') === 0) return inputId.replace('qi-edit-', 'qs-edit-');
        if (inputId.indexOf('qi-new-') === 0) return inputId.replace('qi-new-', 'qs-new-');
        return null;
    }
    function escapeLessonHtml(value) {
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    function inlineLessonMarkdown(value) {
        return escapeLessonHtml(value).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    }
    function htmlToPlainLessonSource(html) {
        var template = document.createElement('template');
        template.innerHTML = html;
        return Array.from(template.content.childNodes).map(function (node) {
            if (node.nodeType === Node.TEXT_NODE) return node.textContent;
            if (node.nodeType !== Node.ELEMENT_NODE) return '';
            if (node.matches('pre')) return node.textContent;
            if (node.matches('br')) return '';
            return node.textContent;
        }).join('\n');
    }
    function sourceToLessonHtml(source) {
        var text = (source || '').replace(/\r\n?/g, '\n').trim();
        if (!text) return '';
        text = text.replace(/(<\/(?:div|section|article|figure)>)(?=\s*(?:#{1,4}\s+|\*\*|\$\$|[a-z][\).]\s+))/gi, '$1\n');
        if (/^<[\s\S]+>$/.test(text)) {
            if (/(^|>)\s*(#{1,4}\s+|\*\*|\$\$|[a-z][\).]\s+)/im.test(text)) {
                text = htmlToPlainLessonSource(text).replace(/\r\n?/g, '\n').trim();
            } else {
                return text;
            }
        }

        var lines = text.split('\n');
        var html = [];
        var listItems = [];

        function flushList() {
            if (!listItems.length) return;
            html.push('<ol type="a">' + listItems.map(function (item) {
                return '<li>' + inlineLessonMarkdown(item) + '</li>';
            }).join('') + '</ol>');
            listItems = [];
        }

        function isLessonComponentStart(line) {
            return /^<(div|section|article|figure)\b[^>]*class=["'][^"']*\blesson-/i.test(line);
        }
        function closingTagFor(line) {
            var match = line.match(/^<([a-z0-9-]+)/i);
            return match ? '</' + match[1].toLowerCase() + '>' : null;
        }

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (!line) {
                flushList();
                continue;
            }

            if (isLessonComponentStart(line)) {
                flushList();
                var block = [lines[i]];
                var closingTag = closingTagFor(line);
                var closed = closingTag && line.toLowerCase().indexOf(closingTag) !== -1;

                while (!closed && i + 1 < lines.length) {
                    i++;
                    block.push(lines[i]);
                    closed = closingTag && lines[i].toLowerCase().indexOf(closingTag) !== -1;
                }

                html.push(block.join('\n'));
                continue;
            }

            if (line === '$$') {
                flushList();
                var mathLines = [];
                i++;
                while (i < lines.length && lines[i].trim() !== '$$') {
                    mathLines.push(lines[i].trim());
                    i++;
                }
                html.push('<div class="lesson-display-math">$$\n' + escapeLessonHtml(mathLines.join('\n')) + '\n$$</div>');
                continue;
            }

            var heading = line.match(/^(#{1,4})\s+(.+)$/);
            if (heading) {
                flushList();
                html.push('<h' + Math.min(heading[1].length, 4) + '>' + inlineLessonMarkdown(heading[2]) + '</h' + Math.min(heading[1].length, 4) + '>');
                continue;
            }

            var boldLine = line.match(/^\*\*(.+)\*\*$/);
            if (boldLine) {
                flushList();
                html.push('<h3>' + inlineLessonMarkdown(boldLine[1]) + '</h3>');
                continue;
            }

            var alphaItem = line.match(/^[a-z][\).]\s+(.+)$/i);
            if (alphaItem) {
                listItems.push(alphaItem[1]);
                continue;
            }

            flushList();
            html.push('<p>' + inlineLessonMarkdown(line) + '</p>');
        }

        flushList();
        return html.join('\n');
    }
    function renderLessonPreview(inputId) {
        var inp = document.getElementById(inputId);
        var previewId = previewIdForInput(inputId);
        var prev = previewId ? document.getElementById(previewId) : null;
        if (!inp || !prev) return;

        window.clearTimeout(_previewTimers[inputId]);
        _previewTimers[inputId] = window.setTimeout(function () {
            prev.innerHTML = inp.value && inp.value.trim()
                ? sourceToLessonHtml(inp.value)
                : '<p class="text-gray-400">Start writing to preview the lesson here.</p>';
            if (inp.value && inp.value.trim() && typeof katexRender === 'function') {
                katexRender(prev);
            }
        }, 120);
    }
    function initQE(editorId, inputId) {
        if (_quills[editorId]) return;
        var el = document.getElementById(editorId);
        var inp = document.getElementById(inputId);
        var sourceId = sourceIdForInput(inputId);
        var source = sourceId ? document.getElementById(sourceId) : null;
        if (!el || !inp) return;
        var q = new Quill(el, {
            theme: 'snow',
            modules: { toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ align: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]}
        });
        if (inp.value && inp.value.trim()) q.clipboard.dangerouslyPasteHTML(inp.value);
        if (source) {
            source.value = inp.value || '';
            source.addEventListener('input', function () {
                inp.value = source.value;
                renderLessonPreview(inputId);
            });
        }
        renderLessonPreview(inputId);
        q.on('text-change', function () {
            inp.value = q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML;
            if (source) source.value = inp.value;
            renderLessonPreview(inputId);
        });
        var form = el.closest('form');
        if (form) form.addEventListener('submit', function () {
            inp.value = source ? sourceToLessonHtml(source.value) : (q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML);
        });
        _quills[editorId] = q;
    }
    </script>
    @include('partials.page-wheel-scroll')
    @stack('scripts')
</body>
</html>
