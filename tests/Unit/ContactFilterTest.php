<?php

namespace Tests\Unit;

use App\Support\ContactFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContactFilterTest extends TestCase
{
    #[DataProvider('blocked')]
    public function test_contact_details_are_found(string $text, string $reason): void
    {
        $this->assertSame($reason, ContactFilter::check($text), $text);
    }

    public static function blocked(): array
    {
        return [
            'phone with spaces'       => ['0933 123 456', 'number'],
            'phone in Arabic digits'  => ['٠٩٣٣١٢٣٤٥٦', 'number'],
            'phone with dashes'       => ['0933-123-456', 'number'],
            'mixed digits'            => ['09٣٣ ١٢٣ ٤٥٦', 'number'],
            'country code'            => ['+963 933 123 456', 'number'],
            'digit per space'         => ['0 9 3 3 1 2 3', 'number'],
            'digits as words'         => ['صفر تسعة تلاتة تلاتة واحد اتنين تلاتة', 'number'],
            'wallet code'             => ['SY12AB34CD56EF', 'number'],
            'whatsapp in Arabic'      => ['ابعتلي ع الواتس', 'app'],
            'whatsapp joined'         => ['عالواتساب', 'app'],
            'whatsapp spaced out'     => ['و ا ت س ا ب', 'app'],
            'telegram'                => ['تلغرام', 'app'],
            'instagram'               => ['insta: coach', 'app'],
            'sham cash'               => ['حولي عشام كاش', 'app'],
            'cash'                    => ['ادفعلي كاش', 'app'],
            'phone number words'      => ['رقم الموبايل تبعي', 'app'],
            'your number (dialect)'   => ['بدي نمرتك', 'app'],
            'link'                    => ['www.site.com', 'link'],
            'short link'              => ['t.me/coach', 'link'],
            'email'                   => ['coach@gmail.com', 'contact'],
            'username'                => ['@coach_ahmad', 'contact'],
        ];
    }

    #[DataProvider('allowed')]
    public function test_training_talk_is_fine(string $text): void
    {
        $this->assertNull(ContactFilter::check($text), $text);
    }

    public static function allowed(): array
    {
        return array_map(fn ($t) => [$t], [
            '4 sets of 12', '3x10 بوزن 60 كغ', '12, 10, 8, 6', 'الأوزان: 60، 70، 80، 90', 'وزني صار 72.5 الحمد لله',
            'تمرين الساعة 10:30', '18/10/2026', 'شو اتسمى التمرين؟', 'ست جلسات بالأسبوع', 'بعتلك اشي جديد',
            'رقمك القياسي ممتاز', 'Good job, one more set', 'ايموجي حلو', '100 كغ ديدلفت و 120 سكوات', 'خمس مجموعات',
        ]);
    }
}
