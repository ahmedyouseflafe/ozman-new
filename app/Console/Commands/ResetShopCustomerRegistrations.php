<?php
namespace App\Console\Commands;

use App\Models\{Shop,VisitorRegistration,ShopSignupReward,ShopSignupWheelCycle,RewardWheel};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Storage};

class ResetShopCustomerRegistrations extends Command
{
    protected $signature = 'shop:reset-customer-registrations {slug} {--execute : Apply reset after creating a private backup}';
    protected $description = 'Reset one shop customer registrations and signup entitlements; keep orders and wheel settings';

    public function handle(): int
    {
        $shop=Shop::where('slug',$this->argument('slug'))->firstOrFail();
        $query=VisitorRegistration::where('shop_id',$shop->id)->where('type','customer')->where('marketing_source','restaurant_welcome');
        $this->info($shop->name.' (#'.$shop->id.'): '.$query->count().' customer registrations.');
        if(!$this->option('execute')) { $this->comment('Preview only. Add --execute to reset.');return self::SUCCESS; }
        DB::transaction(function() use ($shop,$query) {
            $shop=Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $rewards=ShopSignupReward::where('shop_id',$shop->id);
            $cycles=ShopSignupWheelCycle::where('shop_id',$shop->id);
            $wheel=RewardWheel::where('shop_id',$shop->id)->where('key','shop_signup_'.$shop->id);
            $path='registration-resets/'.$shop->id.'-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
            $backup=['shop_id'=>$shop->id,'version'=>$shop->customer_registration_version,'registrations'=>$query->get()->toArray(),'rewards'=>$rewards->get()->toArray(),'cycles'=>$cycles->get()->toArray(),'wheel'=>$wheel->get()->toArray()];
            if(!Storage::disk('local')->put($path,json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)))throw new \RuntimeException('Backup failed; reset cancelled.');
            $rewards->delete();$query->delete();$cycles->delete();$wheel->update(['spin_cycle'=>null]);
            $shop->increment('customer_registration_version');
            $this->info('Private backup: '.$path);
        });
        $this->info('Reset complete. Reload the storefront to register again. Existing orders and other shops were preserved.');
        return self::SUCCESS;
    }
}
