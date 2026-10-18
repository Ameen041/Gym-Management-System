<?php

namespace App\Support;

/**
 * Keeps the chat on Himma: finds phone and account numbers, links, emails,
 * usernames and names of other apps or payment services in a message.
 *
 *   ContactFilter::check('0933 123 456')  // 'number'
 *   ContactFilter::check('4 sets of 12')  // null
 *
 * It stops the easy ways around the platform; someone set on it can still
 * get through, which is why every blocked message is kept for the admin.
 */
class ContactFilter
{
    // This many digits in a row (spaces, dashes and dots between them count as one number) look like a phone or an account
    public const MAX_DIGITS = 7;

    // Digits written as words, after normalize(): "صفر تسعة ..." is a number too
    private const DIGIT_WORDS = [
        'صفر', 'واحد', 'وحده', 'اثنين', 'اثنان', 'اتنين', 'تنين', 'ثنين', 'ثلاثه', 'ثلاث', 'تلاته', 'تلات',
        'اربعه', 'اربع', 'خمسه', 'خمس', 'سته', 'ست', 'سبعه', 'سبع', 'ثمانيه', 'ثماني', 'ثمان', 'تمانيه',
        'تماني', 'تمان', 'تمنيه', 'تسعه', 'تسع',
        'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
    ];

    // Other messaging apps and ways to pay outside Himma, after normalize()
    private const APPS = [
        'واتس', 'وتساب', 'وتس اب', 'تلغرام', 'تلجرام', 'تلكرام', 'تليغرام', 'تيليغرام', 'تيلغرام', 'تليجرام',
        'انستا', 'انستغرام', 'انستقرام', 'انستجرام', 'فيسبوك', 'فيس بوك', 'فيسبك', 'ماسنجر', 'مسنجر', 'سناب',
        'تيك توك', 'تيكتوك', 'فايبر', 'سيغنال', 'سيجنال', 'نمرتي', 'نمرتك', 'دوت كوم', 'بايبال', 'باي بال',
    ];
    private const APPS_LATIN = [
        'whats ?app', 'whats', 'watsapp?', 'wts', 'telegram', 'insta(gram)?', 'facebook', 'fb', 'messenger',
        'snap(chat)?', 'tik ?tok', 'viber', 'signal', 'imo', 'sham ?cash', 'cash', 'pay ?pal', 'payeer',
        'binance', 'usdt', 'western union', 'dot com', 'my number', 'phone number',
    ];
    // Checked again with spaces and dots removed ("و ا ت س ا ب")
    private const APPS_SPACED = ['واتساب', 'تلغرام', 'تلجرام', 'تليغرام', 'انستغرام', 'فيسبوك', 'شامكاش', 'whatsapp', 'telegram', 'instagram', 'facebook', 'shamcash', 'snapchat'];

    /** @return string|null why the message can't be sent: number, link, contact or app; null when it is fine */
    public static function check(string $text): ?string
    {
        $t = self::normalize($text);

        if (preg_match('~[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}|(?<![a-z0-9_])@[a-z0-9_.]{3,}~u', $t)) {
            return 'contact';
        }

        if (preg_match('~https?://|www\.|(?<![\p{L}\p{N}])[\p{L}\p{N}-]+\.(com|net|org|me|io|co|app|link|ly|gl|info|biz|online|site|store|page|xyz|sy|to|gg|tv|ru|tr|ae|sa)(?![\p{L}\p{N}])~u', $t)) {
            return 'link';
        }

        $before = '(?<![\p{L}\p{N}])';
        $after = '(?![\p{L}\p{N}])';
        $apps = implode('|', array_map(fn ($w) => preg_quote($w, '~'), self::APPS));
        if (preg_match("~$apps~u", $t)
            // "كاش" (cash) and "رقم الهاتف" (phone number) as whole words, with ب / ع / ال / و in front
            || preg_match("~{$before}(و|ب|ع|ال|بال|عال)?(كاش|حواله)$after|رقم\h*(ال)?(تلفون|تليفون|هاتف|موبايل|جوال|حساب)~u", $t)
            || preg_match('~' . $before . '(' . implode('|', self::APPS_LATIN) . ')' . $after . '~u', $t)) {
            return 'app';
        }
        $squashed = preg_replace('~[^\p{L}]+~u', '', $t);
        foreach (self::APPS_SPACED as $word) {
            if (str_contains($squashed, $word)) {
                return 'app';
            }
        }

        // digits written as words count as digits
        $words = implode('|', self::DIGIT_WORDS);
        $t = preg_replace("~{$before}($words)$after~u", '0', $t);

        // a run of digits on one line, with only spaces, dashes, dots or brackets between them
        preg_match_all('/\d(?:[\h\-._()+*#|~]*\d)*/u', $t, $runs);
        foreach ($runs[0] as $run) {
            if (preg_match_all('~\d~', $run) >= self::MAX_DIGITS) {
                return 'number';
            }
        }

        // account or wallet codes: long words mixing letters and digits
        preg_match_all('~[a-z0-9]{10,}~u', $t, $codes);
        foreach ($codes[0] as $code) {
            if (preg_match_all('~\d~', $code) >= 4 && preg_match_all('~[a-z]~', $code) >= 2) {
                return 'number';
            }
        }

        return null;
    }

    /** Lower case, Western digits, no Arabic marks or invisible characters, one spelling of each Arabic letter. */
    public static function normalize(string $text): string
    {
        $text = strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي', 'گ' => 'ك', 'ڤ' => 'ف',
            '＠' => '@', '．' => '.',
        ]);
        // tashkeel, tatweel, zero-width and direction marks
        $text = preg_replace('~[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]~u', '', $text);

        return mb_strtolower($text);
    }
}
