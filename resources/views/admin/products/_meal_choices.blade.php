<section class="form-section" id="mealChoiceEditor" hidden>
    <div class="section-head"><div class="section-icon"><i class="ti ti-list-check"></i></div><div><h2>اختيارات الوجبة</h2><p>مثال: نوع النيجيري (سالمون أو تونا)، أو اختيار 4 رولات للسفينة. الأعداد والأسعار لكل وجبة واحدة.</p></div></div>
    <p>أضف إعدادًا جاهزًا ثم احفظ. اختر «أقسام أحددها من المنيو» وحدّد أقسام الرولات المطلوبة. كل نوع ينحسب اختيار واحد بدون تكرار، والوجبة نفسها لا تظهر ضمن الخيارات. خيارات المنيو مشمولة بسعر الوجبة.</p><div class="meal-choice-controls"><button type="button" data-choice-preset="sandwich">ساندويش</button><button type="button" data-choice-preset="nigiri">نيجيري</button><button type="button" data-choice-preset="4">سفينة · 4</button><button type="button" data-choice-preset="5">عائلية 40 · 5</button><button type="button" data-choice-preset="7">عائلية 60 · 7</button></div><div id="mealChoiceGroups"></div>
    <button type="button" class="btn" id="addMealChoiceGroup">+ إضافة مجموعة اختيارات</button>
</section>
<style>
    #mealChoiceEditor[hidden]{display:none!important}.meal-choice-group{border:1px solid #26515a;border-radius:16px;padding:16px;margin-bottom:15px;background:#07151b50}
    .meal-choice-settings{display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px}.meal-choice-group label{display:grid;gap:6px;font-size:12px;color:#b9d5df}
    .meal-choice-option{display:grid;grid-template-columns:2fr 1fr auto;gap:10px;margin:10px 0;align-items:end}.meal-choice-group button{padding:8px 12px;border:1px solid #28545d;border-radius:10px;background:#09242d;color:#70e5ef;font:inherit;cursor:pointer}.meal-choice-controls{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin:12px 0}.meal-choice-controls label{display:flex;align-items:center}.meal-choice-controls input{width:auto!important}
    .meal-choice-categories{display:flex;flex-wrap:wrap;gap:12px;padding:14px 0}.meal-choice-categories label{display:flex;align-items:center;border:1px solid #28545d;padding:10px;border-radius:8px}.meal-choice-categories input{width:20px!important;height:20px;accent-color:#00cfe8}.meal-choice-group [hidden]{display:none!important}
    @media(max-width:600px){.meal-choice-settings{grid-template-columns:1fr 1fr}.meal-choice-settings label:first-child{grid-column:1/-1}.meal-choice-option{grid-template-columns:minmax(0,2fr) minmax(0,1fr) auto}.meal-choice-group{padding:10px}}
</style>
@php($initialMealChoices = old('meal_choice_groups', data_get($product ?? null, 'catalog_attributes.meal_choice_groups', [])))
@php($mealChoiceCategories = collect($categories ?? [])->where('is_active', true)->map(fn ($category) => ['id' => $category->id, 'shop_id' => $category->shop_id, 'name' => $category->name])->values())
<script>window.OZMAN_MEAL_CHOICE_CATEGORIES = @json($mealChoiceCategories);</script>
<script>window.OZMAN_MEAL_CHOICE_GROUPS = @json($initialMealChoices);</script>
<script>{!! file_get_contents(base_path('public/meal-choice-editor.js')) !!}</script>
