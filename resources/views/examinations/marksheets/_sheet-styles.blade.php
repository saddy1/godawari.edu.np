* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; background: #e5e7eb; color: #111; }

.gs-sheet { position: relative; width: 100%; height: 100%; padding: 6mm 8mm; font-size: 9.5pt; }

.gs-header { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
.gs-header td { vertical-align: middle; }
.gs-logo { width: 24mm; text-align: center; }
.gs-logo img { max-width: 20mm; max-height: 20mm; }
.gs-title { text-align: center; }
.gs-title h1 { font-size: 1.5em; font-weight: 700; margin: 0; }
.gs-address { font-size: .85em; color: #444; margin: 1px 0; }
.gs-doc-title { font-size: 1.15em; font-weight: 700; text-decoration: underline; margin-top: 2mm; letter-spacing: .05em; }

.gs-info { width: 100%; margin: 3mm 0; font-size: .95em; line-height: 1.7; }
.gs-info .row { display: flex; justify-content: space-between; gap: 6mm; }
.gs-info b { font-weight: 700; }
.gs-dotted { display: inline-block; border-bottom: 1px dotted #333; min-width: 40mm; padding: 0 1mm; }

.gs-table { width: 100%; border-collapse: collapse; margin-top: 3mm; }
.gs-table th, .gs-table td { border: 0.75pt solid #333; padding: 1.3mm 2mm; font-size: .88em; text-align: left; }
.gs-table th { font-weight: 700; background: #f3f4f6; text-align: center; }
.gs-table td.num { text-align: center; }

.gs-gpa { margin-top: 2mm; text-align: right; font-weight: 700; font-size: 1em; }

.gs-extra { margin-top: 3mm; }
.gs-extra p { font-size: .85em; font-weight: 700; margin-bottom: 1mm; }
.gs-extra-box { border: 0.75pt solid #333; min-height: 8mm; width: 100%; border-collapse: collapse; }
.gs-extra-box td { border: 0.75pt solid #333; padding: 1.3mm 2mm; font-size: .85em; }

.gs-footer { width: 100%; margin-top: 5mm; font-size: .88em; }
.gs-footer .left { line-height: 2; }
.gs-sign-block { position: absolute; right: 8mm; bottom: 22mm; text-align: center; }
.gs-sign-img { display: block; max-height: 9mm; max-width: 32mm; margin: 0 auto 1mm; }
.gs-sign-line { display: block; border-top: 0.75pt solid #333; width: 34mm; margin: 0 auto 1mm; height: 6mm; }

.gs-note { position: absolute; left: 8mm; right: 8mm; bottom: 6mm; font-size: .72em; color: #333; line-height: 1.5; }
.gs-note b { font-weight: 700; }

/* A4 grade-sheet layout matching the school's printed form. */
.gs-sheet { width: 190mm; height: 277mm; margin: 10mm; padding: 10mm 7mm; border: 1.2pt solid #222; background: #fff; font-size: 9pt; }
.gs-header { margin-bottom: 7mm; }
.gs-title h1 { text-transform: uppercase; font-size: 15pt; }
.gs-doc-title { text-decoration: none; margin-top: 8mm; font-size: 11pt; }
.gs-info { border-collapse: collapse; font-size: 8.5pt; line-height: 1.5; }
.gs-info td { padding: 1.5mm 0; vertical-align: top; }
.gs-info b { border-bottom: 1px dotted #777; }
.gs-table { table-layout: fixed; }
.gs-table th { background: white; font-size: 8pt; }
.gs-table th:nth-child(1) { width: 12%; }
.gs-table th:nth-child(2) { width: 48%; }
.gs-table th:nth-child(3), .gs-table th:nth-child(4), .gs-table th:nth-child(5), .gs-table th:nth-child(6) { width: 10%; }
.gs-table tbody td { border-top: 0; border-bottom: 0; padding: 1.6mm 1.5mm; }
.gs-table tfoot td.gs-gpa { text-align: right; font-weight: bold; padding: 2mm; border: .75pt solid #333; }
.gs-extra { margin-top: 6mm; }
.gs-extra-box td { height: 9mm; }
.gs-footer { margin-top: 12mm; }
.gs-footer .left { line-height: 3; }
.gs-sign-block { right: 10mm; bottom: 82mm; font-size: 8pt; }
.gs-note { left: 0; right: 0; bottom: 0; padding: 6mm 7mm; min-height: 39mm; border-top: .75pt solid #333; font-size: 7.5pt; line-height: 1.5; }
.gs-sheet { box-sizing: content-box; width: 174mm; height: 254mm; }
.page-sheet { padding: 10mm; box-sizing: border-box; overflow: hidden; }
.gs-sheet { margin: 0; height: 252mm; }
