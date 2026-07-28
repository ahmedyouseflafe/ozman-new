<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>الأرقام الرابحة</title>
    <style>
        @page { margin: 24px; }
        body {
            direction: ltr;
            font-family: DejaVu Sans, sans-serif;
            color: #161616;
            font-size: 11px;
        }
        h1 { margin: 0 0 6px; color: #007f91; font-size: 24px; text-align: right; }
        .summary { margin-bottom: 18px; color: #555; text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td {
            border: 1px solid #cfd8dc;
            padding: 8px;
            text-align: right;
            direction: ltr;
            vertical-align: middle;
        }
        th { color: #fff; background: #007f91; font-weight: bold; }
        tbody tr:nth-child(even) { background: #f3f7f8; }
        .number { direction: ltr; text-align: center; font-weight: bold; color: #007f91; }
        .image { width: 42px; height: 42px; object-fit: cover; }
        .status-used { color: #bd1831; font-weight: bold; }
        .status-available { color: #087a42; font-weight: bold; }
        .empty { padding: 28px; text-align: center; color: #777; }
        .footer { margin-top: 12px; color: #777; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $shapeArabic('تقرير الأرقام الرابحة') }}</h1>
    <div class="summary">
        {{ $shapeArabic('النطاق') }}: {{ $fromNumber }} — {{ $toNumber }}
        | {{ $shapeArabic('عدد البطاقات الرابحة') }}: {{ $cards->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:12%">{{ $shapeArabic('رقم البطاقة') }}</th>
                <th style="width:10%">{{ $shapeArabic('الصورة') }}</th>
                <th style="width:23%">{{ $shapeArabic('الجائزة') }}</th>
                <th style="width:13%">{{ $shapeArabic('الحالة') }}</th>
                <th style="width:22%">{{ $shapeArabic('الفائز') }}</th>
                <th style="width:20%">{{ $shapeArabic('رقم التواصل') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cards as $card)
                <tr>
                    <td class="number">{{ $card->card_number }}</td>
                    <td>
                        @if($card->pdf_prize_image)
                            <img class="image" src="{{ $card->pdf_prize_image }}" alt="">
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $card->pdf_prize_title }}</td>
                    <td class="{{ $card->used_at ? 'status-used' : 'status-available' }}">
                        {{ $shapeArabic($card->used_at ? 'مستخدمة' : 'متاحة') }}
                    </td>
                    <td>{{ $card->pdf_customer_name }}</td>
                    <td dir="ltr">{{ $card->used_customer_whatsapp ?: ($card->used_customer_phone ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="6">{{ $shapeArabic('لا توجد بطاقات رابحة ضمن النطاق المحدد.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ $shapeArabic('تاريخ إنشاء التقرير') }}: {{ $generatedAt->format('Y-m-d H:i') }} — Ozman</div>
</body>
</html>
