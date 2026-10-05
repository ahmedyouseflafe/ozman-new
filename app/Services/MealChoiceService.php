<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MealChoiceService
{
    public function normalize(array $groups): array
    {
        $data = Validator::make(['groups'=>$groups], [
            'groups'=>'array|max:10', 'groups.*'=>'array:id,name,min,max,allow_repeat,options,source',
            'groups.*.source'=>'nullable|in:manual,menu',
            'groups.*.id'=>'required|string|regex:/^[a-zA-Z0-9_-]{1,64}$/|distinct',
            'groups.*.name'=>'required|string|max:100',
            'groups.*.min'=>'required|integer|min:0|max:20', 'groups.*.max'=>'required|integer|min:1|max:20',
            'groups.*.allow_repeat'=>'required|boolean', 'groups.*.options'=>'sometimes|array|max:30',
            'groups.*.options.*'=>'array:id,name,price',
            'groups.*.options.*.id'=>'required|string|regex:/^[a-zA-Z0-9_-]{1,64}$/',
            'groups.*.options.*.name'=>'required|string|max:100',
            'groups.*.options.*.price'=>'required|numeric|min:0|max:100000',
        ])->validate();
        return collect($data['groups'])->map(function ($group) {
            $source=$group['source']??'manual';
            $options=collect($source==='menu'?[]:($group['options']??[]))->map(fn($option)=>[
                'id'=>$option['id'],'name'=>trim($option['name']),'price'=>round((float)$option['price'],2),
            ])->values();
            if ($group['min']>$group['max'] || trim($group['name'])==='' || ($source==='manual' && ($options->isEmpty() || $options->contains(fn($o)=>$o['name']==='') || (!$group['allow_repeat'] && $group['max']>$options->count())))
                || $options->pluck('id')->unique()->count()!==$options->count() || $options->pluck('name')->unique()->count()!==$options->count()) {
                throw ValidationException::withMessages(['meal_choice_groups'=>'راجع حدود الاختيار وأسماء الخيارات؛ لا تكرر الخيار، والحد الأعلى لا يتجاوز عدد الأنواع إلا عند السماح بالتكرار.']);
            }
            return ['id'=>$group['id'],'name'=>trim($group['name']),'min'=>(int)$group['min'],'max'=>(int)$group['max'],
                'allow_repeat'=>(bool)$group['allow_repeat'],'source'=>$source,'options'=>$options->all()];
        })->values()->all();
    }

    public function forProduct(\App\Models\Product $product, ?\Illuminate\Support\Collection $menu = null): array
    {
        $groups=$product->catalog_attributes['meal_choice_groups']??[];
        if (!collect($groups)->contains(fn($g)=>($g['source']??'manual')==='menu')) return $groups;
        $menu ??= \App\Models\Product::where('shop_id',$product->shop_id)->where('is_active',true)
            ->whereHas('category',fn($q)=>$q->where('is_active',true))->orderBy('name')->get();
        foreach ($groups as &$group) {
            if (($group['source']??'manual')!=='menu') continue;
            $group['options']=$menu->filter(fn($p)=>$p->shop_id===$product->shop_id && $p->id!==$product->id && $p->is_active)
                ->map(fn($p)=>['id'=>'product-'.$p->id,'name'=>$p->name,'price'=>0])->values()->all();
        }
        return $groups;
    }

    public function resolve(array $groups, array $selections): array
    {
        $byId=collect($selections)->keyBy('group_id');
        if ($byId->count()!==count($selections) || $byId->keys()->diff(array_column($groups,'id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['items'=>'إحدى مجموعات اختيارات الوجبة غير متاحة. أعد اختيار الوجبة.']);
        }
        $resolved=[];$extra=0;
        foreach($groups as $group) {
            $selected=$byId->get($group['id'])['options']??[];
            $available=collect($group['options'])->keyBy('id');$seen=[];$count=0;$options=[];
            foreach($selected as $selection) {
                $id=$selection['option_id'];$qty=(int)$selection['qty'];$option=$available->get($id);
                if (!$option || isset($seen[$id]) || $qty<1 || $qty>20 || (!$group['allow_repeat'] && $qty>1)) {
                    throw ValidationException::withMessages(['items'=>'اختيار غير صالح ضمن '.$group['name'].'.']);
                }
                $seen[$id]=true;$count+=$qty;$extra+=(float)$option['price']*$qty;
                $options[]=['id'=>$id,'name'=>$option['name'],'qty'=>$qty,'price'=>(float)$option['price']];
            }
            if ($count<$group['min'] || $count>$group['max']) {
                throw ValidationException::withMessages(['items'=>'اختر من '.$group['min'].' إلى '.$group['max'].' ضمن «'.$group['name'].'» لكل وجبة.']);
            }
            if ($options) $resolved[]=['group_id'=>$group['id'],'name'=>$group['name'],'options'=>$options];
        }
        return ['groups'=>$resolved,'extra'=>round($extra,2)];
    }

    public static function summary(array $groups): string
    {
        return collect($groups)->map(fn($group)=>$group['name'].': '.collect($group['options'])->map(fn($option)=>$option['qty'].'× '.$option['name'])->implode('، '))->implode(' | ');
    }
}
