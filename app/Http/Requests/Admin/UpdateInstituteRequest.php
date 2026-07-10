<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:120'],
            'name_en'             => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9\s\-&.\']+$/'],
            'description'         => ['nullable', 'string', 'max:500'],

            'logo'                => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'cover'               => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],

            'phone'               => ['nullable', 'string', 'max:20'],
            'mobile'              => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:120'],
            'website'             => ['nullable', 'url', 'max:200'],

            'instagram'           => ['nullable', 'string', 'max:80'],
            'telegram'            => ['nullable', 'string', 'max:80'],
            'whatsapp'            => ['nullable', 'string', 'max:20'],

            'address'             => ['nullable', 'string', 'max:300'],
            'city'                => ['nullable', 'string', 'max:60'],
            'province'            => ['nullable', 'string', 'max:60'],
            'postal_code'         => ['nullable', 'string', 'max:10'],

            'working_days'        => ['nullable', 'array'],
            'working_days.*'      => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'working_hours_from'  => ['nullable', 'date_format:H:i'],
            'working_hours_to'    => ['nullable', 'date_format:H:i', 'after:working_hours_from'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'               => 'نام آموزشگاه',
            'name_en'            => 'نام انگلیسی',
            'description'        => 'توضیحات',
            'logo'               => 'لوگو',
            'cover'              => 'تصویر کاور',
            'phone'              => 'شماره تماس',
            'mobile'             => 'موبایل',
            'email'              => 'ایمیل',
            'website'            => 'وب‌سایت',
            'instagram'          => 'اینستاگرام',
            'telegram'           => 'تلگرام',
            'whatsapp'           => 'واتساپ',
            'address'            => 'آدرس',
            'city'               => 'شهر',
            'province'           => 'استان',
            'postal_code'        => 'کد پستی',
            'working_days'       => 'روزهای کاری',
            'working_hours_from' => 'شروع ساعت کاری',
            'working_hours_to'   => 'پایان ساعت کاری',
        ];
    }
}
