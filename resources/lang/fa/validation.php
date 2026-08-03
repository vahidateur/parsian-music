<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Localized Validation Lines (fa)
|--------------------------------------------------------------------------
|
| Persian messages for the validation rules used by the application forms.
| Any rule that is not translated here falls back to the framework locale
| configured in `APP_FALLBACK_LOCALE`, so this file can stay focused on the
| rules the project actually applies.
|
| Requirements: 6.5, 6.7
|
*/

return [

    'accepted'             => 'پذیرفتن :attribute الزامی است.',
    'after'                => ':attribute باید تاریخی پس از :date باشد.',
    'after_or_equal'       => ':attribute باید تاریخی برابر یا پس از :date باشد.',
    'array'                => ':attribute باید فهرستی از مقادیر باشد.',
    'before'               => ':attribute باید تاریخی پیش از :date باشد.',
    'before_or_equal'      => ':attribute باید تاریخی برابر یا پیش از :date باشد.',
    'between'              => [
        'array'   => ':attribute باید بین :min تا :max مورد داشته باشد.',
        'file'    => 'حجم :attribute باید بین :min تا :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min تا :max باشد.',
        'string'  => ':attribute باید بین :min تا :max کاراکتر باشد.',
    ],
    'boolean'              => 'مقدار :attribute باید بله یا خیر باشد.',
    'confirmed'            => 'تکرار :attribute با مقدار وارد‌شده مطابقت ندارد.',
    'current_password'     => 'رمز عبور فعلی نادرست است.',
    'date'                 => ':attribute باید یک تاریخ معتبر باشد.',
    'date_format'          => ':attribute باید با قالب :format مطابقت داشته باشد.',
    'different'            => ':attribute و :other باید متفاوت باشند.',
    'digits'               => ':attribute باید :digits رقم باشد.',
    'digits_between'       => ':attribute باید بین :min تا :max رقم باشد.',
    'email'                => ':attribute باید یک نشانی ایمیل معتبر باشد.',
    'enum'                 => ':attribute انتخاب‌شده نامعتبر است.',
    'exists'               => ':attribute انتخاب‌شده نامعتبر است.',
    'file'                 => ':attribute باید یک فایل باشد.',
    'filled'               => 'وارد کردن :attribute الزامی است.',
    'image'                => ':attribute باید یک تصویر باشد.',
    'in'                   => ':attribute انتخاب‌شده نامعتبر است.',
    'integer'              => ':attribute باید یک عدد صحیح باشد.',
    'lowercase'            => ':attribute باید با حروف کوچک نوشته شود.',
    'max'                  => [
        'array'   => ':attribute نباید بیش از :max مورد داشته باشد.',
        'file'    => 'حجم :attribute نباید بیش از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بیش از :max باشد.',
        'string'  => ':attribute نباید بیش از :max کاراکتر باشد.',
    ],
    'mimes'                => ':attribute باید فایلی از نوع :values باشد.',
    'mimetypes'            => ':attribute باید فایلی از نوع :values باشد.',
    'min'                  => [
        'array'   => ':attribute باید حداقل :min مورد داشته باشد.',
        'file'    => 'حجم :attribute باید حداقل :min کیلوبایت باشد.',
        'numeric' => ':attribute نباید کمتر از :min باشد.',
        'string'  => ':attribute باید حداقل :min کاراکتر باشد.',
    ],
    'not_in'               => ':attribute انتخاب‌شده نامعتبر است.',
    'not_regex'            => 'قالب :attribute نامعتبر است.',
    'numeric'              => ':attribute باید یک عدد باشد.',
    'password'             => [
        'letters'       => ':attribute باید حداقل یک حرف داشته باشد.',
        'mixed'         => ':attribute باید حداقل یک حرف بزرگ و یک حرف کوچک داشته باشد.',
        'numbers'       => ':attribute باید حداقل یک رقم داشته باشد.',
        'symbols'       => ':attribute باید حداقل یک نماد داشته باشد.',
        'uncompromised' => 'این :attribute در فهرست رمزهای افشا‌شده دیده شده است؛ مقدار دیگری انتخاب کنید.',
    ],
    'present'              => ':attribute باید ارسال شود.',
    'prohibited'           => 'ارسال :attribute مجاز نیست.',
    'regex'                => 'قالب :attribute نامعتبر است.',
    'required'             => 'وارد کردن :attribute الزامی است.',
    'required_if'          => 'وارد کردن :attribute هنگامی که :other برابر :value است الزامی است.',
    'required_with'        => 'وارد کردن :attribute هنگام ارسال :values الزامی است.',
    'required_without'     => 'وارد کردن :attribute هنگام نبود :values الزامی است.',
    'same'                 => ':attribute باید با :other یکسان باشد.',
    'size'                 => [
        'array'   => ':attribute باید :size مورد داشته باشد.',
        'file'    => 'حجم :attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر :size باشد.',
        'string'  => ':attribute باید :size کاراکتر باشد.',
    ],
    'string'               => ':attribute باید متن باشد.',
    'unique'               => 'این :attribute پیش‌تر ثبت شده است.',
    'uploaded'             => 'بارگذاری :attribute انجام نشد.',
    'url'                  => ':attribute باید یک نشانی اینترنتی معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Persian field labels used inside the messages above, so every field-level
    | error names the field the way the form labels it.
    |
    */

    'attributes' => [
        'amount'                  => 'مبلغ',
        'age'                     => 'سن',
        'assigned_to'             => 'کارشناس مسئول',
        'bio'                     => 'بیوگرافی',
        'capacity'                => 'ظرفیت',
        'current_password'        => 'رمز عبور فعلی',
        'due_date'                => 'تاریخ سررسید',
        'email'                   => 'ایمیل',
        'enrollment_id'           => 'ثبت‌نام',
        'full_name'               => 'نام و نام خانوادگی',
        'hire_date'               => 'تاریخ استخدام',
        'instrument_id'           => 'ساز',
        'is_active'               => 'وضعیت فعال بودن',
        'is_primary'              => 'ساز اصلی',
        'issue_date'              => 'تاریخ صدور',
        'items'                   => 'ردیف‌های فاکتور',
        'items.*.description'     => 'توضیح ردیف',
        'items.*.discount'        => 'تخفیف ردیف',
        'items.*.quantity'        => 'تعداد',
        'items.*.title'           => 'عنوان ردیف',
        'items.*.unit_price'      => 'قیمت واحد',
        'join_date'               => 'تاریخ عضویت',
        'method'                  => 'روش پرداخت',
        'name'                    => 'نام',
        'name_fa'                 => 'نام فارسی',
        'next_follow_up_at'       => 'پیگیری بعدی',
        'notes'                   => 'یادداشت',
        'parent_phone'            => 'تلفن والدین',
        'password'                => 'رمز عبور',
        'phone'                   => 'تلفن',
        'preferred_instrument_id' => 'ساز مورد علاقه',
        'preferred_teacher_id'    => 'استاد مورد نظر',
        'priority'                => 'اولویت',
        'reference'               => 'شماره پیگیری',
        'role'                    => 'نقش',
        'skill_level'             => 'سطح مهارت',
        'source'                  => 'منبع آشنایی',
        'start_date'              => 'تاریخ شروع',
        'status'                  => 'وضعیت',
        'student_id'              => 'هنرجو',
        'tax'                     => 'مالیات',
        'teacher_id'              => 'استاد',
    ],

];
