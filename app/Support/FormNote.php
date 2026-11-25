<?php

namespace App\Support;

use App\Models\Exercise;

/**
 * The detail a picture can't be trusted to show: which way the palms face, how wide the hands are, one arm or both,
 * one leg after the other. The pictures (Gemini) get the movement right but often not the hands, so this fixed line,
 * written once and always exact, says it under the picture on the exercise page and in the trainee's plan.
 */
class FormNote
{
    /** The icon of each kind of note. */
    private const ICONS = [
        'grip'      => 'fa-hand',
        'close'     => 'fa-compress',
        'wide'      => 'fa-arrows-left-right',
        'turn'      => 'fa-rotate',
        'alternate' => 'fa-right-left',
        'one_arm'   => 'fa-hand-point-up',
        'one_leg'   => 'fa-shoe-prints',
        'one_side'  => 'fa-repeat',
    ];

    /** Exercise name => notes: [kind, label, Arabic label, text, Arabic text]. */
    private const NOTES = [
        // ---- back
        'Pull-Up' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing away from you, hands a little wider than your shoulders.',
            'باطن الكفين للأمام بعيداً عنك، واليدان أعرض من الكتفين قليلاً.']],
        'Chin-Up' => [['grip', 'Reverse grip', 'قبضة عكسية',
            'Palms facing you, hands shoulder-width apart.',
            'باطن الكفين باتجاه وجهك، واليدان بعرض الكتفين.']],
        'Lat Pulldown' => [['wide', 'Wide grip', 'قبضة واسعة',
            'Hands wider than your shoulders, palms facing away from you.',
            'اليدان أعرض من الكتفين، وباطن الكفين للأمام بعيداً عنك.']],
        'Close-Grip Lat Pulldown' => [['close', 'Close grip', 'قبضة ضيقة',
            'Hands shoulder-width apart or a little closer, palms facing away from you.',
            'اليدان بعرض الكتفين أو أضيق قليلاً، وباطن الكفين للأمام بعيداً عنك.']],
        'Behind-the-Neck Lat Pulldown' => [['wide', 'Wide grip', 'قبضة واسعة',
            'Palms facing away from you, hands wide on the bar. Pull the bar down behind your neck.',
            'باطن الكفين للأمام واليدان متباعدتان على البار، والسحب إلى خلف الرقبة.']],
        'V-Bar Lat Pulldown' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other on the V-bar handle.',
            'باطن الكفين متقابلان على مقبض المثلث (V).']],
        'T-Bar Row' => [['grip', 'Both hands on the handle', 'اليدان معاً على المقبض',
            'The bar runs between your legs; both hands hold the handle, palms facing each other.',
            'البار بين رجليك، واليدان معاً على المقبض وباطن الكفين متقابلان.']],
        'Bent-Over Barbell Row' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing your legs, hands a little wider than your shoulders.',
            'باطن الكفين باتجاه رجليك، واليدان أعرض من الكتفين قليلاً.']],
        'Reverse-Grip Barbell Row' => [['grip', 'Reverse grip', 'قبضة عكسية',
            'Palms facing forward, away from your legs, hands shoulder-width apart.',
            'باطن الكفين للأمام بعيداً عن رجليك، واليدان بعرض الكتفين.']],
        'Bent-Over Dumbbell Row' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other; both arms pull together.',
            'باطن الكفين متقابلان، والذراعان تسحبان معاً.']],
        'Chest-Supported Dumbbell Row' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other; both arms pull together.',
            'باطن الكفين متقابلان، والذراعان تسحبان معاً.']],
        'One-Arm Dumbbell Row' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'Your other hand and knee rest on the bench. Finish the set, then switch arms.',
            'اليد الأخرى والركبة على البنش. أكمل المجموعة ثم بدّل اليد.']],
        'Seated One-Arm Cable Row' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'One arm pulls; the other rests on your knee and does not move. Finish the set, then switch arms.',
            'يد واحدة تسحب والأخرى مرتاحة على الركبة لا تتحرك. أكمل المجموعة ثم بدّل اليد.']],

        // ---- chest and shoulders
        'Close-Grip Bench Press' => [['close', 'Close grip', 'قبضة ضيقة',
            'Hands shoulder-width apart, no closer, elbows close to your body.',
            'اليدان بعرض الكتفين لا أضيق، والمرفقان قريبان من جسمك.']],
        'Close-Grip Push-Up' => [['close', 'Hands close together', 'اليدان متقاربتان',
            'Hands under your chest, a little narrower than your shoulders, elbows close to your sides.',
            'اليدان تحت صدرك أضيق من الكتفين قليلاً، والمرفقان ملاصقان لجسمك.']],
        'Arnold Press' => [['turn', 'Turn your palms', 'لفّ الكفين',
            'Start with your palms facing you and turn them to face forward as you press up.',
            'ابدأ وباطن الكفين باتجاهك، ولفّهما للأمام وأنت ترفع.']],
        'Seated Behind-the-Neck Press' => [['wide', 'Wide grip', 'قبضة واسعة',
            'Palms facing forward, hands wider than your shoulders. The bar comes down behind your neck.',
            'باطن الكفين للأمام واليدان أعرض من الكتفين، والبار ينزل خلف الرقبة.']],
        'Barbell Front Raise' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing down, hands shoulder-width apart.',
            'باطن الكفين للأسفل، واليدان بعرض الكتفين.']],
        'Barbell Upright Row' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing your body, hands about shoulder-width apart.',
            'باطن الكفين باتجاه جسمك، واليدان بعرض الكتفين تقريباً.']],
        'Single-Arm Cable Lateral Raise' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'Hold the handle in the hand farther from the machine. Finish the set, then switch sides.',
            'امسك المقبض باليد البعيدة عن الجهاز. أكمل المجموعة ثم بدّل الجهة.']],
        'Lying Side Lateral Raise' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'Lie on your side; the lower arm supports your head. Finish the set, then switch sides.',
            'استلقِ على جنبك واسند رأسك باليد السفلى. أكمل المجموعة ثم بدّل الجهة.']],

        // ---- biceps
        'Barbell Biceps Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing up, hands shoulder-width apart.',
            'باطن الكفين للأعلى، واليدان بعرض الكتفين.']],
        'EZ-Bar Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing up on the angled grips.',
            'باطن الكفين للأعلى على المقابض المائلة.']],
        'Barbell Preacher Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing up, hands shoulder-width apart.',
            'باطن الكفين للأعلى، واليدان بعرض الكتفين.']],
        'Standing Cable Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing up, hands shoulder-width apart.',
            'باطن الكفين للأعلى، واليدان بعرض الكتفين.']],
        'Overhead Cable Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'A handle in each hand, palms facing up, elbows at shoulder height.',
            'مقبض بكل يد وباطن الكفين للأعلى، والمرفقان بمستوى الكتفين.']],
        'Reverse-Grip Barbell Curl' => [['grip', 'Reverse grip', 'قبضة عكسية',
            'Palms facing down the whole movement, the opposite of a normal curl.',
            'باطن الكفين للأسفل طوال الحركة، عكس الكيرل العادي.']],
        'Hammer Curl' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing your body, thumbs up, the whole movement.',
            'باطن الكفين باتجاه جسمك والإبهام للأعلى طوال الحركة.']],
        'Cable Rope Hammer Curl' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other on the rope, thumbs up, the whole movement.',
            'باطن الكفين متقابلان على الحبل والإبهام للأعلى طوال الحركة.']],
        'Hammer Preacher Curl' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other, thumbs up, the whole movement.',
            'باطن الكفين متقابلان والإبهام للأعلى طوال الحركة.']],
        'Alternating Dumbbell Curl' => [['alternate', 'Alternating', 'بالتناوب',
            'One arm curls while the other waits at your side, then the other: one arm after the other.',
            'تصعد يد واحدة والأخرى تنتظر بجانبك، ثم العكس: يد بعد يد.']],
        'Concentration Curl' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'Your elbow rests on the inside of your thigh; the other hand rests on the other thigh. Finish the set, then switch arms.',
            'المرفق على باطن فخذك، واليد الأخرى على الفخذ الآخر. أكمل المجموعة ثم بدّل اليد.']],

        // ---- triceps
        'Straight-Bar Triceps Pushdown' => [['grip', 'Normal grip', 'قبضة عادية',
            'Palms facing down, hands about shoulder-width apart.',
            'باطن الكفين للأسفل، واليدان بعرض الكتفين تقريباً.']],
        'Reverse-Grip Triceps Pushdown' => [['grip', 'Reverse grip', 'قبضة عكسية',
            'Palms facing up, hands shoulder-width apart, elbows fixed at your sides.',
            'باطن الكفين للأعلى، واليدان بعرض الكتفين، والمرفقان ثابتان بجانبك.']],
        'Single-Arm Reverse-Grip Cable Pushdown' => [['one_arm', 'One arm, reverse grip', 'يد واحدة بقبضة عكسية',
            'Palm facing up; the free hand rests on your hip or the machine. Finish the set, then switch arms.',
            'باطن الكف للأعلى، واليد الأخرى على خصرك أو على الجهاز. أكمل المجموعة ثم بدّل اليد.']],
        'Triceps Rope Pushdown' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'Palms facing each other on the rope; spread the ends apart at the bottom.',
            'باطن الكفين متقابلان على الحبل، وافتح طرفيه في الأسفل.']],
        'Lying Dumbbell Triceps Extension' => [['grip', 'Neutral grip', 'قبضة متقابلة',
            'A dumbbell in each hand, palms facing each other.',
            'دمبل بكل يد، وباطن الكفين متقابلان.']],
        'Standing Overhead Dumbbell Extension' => [['grip', 'One dumbbell, both hands', 'دمبل واحد باليدين',
            'Both hands hold one dumbbell under its top plate.',
            'اليدان معاً تمسكان دمبلاً واحداً من تحت قرصه العلوي.']],
        'Dumbbell Triceps Kickback' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'Your other hand and knee rest on the bench; the elbow stays fixed at your side. Finish the set, then switch arms.',
            'اليد الأخرى والركبة على البنش، والمرفق ثابت بجانبك. أكمل المجموعة ثم بدّل اليد.']],

        // ---- forearms
        'Barbell Wrist Curl' => [['grip', 'Normal grip', 'قبضة عادية',
            'Forearms resting on the bench, palms facing up.',
            'الساعدان على البنش، وباطن الكفين للأعلى.']],
        'Reverse Barbell Wrist Curl' => [['grip', 'Reverse grip', 'قبضة عكسية',
            'Forearms resting on the bench, palms facing down.',
            'الساعدان على البنش، وباطن الكفين للأسفل.']],

        // ---- legs and glutes
        'Dumbbell Forward Lunge' => [['alternate', 'Alternating legs', 'بالتناوب',
            'One leg after the other, or all the reps on one leg and then the other.',
            'رجل بعد رجل، أو كل التكرارات على رجل ثم الأخرى.']],
        'Barbell Walking Lunge' => [['alternate', 'Alternating legs', 'بالتناوب',
            'One leg after the other as you walk forward.',
            'رجل بعد رجل وأنت تمشي للأمام.']],
        'Dumbbell Split Squat' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'The same leg stays in front for the whole set, then switch.',
            'تبقى الرجل نفسها في الأمام طوال المجموعة، ثم بدّل.']],
        'Dumbbell Step-Up' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'All the reps on one leg, then switch.',
            'كل التكرارات على رجل واحدة، ثم بدّل.']],
        'Standing Single-Leg Curl' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'One leg works. Finish the set, then switch legs.',
            'تعمل رجل واحدة. أكمل المجموعة ثم بدّل الرجل.']],
        'Single-Leg Calf Raise' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'Stand on one foot on the step and hold the wall with your free hand. Finish the set, then switch.',
            'قف على قدم واحدة على الدرجة وامسك الحائط باليد الأخرى. أكمل المجموعة ثم بدّل.']],
        'Single-Leg Glute Bridge' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'One foot on the floor, the other leg raised. Finish the set, then switch.',
            'قدم واحدة على الأرض والرجل الأخرى مرفوعة. أكمل المجموعة ثم بدّل.']],
        'Cable Glute Kickback' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'One leg pushes back against the cable. Finish the set, then switch legs.',
            'رجل واحدة تدفع للخلف ضد الكابل. أكمل المجموعة ثم بدّل الرجل.']],
        'Bodyweight Glute Kickback' => [['one_leg', 'One leg at a time', 'رجل واحدة',
            'One leg lifts back and up. Finish the set, then switch legs.',
            'رجل واحدة ترتفع للخلف وللأعلى. أكمل المجموعة ثم بدّل الرجل.']],
        'Single-Arm Kettlebell Swing' => [['one_arm', 'One arm at a time', 'يد واحدة',
            'One hand on the handle, the other relaxed at your side. Finish the set, then switch arms.',
            'يد واحدة على المقبض والأخرى مرتاحة بجانبك. أكمل المجموعة ثم بدّل اليد.']],

        // ---- abs
        'Bicycle Crunch' => [['alternate', 'Alternating sides', 'بالتناوب',
            'Elbow toward the opposite knee, one side after the other.',
            'المرفق نحو الركبة المعاكسة، جهة بعد جهة.']],
        'Dead Bug' => [['alternate', 'Alternating sides', 'بالتناوب',
            'One arm and the opposite leg together, then the other side.',
            'يد والرجل المعاكسة معاً، ثم الجهة الأخرى.']],
        'Mountain Climber' => [['alternate', 'Alternating legs', 'بالتناوب',
            'One knee after the other, at a steady rhythm.',
            'ركبة بعد ركبة بإيقاع ثابت.']],
        'Russian Twist' => [['alternate', 'Side to side', 'من جهة لجهة',
            'Turn to one side, then to the other.',
            'لفّ لجهة ثم للجهة الأخرى.']],
        'Side Plank' => [['one_side', 'One side, then the other', 'جهة ثم الأخرى',
            'Hold on one side, then repeat on the other.',
            'اثبت على جهة، ثم كرّر على الجهة الأخرى.']],
        'Side-Lying Oblique Crunch' => [['one_side', 'One side, then the other', 'جهة ثم الأخرى',
            'Finish your reps on one side, then switch.',
            'أكمل التكرارات على جهة، ثم بدّل.']],
        'Cable Wood Chop' => [['one_side', 'One side, then the other', 'جهة ثم الأخرى',
            'Both hands on the handle. Finish your reps on one side, then switch.',
            'اليدان معاً على المقبض. أكمل التكرارات على جهة، ثم بدّل.']],
    ];

    /** The notes of the exercise in the current language: [['icon' => ..., 'label' => ..., 'text' => ...], ...]. */
    public static function for(Exercise $exercise): array
    {
        $ar = app()->isLocale('ar');

        return array_map(fn (array $note) => [
            'icon'  => self::ICONS[$note[0]],
            'label' => $ar ? $note[2] : $note[1],
            'text'  => $ar ? $note[4] : $note[3],
        ], self::NOTES[$exercise->name] ?? []);
    }

    /** Names of the exercises that have a note. */
    public static function names(): array
    {
        return array_keys(self::NOTES);
    }
}
