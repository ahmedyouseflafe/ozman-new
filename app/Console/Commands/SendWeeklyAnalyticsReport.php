<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ShopAnalytics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Sunday morning summary of the last 7 days for the main admins. */
class SendWeeklyAnalyticsReport extends Command
{
    protected $signature = 'analytics:weekly-report {--dry : Print the report instead of emailing it}';

    protected $description = 'Email the super admins a summary of the last 7 days across all shops';

    public function handle(ShopAnalytics $analytics): int
    {
        $to = now()->subDay()->endOfDay();
        $from = $to->copy()->subDays(6)->startOfDay();
        $report = $analytics->report($from, $to, null);
        $text = $this->compose($report);
        if ($this->option('dry')) {
            $this->line($text);

            return self::SUCCESS;
        }
        $admins = User::where('role', 'super_admin')->where('is_active', true)->whereNotNull('email')->pluck('email');
        foreach ($admins as $email) {
            Mail::raw($text, fn ($m) => $m->to($email)->subject('تقرير أوزمان الأسبوعي · '.$report['period']['from'].' ← '.$report['period']['to']));
        }
        $this->info('Sent to '.$admins->count().' admin(s).');

        return self::SUCCESS;
    }

    private function compose(array $r): string
    {
        $s = $r['summary'];
        $money = fn ($v) => number_format((float) $v, 2).' ₪';
        $i = $r['insights'] ?? [];
        $lines = [
            'تقرير أوزمان للأسبوع من '.$r['period']['from'].' إلى '.$r['period']['to'], '',
            '• قيمة المبيعات المؤكدة: '.$money($s['revenue']).($s['revenue_change'] !== null ? ' ('.($s['revenue_change'] > 0 ? '+' : '').$s['revenue_change'].'% عن الأسبوع الماضي)' : ''),
            '• عمليات البيع: '.$s['sales'].' — الطلبات: '.$s['orders'].' — الملغية: '.$s['cancelled'],
            '• زوار المحلات: '.$s['visitors'].' — تسجيلات عملاء: '.$s['registrations'],
        ];
        if (! empty($i['funnel']['total']['visit_to_order'])) {
            $lines[] = '• نسبة التحويل: '.$i['funnel']['total']['visit_to_order'].'% من الزوار طلبوا';
        }
        if (! empty($i['kitchen']['total']['timed'])) {
            $k = $i['kitchen']['total'];
            $lines[] = '• المطبخ: متوسط التحضير '.$k['avg_actual'].' د (الموعود '.$k['avg_promised'].' د) — متأخر '.$k['late_pct'].'%';
        }
        $lines[] = '';
        $lines[] = 'أعلى المحلات مبيعاً:';
        foreach (collect($r['shops'])->where('revenue', '>', 0)->take(5) as $n => $shop) {
            $lines[] = '  '.($n + 1).'. '.$shop['name'].' — '.$money($shop['revenue']).' ('.$shop['sales'].' بيع)';
        }
        if (! empty($i['alerts'])) {
            $lines[] = '';
            $lines[] = 'بحاجة لانتباهك:';
            foreach (array_slice($i['alerts'], 0, 8) as $alert) {
                $lines[] = '  '.$alert['icon'].' '.$alert['text'];
            }
        }
        $lines[] = '';
        $lines[] = 'التفاصيل الكاملة: '.route('admin.analytics.index', ['from' => $r['period']['from'], 'to' => $r['period']['to']]);

        return implode("\n", $lines);
    }
}
