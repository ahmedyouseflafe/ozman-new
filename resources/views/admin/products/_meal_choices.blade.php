<section class="form-section" id="mealChoiceEditor" hidden>
    <div class="section-head"><div class="section-icon"><i class="ti ti-list-check"></i></div><div><h2>اختيارات الوجبة</h2><p>مثال: نوع النيجيري (سالمون أو تونا)، أو اختيار 4 رولات للسفينة. الأعداد والأسعار لكل وجبة واحدة.</p></div></div>
    <p>أضف إعدادًا جاهزًا ثم احفظ. اختر «أقسام أحددها من المنيو» وحدّد أقسام الرولات المطلوبة. كل نوع ينحسب اختيار واحد بدون تكرار، والوجبة نفسها لا تظهر ضمن الخيارات. خيارات المنيو مشمولة بسعر الوجبة.</p><div class="meal-choice-controls"><button type="button" data-choice-preset="sandwich">ساندويش</button><button type="button" data-choice-preset="nigiri">نيجيري</button><button type="button" data-choice-preset="4">سفينة · 4</button><button type="button" data-choice-preset="5">عائلية 40 · 5</button><button type="button" data-choice-preset="7">عائلية 60 · 7</button></div><div id="mealChoiceGroups"></div>
    <button type="button" class="btn" id="addMealChoiceGroup">+ إضافة مجموعة اختيارات</button>
</section>
@viewStyle('admin.products._meal_choices')
@php($initialMealChoices = old('meal_choice_groups', data_get($product ?? null, 'catalog_attributes.meal_choice_groups', [])))
@php($mealChoiceCategories = collect($categories ?? [])->where('is_active', true)->map(fn ($category) => ['id' => $category->id, 'shop_id' => $category->shop_id, 'name' => $category->name])->values())
<script>window.OZMAN_MEAL_CHOICE_CATEGORIES = @json($mealChoiceCategories);</script>
<script>window.OZMAN_MEAL_CHOICE_GROUPS = @json($initialMealChoices);</script>
<script>{!! file_get_contents(base_path('public/meal-choice-editor.js')) !!}</script>
