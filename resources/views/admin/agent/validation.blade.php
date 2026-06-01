@if($errors->any())
    <div class="alert">
        <strong>راجع الحقول التالية:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
