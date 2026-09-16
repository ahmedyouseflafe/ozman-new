<?php

// Transcribed from the menu supplied by the restaurant owner.
// Photos are representative Pexels stock images, not photographs of the restaurant's food.
// Each photo's source is https://images.pexels.com/photos/{pexels_id}/pexels-photo-{pexels_id}.jpeg and its license is
// https://www.pexels.com/license/ . Prices are in ILS.
return [
    'categories' => [
        [
            'key' => 'hummus-ful',
            'name' => 'حمص وفول',
            'name_he' => 'חומוס ופול',
            'name_en' => 'Hummus & Foul',
            'image' => 'hummus.jpg',
            'products' => [
                ['key' => 'hummus-ful-musabaha', 'name' => 'حمص حب / فول / مسبحة', 'name_he' => 'חומוס גרגירים / פול / מסבחה', 'name_en' => 'Hummus / Foul / Musabaha', 'price' => 33, 'image' => 'hummus.jpg', 'pexels_id' => 6252721],
                ['key' => 'hummus-meat-personal', 'name' => 'حمص لحمة - وجبة شخصية', 'name_he' => 'חומוס עם בשר - מנה אישית', 'name_en' => 'Hummus with Meat - Individual', 'price' => 55, 'image' => 'hummus-meat-small.jpg', 'pexels_id' => 5191824],
                ['key' => 'hummus-meat-for-two', 'name' => 'حمص لحمة - وجبة زوجية', 'name_he' => 'חומוס עם בשר - מנה זוגית', 'name_en' => 'Hummus with Meat - For Two', 'price' => 100, 'image' => 'hummus-meat-large.jpg', 'pexels_id' => 5191830],
                ['key' => 'hummus-kibbeh', 'name' => 'حمص كبة', 'name_he' => 'חומוס עם קובה', 'name_en' => 'Hummus with Kibbeh', 'price' => 50, 'image' => 'hummus-kibbeh.jpg', 'pexels_id' => 39018407],
                ['key' => 'hummus-kebab', 'name' => 'حمص كباب', 'name_he' => 'חומוס עם קבב', 'name_en' => 'Hummus with Kebab', 'price' => 50, 'image' => 'hummus-kebab.jpg', 'pexels_id' => 6419749],
                ['key' => 'hummus-chicken', 'name' => 'حمص دجاج', 'name_he' => 'חומוס עם עוף', 'name_en' => 'Hummus with Chicken', 'price' => 45, 'image' => 'hummus-chicken.jpg', 'pexels_id' => 18363397],
                ['key' => 'hummus-mushrooms', 'name' => 'حمص فطر', 'name_he' => 'חומוס עם פטריות', 'name_en' => 'Hummus with Mushrooms', 'price' => 38, 'image' => 'hummus-mushroom.jpg', 'pexels_id' => 6252673],
            ],
        ],
        [
            'key' => 'main-meals',
            'name' => 'وجبات أساسية',
            'name_he' => 'מנות עיקריות',
            'name_en' => 'Main Meals',
            'image' => 'shakshuka.jpg',
            'products' => [
                ['key' => 'shakshuka', 'name' => 'شكشوكة', 'name_he' => 'שקשוקה', 'name_en' => 'Shakshuka', 'price' => 35, 'image' => 'shakshuka.jpg', 'pexels_id' => 691077],
                ['key' => 'omelette', 'name' => 'عجة', 'name_he' => 'חביתה', 'name_en' => 'Omelette', 'price' => 25, 'image' => 'omelette.jpg', 'pexels_id' => 12310569],
                ['key' => 'sausage-omelette', 'name' => 'عجة نقانق', 'name_he' => 'חביתת נקניקיות', 'name_en' => 'Sausage Omelette', 'price' => 30, 'image' => 'omelette-sausage.jpg', 'pexels_id' => 32795473],
                ['key' => 'fried-eggs', 'name' => 'بيض مقلي', 'name_he' => 'ביצת עין', 'name_en' => 'Fried Eggs', 'price' => 20, 'image' => 'fried-eggs.jpg', 'pexels_id' => 8980397],
                ['key' => 'tomato-meat', 'name' => 'بندورة ولحمة', 'name_he' => 'עגבניות עם בשר', 'name_en' => 'Tomato and Meat', 'price' => 45, 'image' => 'tomato-meat.jpg', 'pexels_id' => 30678525],
                ['key' => 'okra', 'name' => 'بامية', 'name_he' => 'במיה', 'name_en' => 'Okra', 'price' => 30, 'image' => 'okra.jpg', 'pexels_id' => 6063309],
                ['key' => 'farmers-salad', 'name' => 'سلطة فلاحية', 'name_he' => 'סלט פלאחי', 'name_en' => 'Farmer\'s Salad', 'price' => 20, 'image' => 'salad.jpg', 'pexels_id' => 15059716],
                ['key' => 'fries', 'name' => 'شيبس', 'name_he' => "צ'יפס", 'name_en' => 'French Fries', 'price' => 15, 'image' => 'fries.jpg', 'pexels_id' => 5695624],
                ['key' => 'pastries', 'name' => 'صحن معجنات', 'name_he' => 'צלחת מאפים', 'name_en' => 'Pastry Platter', 'price' => 30, 'image' => 'pastries.jpg', 'pexels_id' => 30944693],
                ['key' => 'fried-kibbeh-five', 'name' => 'صحن كبة مقلية - 5 حبات', 'name_he' => 'צלחת קובה מטוגנת - 5 יחידות', 'name_en' => 'Fried Kibbeh - 5 Pieces', 'price' => 30, 'image' => 'fried-kibbeh.jpg', 'pexels_id' => 10837799],
                ['key' => 'fried-meat-personal', 'name' => 'صحن لحمة مقلية - شخصي', 'name_he' => 'צלחת בשר מטוגן - מנה אישית', 'name_en' => 'Fried Meat - Individual', 'price' => 60, 'image' => 'fried-meat-small.jpg', 'pexels_id' => 34251051],
                ['key' => 'fried-meat-large', 'name' => 'صحن لحمة مقلية - كبير', 'name_he' => 'צלחת בשר מטוגן גדולה', 'name_en' => 'Fried Meat - Large', 'price' => 100, 'image' => 'fried-meat-large.jpg', 'pexels_id' => 15059694],
            ],
        ],
        [
            'key' => 'sandwiches',
            'name' => 'سندويشات',
            'name_he' => 'סנדוויצ׳ים',
            'name_en' => 'Sandwiches',
            'image' => 'falafel-sandwich.jpg',
            'products' => [
                ['key' => 'falafel-sandwich', 'name' => 'رغيف فلافل', 'name_he' => 'פיתה פלאפל', 'name_en' => 'Falafel Sandwich', 'price' => 15, 'image' => 'falafel-sandwich.jpg', 'pexels_id' => 6546020],
                ['key' => 'schnitzel-baguette', 'name' => 'باجيت شنيتسل', 'name_he' => 'באגט שניצל', 'name_en' => 'Schnitzel Baguette', 'price' => 25, 'image' => 'schnitzel-sandwich.jpg', 'pexels_id' => 31918770],
            ],
        ],
        [
            'key' => 'drinks',
            'name' => 'مشروبات',
            'name_he' => 'שתייה',
            'name_en' => 'Drinks',
            'image' => 'cold-drink-small.jpg',
            'products' => [
                ['key' => 'cold-drink-small', 'name' => 'بارد صغير', 'name_he' => 'שתייה קלה קטנה', 'name_en' => 'Cold Drink - Small', 'price' => 8, 'image' => 'cold-drink-small.jpg', 'pexels_id' => 20045266],
                ['key' => 'cold-drink-large', 'name' => 'بارد كبير', 'name_he' => 'שתייה קלה גדולה', 'name_en' => 'Cold Drink - Large', 'price' => 16, 'image' => 'cold-drink-large.jpg', 'pexels_id' => 34385014],
                ['key' => 'water-small', 'name' => 'ماء صغير', 'name_he' => 'מים קטן', 'name_en' => 'Water - Small', 'price' => 5, 'image' => 'water-small.jpg', 'pexels_id' => 11860563],
                ['key' => 'water-large', 'name' => 'ماء كبير', 'name_he' => 'מים גדול', 'name_en' => 'Water - Large', 'price' => 10, 'image' => 'water-large.jpg', 'pexels_id' => 11860559],
            ],
        ],
    ],
];
