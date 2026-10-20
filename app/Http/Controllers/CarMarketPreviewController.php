<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CarMarketPreviewController extends Controller
{
    public function index(): View
    {
        // Design examples only: no live listings, seller accounts, or market prices.
        $cars = [
            ['id'=>1, 'name'=>'كروس أوفر فاخرة', 'make'=>'أخرى', 'model'=>'PREMIUM CROSSOVER', 'year'=>2025, 'price'=>245000, 'km'=>0, 'city'=>'رام الله', 'condition'=>'new', 'body'=>'suv', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'hero.png', 'color'=>'رمادي', 'featured'=>true],
            ['id'=>2, 'name'=>'بي إم دبليو • سيدان', 'make'=>'BMW', 'model'=>'BMW · SPORT SEDAN', 'year'=>2020, 'price'=>210000, 'km'=>68000, 'city'=>'نابلس', 'condition'=>'used', 'body'=>'sedan', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'coupe.jpg', 'color'=>'أبيض', 'featured'=>false],
            ['id'=>3, 'name'=>'فيات 500', 'make'=>'فيات', 'model'=>'FIAT 500 · CITY CAR', 'year'=>2018, 'price'=>56000, 'km'=>82000, 'city'=>'الخليل', 'condition'=>'used', 'body'=>'hatchback', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'city.jpg', 'color'=>'أزرق', 'featured'=>false],
            ['id'=>4, 'name'=>'بورشه باناميرا', 'make'=>'بورشه', 'model'=>'PORSCHE PANAMERA', 'year'=>2022, 'price'=>395000, 'km'=>18000, 'city'=>'القدس', 'condition'=>'used', 'body'=>'sedan', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'sport.jpg', 'color'=>'أسود', 'featured'=>true],
            ['id'=>5, 'name'=>'فورد موستانج', 'make'=>'فورد', 'model'=>'FORD MUSTANG', 'year'=>2019, 'price'=>165000, 'km'=>52000, 'city'=>'رام الله', 'condition'=>'used', 'body'=>'coupe', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'classic.jpg', 'color'=>'أسود', 'featured'=>false],
            ['id'=>6, 'name'=>'شيفروليه كامارو', 'make'=>'شيفروليه', 'model'=>'CHEVROLET CAMARO', 'year'=>2024, 'price'=>320000, 'km'=>0, 'city'=>'بيت لحم', 'condition'=>'new', 'body'=>'coupe', 'fuel'=>'بنزين', 'gear'=>'أوتوماتيك', 'image'=>'performance.jpg', 'color'=>'أزرق', 'featured'=>false],
        ];
        return view('front.cars.index', compact('cars'));
    }

    public function media(Request $request, string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, ['hero.png','coupe.jpg','city.jpg','sport.jpg','classic.jpg','performance.jpg'], true), 404);
        $path = base_path('public/images/car-market/'.$file);
        abort_unless(is_file($path), 404);
        $response = response()->file($path, ['Cache-Control'=>'public, max-age=86400', 'X-Content-Type-Options'=>'nosniff']);
        $response->setEtag(hash_file('sha256', $path));
        $response->isNotModified($request);
        return $response;
    }
}
