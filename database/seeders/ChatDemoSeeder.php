<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo of the chat between trainer@demo.com and rami@demo.com.
 * The last message from Rami is unread, so the coach sees the badge.
 */
class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $coach = User::where('email', 'trainer@demo.com')->first();
        $trainee = User::where('email', 'rami@demo.com')->first();
        if (!$coach || !$trainee) {
            return;
        }

        $chat = Conversation::between($coach->id, $trainee->id);
        if ($chat->messages()->exists()) {
            return;
        }

        $lines = [
            // [days ago, time, from coach?, text]
            [3, '18:10', true, 'أهلين رامي 👋 شفت متابعتك، نزلت نص كيلو هالأسبوع، ممتاز.'],
            [3, '18:12', true, 'بس انتبه للنوم، حاول تنام ٧ ساعات على الأقل.'],
            [3, '21:40', false, 'تمام كوتش، الأسبوع الجاي عندي امتحانات بس رح حاول.'],
            [1, '09:05', false, 'سؤال: بقدر بدّل السكوات بالليغ برس؟ ركبتي عم توجعني شوي.'],
            [1, '12:30', true, 'إي بدّلها هالأسبوع، ٤ مجموعات: 12، 10، 10، 8 بوزن خفيف. وإذا ضل الوجع خبرني.'],
            [0, '08:15', false, 'جربت الليغ برس مبارح، ما في وجع 💪 شكراً!'],
        ];

        foreach ($lines as [$daysAgo, $time, $fromCoach, $text]) {
            // times are written in Damascus time, stored in UTC
            $at = now(config('app.display_timezone'))->subDays($daysAgo)->setTimeFromTimeString($time)->utc();
            $isLast = $text === end($lines)[3];
            $chat->messages()->create([
                'sender_id' => $fromCoach ? $coach->id : $trainee->id,
                'body'      => $text,
                'read_at'   => $isLast ? null : $at->copy()->addMinutes(20),
            ])->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }

        $chat->update(['last_message_at' => $chat->messages()->max('created_at')]);
    }
}
