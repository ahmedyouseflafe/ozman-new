@if($errors->any())
    <div class="alert">
        <strong>يرجى مراجعة البيانات المدخلة:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
