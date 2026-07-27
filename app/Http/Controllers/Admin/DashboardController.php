<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'kpis' => [
                ['label' => 'کل هنرجویان', 'value' => '۱۲٬۴۵۸', 'hint' => '۱۲٪ رشد این ماه', 'badge' => 'رشد', 'tone' => 'amber', 'icon' => '♙'],
                ['label' => 'اساتید فعال', 'value' => '۸۶', 'hint' => 'در حال تدریس', 'badge' => 'فعال', 'tone' => 'emerald', 'icon' => '♬'],
                ['label' => 'جلسات امروز', 'value' => '۱۵۶', 'hint' => 'برنامه‌ریزی‌شده', 'badge' => 'امروز', 'tone' => 'sky', 'icon' => '◷'],
                ['label' => 'نرخ تبدیل', 'value' => '۴٫۸٪', 'hint' => '۰٫۶٪ بهبود', 'badge' => 'بهبود', 'tone' => 'violet', 'icon' => '◎'],
            ],
            'chartBars' => [
                ['label' => 'شنبه', 'value' => '۱۲ م.', 'height' => '48'],
                ['label' => 'یکشنبه', 'value' => '۱۶ م.', 'height' => '65'],
                ['label' => 'دوشنبه', 'value' => '۱۴ م.', 'height' => '58'],
                ['label' => 'سه‌شنبه', 'value' => '۲۰ م.', 'height' => '82'],
                ['label' => 'چهارشنبه', 'value' => '۱۸ م.', 'height' => '71'],
                ['label' => 'پنجشنبه', 'value' => '۲۳ م.', 'height' => '92'],
                ['label' => 'جمعه', 'value' => '۱۹ م.', 'height' => '76'],
            ],
            'activities' => [
                ['title' => 'هنرجوی جدید ثبت‌نام کرد', 'description' => 'مریم رضایی · ۵ دقیقه پیش', 'badge' => 'ثبت‌نام', 'tone' => 'sky', 'time' => 'اکنون'],
                ['title' => 'ثبت‌نام دوره انجام شد', 'description' => 'علی محمدی · ویولن مقدماتی', 'badge' => 'دوره', 'tone' => 'violet', 'time' => '۱۲ دقیقه پیش'],
                ['title' => 'جلسه با موفقیت برگزار شد', 'description' => 'سارا احمدی · اتاق شماره ۲', 'badge' => 'جلسه', 'tone' => 'emerald', 'time' => '۲۵ دقیقه پیش'],
                ['title' => 'استاد جدید اضافه شد', 'description' => 'نگار کریمی · پیانو', 'badge' => 'استاد', 'tone' => 'amber', 'time' => '۱ ساعت پیش'],
            ],
            'recentEnrollments' => [
                ['code' => '#۴۸۲۱', 'student' => 'نگار کریمی', 'course' => 'پیانو مقدماتی', 'amount' => '۳٫۸۵ میلیون', 'status' => 'در انتظار', 'variant' => 'warning'],
                ['code' => '#۴۸۲۰', 'student' => 'علی محمدی', 'course' => 'گیتار کلاسیک', 'amount' => '۱۲٫۴ میلیون', 'status' => 'تأیید شده', 'variant' => 'info'],
                ['code' => '#۴۸۱۹', 'student' => 'سارا احمدی', 'course' => 'ویولن متوسط', 'amount' => '۱٫۷۸ میلیون', 'status' => 'تکمیل شده', 'variant' => 'success'],
            ],
            'topCourses' => [
                ['name' => 'ویولن مقدماتی', 'meta' => '۴۵ ثبت‌نام', 'value' => '۹۲', 'amount' => '۴۰۵ م.', 'tone' => 'amber'],
                ['name' => 'پیانو کلاسیک', 'meta' => '۳۸ ثبت‌نام', 'value' => '۷۸', 'amount' => '۳۲۰ م.', 'tone' => 'sky'],
                ['name' => 'گیتار مدرن', 'meta' => '۲۹ ثبت‌نام', 'value' => '۶۱', 'amount' => '۸۷ م.', 'tone' => 'violet'],
            ],
        ]);
    }
}
