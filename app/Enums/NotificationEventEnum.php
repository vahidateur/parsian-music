<?php

namespace App\Enums;

enum NotificationEventEnum: string
{
    case StudentCreated   = 'student_created';
    case EnrollmentCreated = 'enrollment_created';
    case SessionReminder  = 'session_reminder';
    case SessionCancelled = 'session_cancelled';
    case AttendanceMarked = 'attendance_marked';
    case PaymentDue       = 'payment_due';
    case PaymentReceived  = 'payment_received';
    case TeacherAssigned  = 'teacher_assigned';

    public function label(): string
    {
        return match ($this) {
            self::StudentCreated    => 'ثبت هنرجوی جدید',
            self::EnrollmentCreated => 'ثبت‌نام جدید',
            self::SessionReminder   => 'یادآوری جلسه',
            self::SessionCancelled  => 'لغو جلسه',
            self::AttendanceMarked  => 'ثبت حضور و غیاب',
            self::PaymentDue        => 'سررسید پرداخت',
            self::PaymentReceived   => 'دریافت پرداخت',
            self::TeacherAssigned   => 'تخصیص استاد',
        };
    }

    public function defaultPriority(): NotificationPriorityEnum
    {
        return match ($this) {
            self::SessionCancelled, self::PaymentDue => NotificationPriorityEnum::High,
            self::SessionReminder, self::PaymentReceived => NotificationPriorityEnum::Medium,
            default => NotificationPriorityEnum::Low,
        };
    }
}
