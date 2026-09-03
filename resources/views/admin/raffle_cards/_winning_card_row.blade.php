<tr data-winning-card-row="{{ $card->id }}">
    <td class="select-cell"><input type="checkbox" name="cards[]" value="{{ $card->id }}" form="winningCardsBulkDeleteForm" data-winning-card-checkbox></td>
    <td dir="ltr"><span class="tag">{{ $card->card_number }}</span></td>
    <td><div class="prize">@if($card->prize_image)<img src="{{ asset($card->prize_image) }}" alt="{{ $card->prize_title }}">@endif<strong>{{ $card->prize_title }}</strong></div></td>
    <td>@if($card->used_at)<span class="tag red">مستخدمة</span>@else<span class="tag green">متاحة</span>@endif</td>
    <td>{{ $card->used_customer_name ?: '-' }}@if($card->used_customer_whatsapp)<div dir="ltr" style="color:var(--muted)">{{ $card->used_customer_whatsapp }}</div>@endif</td>
    <td>
        <form action="{{ route('raffle-cards.update', $card) }}" method="POST" enctype="multipart/form-data" class="edit-box">
            @csrf @method('PUT')
            <input name="card_number" value="{{ $card->card_number }}" maxlength="6" pattern="\d{6}" dir="ltr" required>
            <input name="prize_title" value="{{ $card->prize_title }}" required>
            <label class="file-card" for="prize_image_{{ $card->id }}"><span data-file-label>تغيير الصورة</span><i class="ti ti-photo-up"></i></label>
            <input class="file-input" id="prize_image_{{ $card->id }}" name="prize_image" type="file" accept="image/*" data-file-input>
            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($card->is_active)> نشطة</label>
            <div class="actions"><button class="btn btn-soft" type="submit"><i class="ti ti-device-floppy"></i></button></div>
        </form>
        <form action="{{ route('raffle-cards.destroy', $card) }}" method="POST" onsubmit="return confirm('حذف بطاقة الربح؟')" style="margin-top:8px">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> حذف</button>
        </form>
    </td>
</tr>
