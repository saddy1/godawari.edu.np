* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; background: #e5e7eb; color: #111; }

.slot { overflow: hidden; border-bottom: 1px dashed #9ca3af; padding-bottom: 4mm; margin-bottom: 4mm; }
.slot:last-child { border-bottom: none; margin-bottom: 0; }

.admit-card { border: 1pt solid #222; position: relative; width: 100%; height: 100%; padding: 3mm 4mm; font-size: 8.5pt; }

.ac-header { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
.ac-header td { vertical-align: middle; }
.ac-logo { width: 20mm; text-align: center; }
.ac-logo img { max-width: 18mm; max-height: 18mm; }
.ac-title { text-align: center; }
.ac-title h1 { font-size: 1.55em; font-weight: 700; letter-spacing: .02em; margin: 0; }
.ac-address { font-size: .85em; color: #444; margin: 1px 0; }
.ac-exam-name { font-size: 1.05em; font-weight: 700; margin-top: 2mm; }
.ac-admit { font-size: .95em; font-weight: 700; text-decoration: underline; margin-top: 1mm; }

.ac-meta { width: 100%; border-collapse: collapse; margin: 2mm 0; }
.ac-meta td { padding: 1mm 2mm 1mm 0; font-size: .95em; }
.ac-label { font-weight: 700; white-space: nowrap; width: 1%; }
.ac-value { white-space: nowrap; }

.ac-subjects { width: 100%; border-collapse: collapse; margin-top: 2mm; }
.ac-subjects th, .ac-subjects td { border: 0.75pt solid #333; padding: 1mm 2mm; font-size: .85em; text-align: left; }
.ac-subjects th { font-weight: 700; background: #f3f4f6; }
.ac-subject-name { font-weight: 600; }
.ac-empty { text-align: center; color: #888; }

.ac-footer { width: 100%; border-collapse: collapse; margin-top: 16mm; }
.ac-footer td { text-align: center; font-size: .8em; font-weight: 600; padding-top: 1mm; width: 33%; vertical-align: bottom; position: relative; }
.ac-sign-line { display: block; border-top: 0.75pt solid #333; width: 80%; margin: 0 auto 5mm; height: 0; }
.ac-sign-img { position: absolute; bottom: 5mm; left: 25%; max-height: 15mm; max-width: 50%; }

.ac-photo { position: absolute; top: 3mm; right: 4mm; width: 20mm; height: 24mm; border: 0.75pt solid #333; background: #fff; overflow: hidden; }
.ac-photo img { width: 100%; height: 100%; object-fit: cover; }

