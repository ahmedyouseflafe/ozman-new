<?php
namespace App\Services;
use App\Models\{PromoterPost, PromoterUpdate, CommunityPushSubscription};
use Illuminate\Support\Facades\DB;
class PromoterFollowerNotifications {
    public function publish(PromoterPost $post): void {
        if (!$post->isVisible()) return;
        $post->profile->followers()->where('users.is_active',true)->select('users.id')->chunkById(200,function($users) use($post) {
            foreach($users as $user) {
                $update=PromoterUpdate::firstOrCreate(['user_id'=>$user->id,'promoter_post_id'=>$post->id]);
                foreach(CommunityPushSubscription::where('user_id',$user->id)->pluck('id') as $id) {
                    DB::table('community_push_deliveries')->insertOrIgnore(['subscription_id'=>$id,'update_id'=>$update->id,'available_at'=>now()]);
                }
            }
        },'users.id','id');
    }
    public function flush(): int {
        $sent=0; $deadline=microtime(true)+40;
        $ids=DB::table('community_push_deliveries')->whereNull('sent_at')->whereNull('failed_at')->where('available_at','<=',now())
            ->where(fn($q)=>$q->whereNull('locked_at')->orWhere('locked_at','<',now()->subMinutes(3)))->orderBy('id')->limit(100)->pluck('id');
        foreach($ids as $id) {
            if(microtime(true)>$deadline) break;
            if(!DB::table('community_push_deliveries')->where('id',$id)->whereNull('sent_at')->whereNull('failed_at')
                ->where(fn($q)=>$q->whereNull('locked_at')->orWhere('locked_at','<',now()->subMinutes(3)))
                ->update(['locked_at'=>now(),'attempts'=>DB::raw('attempts + 1')])) continue;
            $delivery=DB::table('community_push_deliveries')->find($id);
            $device=CommunityPushSubscription::with('user')->find($delivery->subscription_id);
            $update=PromoterUpdate::with('post.profile')->find($delivery->update_id);
            $post=$update?->post;
            if(!$device || !$device->user?->is_active || !$post?->isVisible() || (int)$device->user_id!==(int)$update->user_id
                || !$post->profile->followers()->where('users.id',$device->user_id)->exists()) {
                DB::table('community_push_deliveries')->where('id',$id)->update(['failed_at'=>now(),'locked_at'=>null]); continue;
            }
            try {
                $result=app(WebPushService::class)->sendCommunityDevice($device,[
                    'title'=>$post->profile->display_name,'body'=>$post->kind==='story'?'ستوري جديدة — شوفها قبل ما تنتهي':'منشور جديد بانتظارك',
                    'url'=>$post->profile->publicUrl(),'tag'=>'promoter-post-'.$post->id,'actionTitle'=>'شاهد المحتوى',
                ]);
                if($result==='expired') { $device->delete(); continue; }
                if($result!=='sent') throw new \RuntimeException('Push delivery failed');
                DB::table('community_push_deliveries')->where('id',$id)->update(['sent_at'=>now(),'locked_at'=>null]); $sent++;
            } catch(\Throwable $e) {
                DB::table('community_push_deliveries')->where('id',$id)->update(['locked_at'=>null,'failed_at'=>$delivery->attempts>=8?now():null,'available_at'=>now()->addMinutes(min(60,2**$delivery->attempts))]);
            }
        }
        return $sent;
    }
}
