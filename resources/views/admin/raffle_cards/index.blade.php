<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>بطاقات السحب - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.raffle_cards.index')
</head>

<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'بطاقات السحب'])
        <div class="content">
            <header class="page-head">
                <div>
                    <div class="page-kicker">Ozman Raffle</div>
                    <h1>بطاقات السحب</h1>
                    <p>أدخل الأرقام الرابحة فقط، وأي رقم غير موجود يدخل مرة واحدة في سحب جوائز البثوث المباشرة.</p>
                </div>
            </header>

            @if(session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="grid">
                <div class="card"><span>الأرقام الرابحة</span><strong>{{ $totalCards }}</strong><i class="ti ti-ticket"></i></div>
                <div class="card" style="--card-color:var(--green)"><span>بطاقات مستخدمة</span><strong>{{ $usedCards }}</strong><i class="ti ti-gift"></i></div>
                <div class="card" style="--card-color:var(--yellow)"><span>جوائز البثوث المباشرة</span><strong>{{ $liveEntriesCount }}</strong><i class="ti ti-broadcast"></i></div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-brand-whatsapp"></i> رقم واتساب السحب</div>
                </div>
                <form action="{{ route('raffle-cards.settings') }}" method="POST" class="form-grid" style="grid-template-columns:1fr auto;">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="raffle_whatsapp">رقم الواتساب الذي يتواصل معه الفائز</label>
                        <input id="raffle_whatsapp" name="raffle_whatsapp" value="{{ old('raffle_whatsapp', $raffleWhatsapp) }}" placeholder="+97059xxxxxxx" dir="ltr">
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> حفظ الرقم</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-printer"></i> توليد بطاقات QR للطباعة</div>
                        <p style="color:var(--muted); font-weight:800; margin-top:8px">اختر نطاق الأرقام وسيتم إنشاء صفحة A4 جاهزة للطباعة أو الحفظ PDF، وكل بطاقة تحمل QR خاص بها.</p>
                    </div>
                </div>
                <form action="{{ route('raffle-cards.printable') }}" method="POST" target="_blank" class="form-grid">
                    @csrf
                    <div>
                        <label for="from_number">من رقم</label>
                        <input id="from_number" name="from_number" value="{{ old('from_number', '000001') }}" maxlength="6" pattern="\d{6}" placeholder="000001" dir="ltr" required>
                    </div>
                    <div>
                        <label for="to_number">إلى رقم</label>
                        <input id="to_number" name="to_number" value="{{ old('to_number', '000100') }}" maxlength="6" pattern="\d{6}" placeholder="000100" dir="ltr" required>
                    </div>
                    <div>
                        <label for="cards_per_page">عدد البطاقات في الصفحة</label>
                        <select id="cards_per_page" name="cards_per_page" required>
                            <option value="8" @selected(old('cards_per_page', '8') === '8')>8 بطاقات</option>
                            <option value="10" @selected(old('cards_per_page') === '10')>10 بطاقات</option>
                            <option value="6" @selected(old('cards_per_page') === '6')>6 بطاقات</option>
                            <option value="24" @selected(old('cards_per_page') === '24')>24 بطاقة</option>
                        </select>
                    </div>
                    <div>
                        <label for="brand_text">اسم البراند على البطاقة</label>
                        <input id="brand_text" name="brand_text" value="{{ old('brand_text', 'Ozman') }}" placeholder="Ozman">
                    </div>
                    <div style="grid-column:span 2">
                        <label for="social_qr_1_url">QR السوشيال الأول</label>
                        <input id="social_qr_1_url" name="social_qr_1_url" value="{{ old('social_qr_1_url', $defaultSocialQrLinks['first'] ?? '') }}" placeholder="https://instagram.com/..." dir="ltr">
                    </div>
                    <div style="grid-column:span 2">
                        <label for="social_qr_2_url">QR السوشيال الثاني</label>
                        <input id="social_qr_2_url" name="social_qr_2_url" value="{{ old('social_qr_2_url', $defaultSocialQrLinks['second'] ?? '') }}" placeholder="https://tiktok.com/..." dir="ltr">
                    </div>
                    <button class="btn btn-primary" type="submit" style="grid-column:1 / -1">
                        <i class="ti ti-file-type-pdf"></i>
                        توليد صفحة الطباعة / PDF
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-notebook"></i> إنشاء دفتر بطاقات الربح</div>
                        <div class="booklet-intro">أدخل أول رقم فقط؛ الدفتر يحتوي دائمًا على 48 بطاقة متتالية، ثم اختر الهدايا وكميتها وارفع صورة كل هدية.</div>
                    </div>
                </div>
                @php $bookletGiftOptions = ['مدالية مفاتيح', 'معطر سيارة', 'قداحة', 'سماعة ايربودز']; @endphp
                <form action="{{ route('raffle-cards.random-bulk') }}" method="POST" enctype="multipart/form-data" class="booklet-form" id="raffleBookletForm">
                    @csrf
                    <div class="booklet-top">
                        <div>
                            <label for="booklet_start_number">رقم أول بطاقة في الدفتر</label>
                            <input id="booklet_start_number" name="booklet_start_number" value="{{ old('booklet_start_number') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000001" dir="ltr" required data-booklet-start>
                        </div>
                        <div class="booklet-range-preview" aria-live="polite">
                            <div><span>دفتر ثابت — 48 بطاقة</span><strong id="bookletRangePreview">أدخل رقم البداية</strong></div>
                            <i class="ti ti-tickets" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="booklet-gifts-head">
                        <div><h3>الهدايا داخل الدفتر</h3><p>اختر الهدية والكمية وارفع صورتها. التوزيع يكون عشوائيًا بين بطاقات الدفتر.</p></div>
                        <span class="booklet-total" id="bookletGiftTotal">0 هدية من 48</span>
                    </div>
                    <div class="booklet-gifts">
                        @foreach(range(0, 3) as $giftIndex)
                            <article class="booklet-gift-row">
                                <span class="booklet-gift-index">{{ str_pad((string) ($giftIndex + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <label for="booklet_gift_title_{{ $giftIndex }}">نوع الهدية</label>
                                    <select id="booklet_gift_title_{{ $giftIndex }}" name="gifts[{{ $giftIndex }}][title]" data-gift-select>
                                        <option value="">بدون هدية</option>
                                        @foreach($bookletGiftOptions as $giftOption)
                                            <option value="{{ $giftOption }}" @selected(old("gifts.{$giftIndex}.title") === $giftOption)>{{ $giftOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="booklet_gift_count_{{ $giftIndex }}">العدد</label>
                                    <input id="booklet_gift_count_{{ $giftIndex }}" name="gifts[{{ $giftIndex }}][count]" type="number" min="0" max="48" value="{{ old("gifts.{$giftIndex}.count", 0) }}" inputmode="numeric" data-gift-count>
                                </div>
                                <div class="booklet-image-field">
                                    <label for="booklet_gift_image_{{ $giftIndex }}">صورة الهدية</label>
                                    <label class="file-card" for="booklet_gift_image_{{ $giftIndex }}"><span data-file-label>ارفع صورة</span><i class="ti ti-photo-up"></i></label>
                                    <input class="file-input" id="booklet_gift_image_{{ $giftIndex }}" name="gifts[{{ $giftIndex }}][image]" type="file" accept="image/*" data-file-input>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p class="booklet-note"><i class="ti ti-info-circle"></i> البطاقات المتبقية بعد الهدايا المختارة تدخل في السحب العادي. لا يمكن تكرار أو تداخل دفتر مع دفتر سابق.</p>
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-sparkles"></i> إنشاء دفتر الـ48 بطاقة وتوزيع الهدايا
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-plus"></i> إضافة بطاقة رابحة يدويًا</div>
                </div>
                <form action="{{ route('raffle-cards.store') }}" method="POST" enctype="multipart/form-data" class="form-grid" id="manualRaffleCardForm">
                    @csrf
                    <div>
                        <label for="card_number">رقم البطاقة</label>
                        <input id="card_number" name="card_number" value="{{ old('card_number') }}" maxlength="6" pattern="\d{6}" placeholder="000000" dir="ltr" required>
                    </div>
                    <div>
                        <label for="prize_title">الجائزة</label>
                        <input id="prize_title" name="prize_title" value="{{ old('prize_title') }}" placeholder="اسم الجائزة" required>
                    </div>
                    <div>
                        <label for="prize_image">صورة الجائزة</label>
                        <label class="file-card" for="prize_image">
                            <span data-file-label>اختر صورة الجائزة</span>
                            <i class="ti ti-photo-up"></i>
                        </label>
                        <input class="file-input" id="prize_image" name="prize_image" type="file" accept="image/*" data-file-input>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" checked>
                        نشطة
                    </label>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-ticket"></i> إضافة بطاقة</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-file-type-pdf"></i> تحميل الأرقام الرابحة PDF</div>
                        <div class="muted">سيتم تضمين البطاقات الرابحة الموجودة فقط ضمن النطاق المحدد، مع بيانات الجائزة والحالة والفائز.</div>
                    </div>
                </div>
                <form action="{{ route('raffle-cards.export-pdf') }}" method="GET" class="form-grid">
                    <div>
                        <label for="pdf_from_number">رقم البطاقة من</label>
                        <input id="pdf_from_number" name="from_number" value="{{ old('from_number', '000000') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000" dir="ltr" required>
                    </div>
                    <div>
                        <label for="pdf_to_number">رقم البطاقة إلى</label>
                        <input id="pdf_to_number" name="to_number" value="{{ old('to_number', '999999') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="999999" dir="ltr" required>
                    </div>
                    <button class="btn btn-primary" type="submit" style="grid-column:span 2">
                        <i class="ti ti-download"></i> تحميل ملف PDF
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-list"></i> الأرقام الرابحة</div>
                        <button class="btn btn-danger" type="submit" form="winningCardsBulkDeleteForm" id="deleteSelectedWinningCardsBtn" disabled style="margin-top:10px">
                            <i class="ti ti-trash"></i> حذف المحدد
                        </button>
                    </div>
                    <form class="filters" method="GET">
                        <input name="search" value="{{ $search }}" placeholder="بحث برقم البطاقة أو الجائزة">
                        <select name="status" class="btn btn-soft" onchange="this.form.submit()">
                            <option value="all" @selected($status === 'all')>كل الحالات</option>
                            <option value="available" @selected($status === 'available')>غير مستخدمة</option>
                            <option value="used" @selected($status === 'used')>مستخدمة</option>
                        </select>
                        <button class="btn btn-soft" type="submit"><i class="ti ti-search"></i> بحث</button>
                    </form>
                </div>
                <form id="winningCardsBulkDeleteForm" action="{{ route('raffle-cards.bulk-destroy') }}" method="POST"
                    onsubmit="return confirm('حذف بطاقات الربح المحددة نهائيًا؟')">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="select-cell">
                                    <input type="checkbox" id="selectAllWinningCards" title="تحديد الكل">
                                </th>
                                <th>البطاقة</th>
                                <th>الجائزة</th>
                                <th>الحالة</th>
                                <th>الفائز</th>
                                <th>تعديل</th>
                            </tr>
                        </thead>
                        <tbody id="winningCardsTableBody">
                            @forelse($cards as $card)
                                <tr>
                                    <td class="select-cell">
                                        <input type="checkbox" name="cards[]" value="{{ $card->id }}" form="winningCardsBulkDeleteForm" data-winning-card-checkbox>
                                    </td>
                                    <td dir="ltr"><span class="tag">{{ $card->card_number }}</span></td>
                                    <td>
                                        <div class="prize">
                                            @if($card->prize_image)
                                                <img src="{{ asset($card->prize_image) }}" alt="{{ $card->prize_title }}">
                                            @endif
                                            <strong>{{ $card->prize_title }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        @if($card->used_at)
                                            <span class="tag red">مستخدمة</span>
                                        @else
                                            <span class="tag green">متاحة</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $card->used_customer_name ?: '-' }}
                                        @if($card->used_customer_whatsapp)
                                            <div dir="ltr" style="color:var(--muted)">{{ $card->used_customer_whatsapp }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('raffle-cards.update', $card) }}" method="POST" enctype="multipart/form-data" class="edit-box">
                                            @csrf
                                            @method('PUT')
                                            <input name="card_number" value="{{ $card->card_number }}" maxlength="6" pattern="\d{6}" dir="ltr" required>
                                            <input name="prize_title" value="{{ $card->prize_title }}" required>
                                            <label class="file-card" for="prize_image_{{ $card->id }}">
                                                <span data-file-label>تغيير الصورة</span>
                                                <i class="ti ti-photo-up"></i>
                                            </label>
                                            <input class="file-input" id="prize_image_{{ $card->id }}" name="prize_image" type="file" accept="image/*" data-file-input>
                                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($card->is_active)> نشطة</label>
                                            <div class="actions">
                                                <button class="btn btn-soft" type="submit"><i class="ti ti-device-floppy"></i></button>
                                            </div>
                                        </form>
                                        <form action="{{ route('raffle-cards.destroy', $card) }}" method="POST" onsubmit="return confirm('حذف بطاقة الربح؟')" style="margin-top:8px">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">لا توجد بطاقات رابحة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $cards->links() }}</div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-broadcast"></i> جوائز البثوث المباشرة</div>
                    <div class="live-actions">
                        <button class="btn btn-danger" type="submit" form="liveEntriesBulkDeleteForm" id="deleteSelectedLiveEntriesBtn" disabled>
                            <i class="ti ti-trash"></i> حذف المحدد
                        </button>
                        <button class="btn btn-primary" type="button" id="liveDrawPickBtn"
                            data-url="{{ route('raffle-cards.live-draw.random') }}"
                            data-token="{{ csrf_token() }}">
                            <i class="ti ti-confetti"></i> اختيار فائز عشوائي
                        </button>
                    </div>
                </div>
                <form id="liveEntriesBulkDeleteForm" action="{{ route('raffle-cards.live-draw.bulk-destroy') }}" method="POST"
                    onsubmit="return confirm('حذف الأرقام المحددة من سحب البث المباشر؟')">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="select-cell">
                                    <input type="checkbox" id="selectAllLiveEntries" title="تحديد الكل">
                                </th>
                                <th>رقم البطاقة</th>
                                <th>العميل</th>
                                <th>الهاتف/واتساب</th>
                                <th>تاريخ التسجيل</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($liveEntries as $entry)
                                <tr>
                                    <td class="select-cell">
                                        <input type="checkbox" name="entries[]" value="{{ $entry->id }}" form="liveEntriesBulkDeleteForm" data-live-entry-checkbox>
                                    </td>
                                    <td dir="ltr"><span class="tag">{{ $entry->card_number }}</span></td>
                                    <td>{{ $entry->customer_name ?: '-' }}</td>
                                    <td dir="ltr">{{ $entry->customer_whatsapp ?: $entry->customer_phone ?: '-' }}</td>
                                    <td>{{ optional($entry->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form action="{{ route('raffle-cards.live-draw.destroy', $entry) }}" method="POST" onsubmit="return confirm('حذف هذا الرقم من سحب البث المباشر؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">لا توجد بطاقات بث مباشر مسجلة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $liveEntries->links() }}</div>
            </section>
        </div>
    </main>
    <div class="live-draw-modal" id="liveDrawModal" aria-hidden="true">
        <div class="live-draw-card">
            <button class="live-draw-close" type="button" id="liveDrawCloseBtn" aria-label="إغلاق">&times;</button>
            <div class="live-draw-kicker">Ozman Live Draw</div>
            <div class="live-draw-title" id="liveDrawTitle">مبروك الفوز</div>
            <div class="live-draw-number" id="liveDrawNumber">000000</div>
            <div class="live-draw-info" id="liveDrawInfo"></div>
            <div class="live-draw-error" id="liveDrawError" hidden></div>
        </div>
    </div>
    @viewScript('admin.raffle_cards.index')
</body>

</html>
