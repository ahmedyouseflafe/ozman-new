<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>طباعة بطاقات السحب {{ $fromNumber }} - {{ $toNumber }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #1d1d1d;
            color: #111;
            font-family: Arial, Tahoma, sans-serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: #050505;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .toolbar strong { color: #00e5ff; }
        .toolbar button {
            border: 0;
            border-radius: 999px;
            min-height: 42px;
            padding: 0 18px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            background: linear-gradient(135deg, #00e5ff, #7000ff);
            color: #001014;
        }
        .print-root {
            display: grid;
            gap: 12mm;
            padding: 12mm 0;
        }
        .sheet {
            width: 210mm;
            min-height: 296mm;
            margin: 0 auto;
            padding: 8mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
            align-content: start;
            break-after: page;
            box-shadow: 0 20px 70px rgba(0,0,0,.45);
        }
        .sheet.cards-6 { grid-template-rows: repeat(3, 1fr); }
        .sheet.cards-8 { grid-template-rows: repeat(4, 1fr); }
        .sheet.cards-10 { grid-template-rows: repeat(5, 1fr); gap: 4mm; }
        .sheet.cards-24 {
            width: 210mm;
            min-height: 296mm;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-template-rows: repeat(8, minmax(0, 1fr));
            gap: 1.5mm;
            padding: 5mm;
        }
        .ticket {
            position: relative;
            display: grid;
            grid-template-columns: 23mm 22mm minmax(0, 1fr);
            align-items: center;
            gap: 2mm;
            min-height: 48mm;
            padding: 3.2mm;
            border: .35mm solid #d9d9d9;
            background: #fff;
            overflow: hidden;
            break-inside: avoid;
        }
        .sheet.cards-10 .ticket {
            min-height: 49mm;
            grid-template-columns: 21mm 20mm minmax(0, 1fr);
            gap: 1.6mm;
            padding: 2.8mm;
        }
        .sheet.cards-24 .ticket {
            min-height: 0;
            grid-template-columns: 13mm 16mm minmax(0, 1fr);
            grid-template-rows: 1fr;
            gap: 1mm;
            padding: 1.2mm;
            border-width: .25mm;
        }
        .cut-mark {
            position: absolute;
            width: 8mm;
            height: 8mm;
            opacity: .65;
        }
        .cut-mark::before,
        .cut-mark::after {
            content: '';
            position: absolute;
            background: #444;
        }
        .cut-mark::before { width: 8mm; height: .25mm; top: 0; left: 0; }
        .cut-mark::after { width: .25mm; height: 8mm; top: 0; left: 0; }
        .cut-tr { top: 1mm; right: 1mm; transform: rotate(90deg); }
        .cut-br { bottom: 1mm; right: 1mm; transform: rotate(180deg); }
        .cut-bl { bottom: 1mm; left: 1mm; transform: rotate(270deg); }
        .social-stack {
            display: grid;
            gap: 2.4mm;
            justify-items: center;
            min-width: 0;
        }
        .social-qr {
            width: 21mm;
            height: 21mm;
            display: grid;
            place-items: center;
        }
        .sheet.cards-10 .social-qr {
            width: 19mm;
            height: 19mm;
        }
        .sheet.cards-24 .social-stack {
            grid-row: auto;
            grid-template-columns: 1fr;
            gap: .8mm;
        }
        .sheet.cards-24 .social-qr {
            width: 10.5mm;
            height: 10.5mm;
        }
        .social-qr img,
        .main-qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .social-placeholder {
            width: 100%;
            height: 100%;
            border: .35mm dashed #999;
            display: grid;
            place-items: center;
            color: #777;
            font-size: 7pt;
            text-align: center;
            line-height: 1.3;
        }
        .follow-copy {
            display: grid;
            gap: 5mm;
            align-content: center;
            text-align: center;
            font-weight: 800;
            font-size: 12pt;
            line-height: 1.45;
            min-width: 0;
        }
        .sheet.cards-10 .follow-copy { font-size: 10pt; gap: 4mm; }
        .sheet.cards-24 .follow-copy {
            grid-row: auto;
            grid-template-columns: 1fr;
            font-size: 5pt;
            line-height: 1.15;
            gap: 1mm;
        }
        .main-side {
            display: grid;
            justify-items: center;
            align-content: center;
            gap: 1.3mm;
            text-align: center;
            min-width: 0;
            overflow: hidden;
        }
        .main-qr {
            position: relative;
            width: 31mm;
            height: 31mm;
        }
        .sheet.cards-10 .main-qr {
            width: 28mm;
            height: 28mm;
        }
        .sheet.cards-24 .main-side {
            grid-row: auto;
            gap: .35mm;
        }
        .sheet.cards-24 .main-qr {
            width: 15mm;
            height: 15mm;
        }
        .sheet.cards-24 .brand-mark {
            min-width: 7mm;
            min-height: 3mm;
            padding: .15mm .5mm;
            font-size: 4pt;
        }
        .sheet.cards-24 .scan-text {
            font-size: 4.5pt;
            line-height: 1.15;
        }
        .brand-mark {
            min-width: 10mm;
            min-height: 4.5mm;
            padding: .35mm 1mm;
            border-radius: 999px;
            background: #fff;
            color: #111;
            font-size: 5.5pt;
            font-weight: 900;
            display: grid;
            place-items: center;
            border: .25mm solid #e8e8e8;
        }
        .scan-text {
            font-size: 7.2pt;
            line-height: 1.25;
            font-weight: 700;
        }
        .card-number {
            direction: ltr;
            font-size: 19pt;
            letter-spacing: 1.15mm;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
            max-width: 100%;
        }
        .sheet.cards-10 .card-number {
            font-size: 16pt;
            letter-spacing: .9mm;
        }
        .sheet.cards-24 .card-number {
            font-size: 10pt;
            letter-spacing: .45mm;
        }
        .sheet.cards-24 .cut-mark {
            width: 4mm;
            height: 4mm;
        }
        .sheet.cards-24 .cut-mark::before { width: 4mm; }
        .sheet.cards-24 .cut-mark::after { height: 4mm; }
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body { background: #fff; }
            .toolbar { display: none; }
            .print-root { display: block; padding: 0; }
            .sheet {
                width: 210mm;
                height: 296mm;
                min-height: 296mm;
                margin: 0;
                box-shadow: none;
                overflow: hidden;
                break-after: auto;
                page-break-after: auto;
            }
            .sheet + .sheet {
                break-before: page;
                page-break-before: always;
            }
            .sheet.cards-24 {
                width: 210mm;
                height: 296mm;
                min-height: 296mm;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <div>
            <strong>بطاقات السحب:</strong>
            من <span dir="ltr">{{ $fromNumber }}</span> إلى <span dir="ltr">{{ $toNumber }}</span>
        </div>
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>

    <div class="print-root">
        @foreach($cardPages as $chunk)
            <section class="sheet cards-{{ $cardsPerPage }}">
                @foreach($chunk as $card)
                    <article class="ticket">
                        <span class="cut-mark cut-tr"></span>
                        <span class="cut-mark cut-br"></span>
                        <span class="cut-mark cut-bl"></span>

                        <div class="social-stack">
                            <div class="social-qr">
                                @if($socialQr1)
                                    <img src="{{ $socialQr1 }}" alt="QR السوشيال الأول">
                                @else
                                    <div class="social-placeholder">QR<br>Social</div>
                                @endif
                            </div>
                            <div class="social-qr">
                                @if($socialQr2)
                                    <img src="{{ $socialQr2 }}" alt="QR السوشيال الثاني">
                                @else
                                    <div class="social-placeholder">QR<br>Social</div>
                                @endif
                            </div>
                        </div>

                        <div class="follow-copy">
                            <div>تابعنا وفوت<br>السحب على<br>الجوائز</div>
                            <div>تابعنا وفوت<br>السحب على<br>الجوائز</div>
                        </div>

                        <div class="main-side">
                            <div class="main-qr">
                                <img src="{{ $card['qr'] }}" alt="QR بطاقة {{ $card['number'] }}">
                            </div>
                            <span class="brand-mark">{{ $brandText }}</span>
                            <div class="scan-text">امسح وادخل على التطبيق<br>لإدخال الكود التسلسلي</div>
                            <div class="card-number">{{ $card['number'] }}</div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endforeach
    </div>
</body>

</html>
