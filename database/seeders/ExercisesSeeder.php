<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

/**
 * Starter weight-training library. Uses firstOrCreate so re-running the
 * seeder on every deploy never overwrites edits made from the admin panel.
 * Video links are added by the admin (Admin → Exercises).
 */
class ExercisesSeeder extends Seeder
{
    public function run(): void
    {
        $arabic = $this->arabic();

        foreach ($this->exercises() as $exercise) {
            $model = Exercise::firstOrCreate(['name' => $exercise['name']], $exercise + ['is_active' => true]);

            // Fill Arabic texts only where they are still empty (keeps admin edits)
            foreach ($arabic[$exercise['name']] ?? [] as $field => $value) {
                if (blank($model->{$field})) {
                    $model->{$field} = $value;
                }
            }
            $model->save();
        }
    }

    private function exercises(): array
    {
        return [
            // Chest
            [
                'name' => 'Barbell Bench Press',
                'muscle_group' => 'chest', 'equipment' => 'Barbell, Flat bench', 'difficulty' => 'intermediate',
                'description' => 'The classic compound movement for building chest size and pressing strength, also working the front shoulders and triceps.',
                'instructions' => "Lie flat on the bench with your eyes under the bar and feet planted on the floor.\nGrip the bar slightly wider than shoulder width and squeeze your shoulder blades together.\nUnrack the bar and hold it above your shoulders with arms straight.\nLower the bar under control to your mid-chest, elbows at roughly 45° from your body.\nPress the bar back up until your arms are straight, breathing out as you push.",
                'tips' => "Keep your shoulder blades retracted and your feet on the floor throughout.\nDon't bounce the bar off your chest.\nUse a spotter or safety bars when lifting heavy.",
            ],
            [
                'name' => 'Incline Dumbbell Press',
                'muscle_group' => 'chest', 'equipment' => 'Dumbbells, Incline bench', 'difficulty' => 'beginner',
                'description' => 'Targets the upper chest with a greater range of motion than the barbell version.',
                'instructions' => "Set the bench to a 30–45° incline and sit with a dumbbell on each thigh.\nLie back and bring the dumbbells to chest level, palms facing forward.\nPress the dumbbells up until your arms are extended over your upper chest.\nLower slowly until you feel a stretch in the chest, then press again.",
                'tips' => "An incline steeper than 45° shifts the work to the shoulders.\nKeep your wrists stacked over your elbows.",
            ],
            [
                'name' => 'Cable Chest Fly',
                'muscle_group' => 'chest', 'equipment' => 'Cable machine', 'difficulty' => 'beginner',
                'description' => 'An isolation movement that keeps constant tension on the chest muscles.',
                'instructions' => "Set both pulleys at shoulder height and grab a handle in each hand.\nStep forward into a staggered stance with a slight bend in your elbows.\nBring your hands together in front of your chest in a wide hugging arc.\nSqueeze the chest for a second, then return slowly to the start.",
                'tips' => "Keep the elbow angle fixed — this is not a press.\nControl the return; don't let the weight pull your arms back.",
            ],
            [
                'name' => 'Push-Up',
                'muscle_group' => 'chest', 'equipment' => 'Bodyweight', 'difficulty' => 'beginner',
                'description' => 'A bodyweight pressing exercise for chest, shoulders and triceps that also trains core stability.',
                'instructions' => "Place your hands slightly wider than your shoulders and extend your legs behind you.\nKeep a straight line from head to heels.\nLower your chest towards the floor, elbows at about 45°.\nPush back up to the starting position.",
                'tips' => "Don't let your hips sag or pike up.\nDo them on your knees or against a bench if full push-ups are too hard.",
            ],

            // Back
            [
                'name' => 'Deadlift',
                'muscle_group' => 'back', 'equipment' => 'Barbell', 'difficulty' => 'advanced',
                'description' => 'A full-body strength lift that mainly builds the lower back, glutes and hamstrings.',
                'instructions' => "Stand with your mid-foot under the bar, feet hip-width apart.\nHinge at the hips and grip the bar just outside your legs.\nBring your shins to the bar, lift your chest and flatten your back.\nPush the floor away with your legs and stand up, keeping the bar close to your body.\nLock out by squeezing your glutes, then lower the bar the same way.",
                'tips' => "Never round your lower back.\nKeep the bar in contact with your legs the whole way.\nStart light and master the technique before adding weight.",
            ],
            [
                'name' => 'Lat Pulldown',
                'muscle_group' => 'back', 'equipment' => 'Cable machine', 'difficulty' => 'beginner',
                'description' => 'Builds back width by working the latissimus dorsi; a great pull-up alternative.',
                'instructions' => "Sit with your thighs locked under the pads and grab the bar slightly wider than shoulder width.\nLean back slightly and lift your chest.\nPull the bar down to your upper chest by driving your elbows down.\nPause, then let the bar rise slowly until your arms are straight.",
                'tips' => "Don't pull the bar behind your neck.\nAvoid swinging your torso to move the weight.",
            ],
            [
                'name' => 'Bent-Over Barbell Row',
                'muscle_group' => 'back', 'equipment' => 'Barbell', 'difficulty' => 'intermediate',
                'description' => 'A compound row that builds thickness in the mid-back and lats.',
                'instructions' => "Hold the bar with an overhand grip, slightly wider than shoulder width.\nHinge forward until your torso is about 45° to the floor, knees slightly bent.\nPull the bar to your lower ribs, squeezing your shoulder blades together.\nLower the bar under control until your arms are straight.",
                'tips' => "Keep your back flat and neck neutral.\nDon't use momentum from your hips.",
            ],
            [
                'name' => 'Seated Cable Row',
                'muscle_group' => 'back', 'equipment' => 'Cable machine', 'difficulty' => 'beginner',
                'description' => 'A controlled rowing movement for the mid-back and lats.',
                'instructions' => "Sit with your feet on the platform and knees slightly bent.\nGrab the handle with arms extended and back straight.\nPull the handle to your stomach, driving your elbows back.\nSqueeze your shoulder blades, then extend your arms slowly.",
                'tips' => "Keep your torso upright — don't rock back and forth.",
            ],

            // Shoulders
            [
                'name' => 'Overhead Dumbbell Press',
                'muscle_group' => 'shoulders', 'equipment' => 'Dumbbells', 'difficulty' => 'beginner',
                'description' => 'Builds strong, rounded shoulders, mainly the front and side deltoids.',
                'instructions' => "Sit on a bench with back support, holding dumbbells at shoulder height, palms forward.\nPress the dumbbells overhead until your arms are straight.\nLower them slowly back to shoulder height.",
                'tips' => "Don't arch your lower back excessively.\nKeep your core tight throughout.",
            ],
            [
                'name' => 'Dumbbell Lateral Raise',
                'muscle_group' => 'shoulders', 'equipment' => 'Dumbbells', 'difficulty' => 'beginner',
                'description' => 'Isolates the side deltoids for wider-looking shoulders.',
                'instructions' => "Stand holding a dumbbell in each hand at your sides.\nWith a slight bend in your elbows, raise the dumbbells out to the sides.\nStop when your arms are parallel to the floor.\nLower slowly to the starting position.",
                'tips' => "Use light weights and strict form.\nLead with your elbows, not your hands.\nDon't shrug your shoulders up.",
            ],

            // Arms
            [
                'name' => 'Barbell Biceps Curl',
                'muscle_group' => 'biceps', 'equipment' => 'Barbell', 'difficulty' => 'beginner',
                'description' => 'The fundamental exercise for building biceps size and strength.',
                'instructions' => "Stand holding the bar with an underhand grip at shoulder width.\nKeep your elbows close to your sides.\nCurl the bar up towards your shoulders.\nSqueeze at the top, then lower slowly.",
                'tips' => "Don't swing your body to lift the weight.\nControl the lowering phase.",
            ],
            [
                'name' => 'Hammer Curl',
                'muscle_group' => 'biceps', 'equipment' => 'Dumbbells', 'difficulty' => 'beginner',
                'description' => 'Works the biceps and brachialis along with the forearms using a neutral grip.',
                'instructions' => "Hold a dumbbell in each hand with palms facing your body.\nKeeping your elbows fixed, curl the dumbbells up.\nPause at the top, then lower slowly.",
                'tips' => "Keep your wrists straight throughout.",
            ],
            [
                'name' => 'Triceps Rope Pushdown',
                'muscle_group' => 'triceps', 'equipment' => 'Cable machine', 'difficulty' => 'beginner',
                'description' => 'An isolation movement that targets all three heads of the triceps.',
                'instructions' => "Attach a rope to a high pulley and hold it with palms facing each other.\nPin your elbows to your sides.\nPush the rope down until your arms are fully straight, spreading the ends apart.\nReturn slowly until your forearms are just above parallel.",
                'tips' => "Only your forearms should move.\nDon't lean over the rope to push with your body weight.",
            ],
            [
                'name' => 'Close-Grip Bench Press',
                'muscle_group' => 'triceps', 'equipment' => 'Barbell, Flat bench', 'difficulty' => 'intermediate',
                'description' => 'A compound pressing variation that puts more emphasis on the triceps.',
                'instructions' => "Lie on a flat bench and grip the bar at shoulder width.\nLower the bar to your lower chest, keeping your elbows close to your body.\nPress back up until your arms are straight.",
                'tips' => "A grip that is too narrow strains the wrists — shoulder width is enough.",
            ],

            // Legs & glutes
            [
                'name' => 'Barbell Back Squat',
                'muscle_group' => 'legs', 'equipment' => 'Barbell, Squat rack', 'difficulty' => 'intermediate',
                'description' => 'The king of leg exercises, building the quads, glutes and overall strength.',
                'instructions' => "Place the bar on your upper back and stand with feet shoulder-width apart.\nBrace your core and take a deep breath.\nSit your hips back and down, keeping your chest up and knees tracking over your toes.\nGo down until your thighs are at least parallel to the floor.\nDrive through your whole foot to stand back up.",
                'tips' => "Keep your heels on the ground.\nDon't let your knees cave inward.\nUse safety bars in the rack.",
            ],
            [
                'name' => 'Leg Press',
                'muscle_group' => 'legs', 'equipment' => 'Leg press machine', 'difficulty' => 'beginner',
                'description' => 'A machine exercise for building the quads and glutes with back support.',
                'instructions' => "Sit in the machine with your feet shoulder-width apart on the platform.\nRelease the safety handles.\nLower the platform until your knees reach about 90°.\nPush the platform back up without locking your knees.",
                'tips' => "Don't let your lower back lift off the seat.\nNever fully lock out your knees.",
            ],
            [
                'name' => 'Romanian Deadlift',
                'muscle_group' => 'legs', 'equipment' => 'Barbell', 'difficulty' => 'intermediate',
                'description' => 'A hip-hinge movement that strongly targets the hamstrings and glutes.',
                'instructions' => "Stand holding the bar at hip level with a slight bend in your knees.\nPush your hips back while sliding the bar down your thighs.\nLower until you feel a strong stretch in your hamstrings.\nDrive your hips forward to return to standing.",
                'tips' => "Keep your back flat — the movement comes from the hips.\nKeep the bar close to your legs.",
            ],
            [
                'name' => 'Barbell Hip Thrust',
                'muscle_group' => 'glutes', 'equipment' => 'Barbell, Bench', 'difficulty' => 'intermediate',
                'description' => 'One of the most effective exercises for building the glutes.',
                'instructions' => "Sit on the floor with your upper back against a bench and a padded bar over your hips.\nPlant your feet hip-width apart.\nDrive your hips up until your body forms a straight line from shoulders to knees.\nSqueeze your glutes at the top, then lower under control.",
                'tips' => "Tuck your chin and keep your ribs down at the top.\nUse a bar pad for comfort.",
            ],

            // Abs
            [
                'name' => 'Plank',
                'muscle_group' => 'abs', 'equipment' => 'Bodyweight', 'difficulty' => 'beginner',
                'description' => 'An isometric core exercise that builds stability and endurance.',
                'instructions' => "Rest on your forearms with elbows under your shoulders.\nExtend your legs and lift your body off the floor.\nHold a straight line from head to heels while breathing normally.",
                'tips' => "Don't let your hips drop.\nSqueeze your glutes to protect your lower back.",
            ],
            [
                'name' => 'Hanging Leg Raise',
                'muscle_group' => 'abs', 'equipment' => 'Pull-up bar', 'difficulty' => 'advanced',
                'description' => 'A challenging exercise for the lower abs and hip flexors.',
                'instructions' => "Hang from a pull-up bar with arms straight.\nKeeping your legs together, raise them until they are parallel to the floor or higher.\nLower slowly without swinging.",
                'tips' => "Bend your knees to make it easier.\nAvoid using momentum.",
            ],
        ];
    }

    private function arabic(): array
    {
        return [
            'Barbell Bench Press' => [
                'name_ar' => 'ضغط البنش بالبار',
                'equipment_ar' => 'بار، بنش مستوي',
                'description_ar' => 'التمرين المركب الأساسي لبناء حجم الصدر وقوة الدفع، ويعمل أيضاً على الكتف الأمامي والترايسبس.',
                'instructions_ar' => "استلقِ على البنش بحيث تكون عيناك تحت البار وقدماك ثابتتان على الأرض.\nامسك البار بقبضة أعرض قليلاً من عرض الكتفين واضغط لوحي الكتف للخلف.\nارفع البار من الحامل وثبّته فوق كتفيك والذراعان ممدودتان.\nأنزل البار ببطء وتحكم إلى منتصف الصدر، مع إبقاء المرفقين بزاوية 45° تقريباً عن الجسم.\nادفع البار للأعلى حتى تستقيم ذراعاك، مع إخراج النفس أثناء الدفع.",
                'tips_ar' => "حافظ على لوحي الكتف مشدودين للخلف وقدميك على الأرض طوال التمرين.\nلا ترتدّ بالبار عن صدرك.\nاستعن بمساعد أو بحواجز الأمان عند رفع أوزان ثقيلة.",
            ],
            'Incline Dumbbell Press' => [
                'name_ar' => 'ضغط دمبل على بنش مائل',
                'equipment_ar' => 'دمبلز، بنش مائل',
                'description_ar' => 'يستهدف الجزء العلوي من الصدر بمدى حركة أكبر من نسخة البار.',
                'instructions_ar' => "اضبط البنش على ميل بين 30 و45 درجة واجلس مع دمبل على كل فخذ.\nاستلقِ للخلف وارفع الدمبلز إلى مستوى الصدر وراحتا اليدين للأمام.\nادفع الدمبلز للأعلى حتى تمتد ذراعاك فوق أعلى الصدر.\nأنزلهما ببطء حتى تشعر بتمدد في الصدر، ثم ادفع من جديد.",
                'tips_ar' => "الميل الأكبر من 45 درجة ينقل الحمل إلى الأكتاف.\nأبقِ الرسغين فوق المرفقين مباشرة.",
            ],
            'Cable Chest Fly' => [
                'name_ar' => 'تفتيح صدر بالكابل',
                'equipment_ar' => 'جهاز كابل',
                'description_ar' => 'تمرين عزل يحافظ على شد مستمر على عضلات الصدر.',
                'instructions_ar' => "اضبط البكرتين على مستوى الكتف وامسك مقبضاً بكل يد.\nتقدّم خطوة للأمام بوقفة متباعدة مع ثني بسيط في المرفقين.\nاجمع يديك أمام صدرك بحركة قوسية واسعة كأنك تعانق.\nاعصر الصدر لثانية ثم عد ببطء إلى البداية.",
                'tips_ar' => "ثبّت زاوية المرفق، فهذا ليس تمرين ضغط.\nتحكّم في العودة ولا تدع الوزن يسحب ذراعيك للخلف.",
            ],
            'Push-Up' => [
                'name_ar' => 'تمرين الضغط',
                'equipment_ar' => 'وزن الجسم',
                'description_ar' => 'تمرين دفع بوزن الجسم للصدر والأكتاف والترايسبس، ويقوّي ثبات عضلات الجذع أيضاً.',
                'instructions_ar' => "ضع يديك أعرض قليلاً من كتفيك ومدّ رجليك للخلف.\nحافظ على خط مستقيم من الرأس حتى الكعبين.\nأنزل صدرك نحو الأرض مع إبقاء المرفقين بزاوية 45° تقريباً.\nادفع للأعلى لتعود إلى وضع البداية.",
                'tips_ar' => "لا تدع الوركين يهبطان أو يرتفعان.\nنفّذه على الركبتين أو مستنداً إلى بنش إذا كان الضغط الكامل صعباً.",
            ],
            'Deadlift' => [
                'name_ar' => 'الرفعة الميتة',
                'equipment_ar' => 'بار',
                'description_ar' => 'رفعة قوة للجسم كاملاً تبني أسفل الظهر والمؤخرة وأوتار الركبة بشكل أساسي.',
                'instructions_ar' => "قف بحيث يكون منتصف القدم تحت البار والقدمان بعرض الورك.\nاثنِ الجسم من الورك وامسك البار خارج الساقين مباشرة.\nقرّب الساقين من البار، ارفع صدرك واجعل ظهرك مستقيماً.\nادفع الأرض بقدميك وقف للأعلى مع إبقاء البار قريباً من جسمك.\nأكمل الحركة بشد عضلات المؤخرة، ثم أنزل البار بنفس الطريقة.",
                'tips_ar' => "لا تقوّس أسفل ظهرك أبداً.\nأبقِ البار ملامساً لرجليك طوال الحركة.\nابدأ بوزن خفيف وأتقن التكنيك قبل زيادة الوزن.",
            ],
            'Lat Pulldown' => [
                'name_ar' => 'سحب أمامي على الجهاز',
                'equipment_ar' => 'جهاز كابل',
                'description_ar' => 'يبني عرض الظهر عبر تمرين العضلة الظهرية العريضة؛ بديل ممتاز للعقلة.',
                'instructions_ar' => "اجلس مع تثبيت فخذيك تحت الوسائد وامسك البار بقبضة أعرض قليلاً من الكتفين.\nمِل للخلف قليلاً وارفع صدرك.\nاسحب البار إلى أعلى الصدر بدفع المرفقين للأسفل.\nتوقف لحظة ثم اترك البار يصعد ببطء حتى تستقيم ذراعاك.",
                'tips_ar' => "لا تسحب البار خلف الرقبة.\nتجنّب أرجحة الجذع لتحريك الوزن.",
            ],
            'Bent-Over Barbell Row' => [
                'name_ar' => 'تجديف بالبار مع الانحناء',
                'equipment_ar' => 'بار',
                'description_ar' => 'تمرين تجديف مركب يبني سماكة منتصف الظهر والعضلة الظهرية.',
                'instructions_ar' => "امسك البار بقبضة علوية أعرض قليلاً من الكتفين.\nانحنِ من الورك حتى يصبح جذعك بزاوية 45° تقريباً مع ثني بسيط في الركبتين.\nاسحب البار نحو أسفل الأضلاع مع ضم لوحي الكتف.\nأنزل البار بتحكم حتى تستقيم ذراعاك.",
                'tips_ar' => "حافظ على ظهر مستقيم ورقبة بوضع طبيعي.\nلا تستخدم اندفاع الوركين لرفع الوزن.",
            ],
            'Seated Cable Row' => [
                'name_ar' => 'تجديف جالس بالكابل',
                'equipment_ar' => 'جهاز كابل',
                'description_ar' => 'حركة تجديف متحكم بها لمنتصف الظهر والعضلة الظهرية.',
                'instructions_ar' => "اجلس وقدماك على المسند والركبتان مثنيتان قليلاً.\nامسك المقبض والذراعان ممدودتان والظهر مستقيم.\nاسحب المقبض نحو بطنك بدفع المرفقين للخلف.\nاضغط لوحي الكتف ثم مدّ ذراعيك ببطء.",
                'tips_ar' => "حافظ على جذعك مستقيماً ولا تتأرجح للأمام والخلف.",
            ],
            'Overhead Dumbbell Press' => [
                'name_ar' => 'ضغط أكتاف بالدمبل',
                'equipment_ar' => 'دمبلز',
                'description_ar' => 'يبني أكتافاً قوية ومستديرة، خصوصاً الكتف الأمامي والجانبي.',
                'instructions_ar' => "اجلس على بنش بظهر مسنود وأمسك الدمبلز بمستوى الكتفين وراحتا اليدين للأمام.\nادفع الدمبلز فوق رأسك حتى تستقيم ذراعاك.\nأنزلهما ببطء إلى مستوى الكتفين.",
                'tips_ar' => "لا تقوّس أسفل ظهرك بشكل مبالغ.\nحافظ على شد عضلات البطن طوال التمرين.",
            ],
            'Dumbbell Lateral Raise' => [
                'name_ar' => 'رفرفة جانبي بالدمبل',
                'equipment_ar' => 'دمبلز',
                'description_ar' => 'يعزل الكتف الجانبي لإعطاء مظهر أعرض للأكتاف.',
                'instructions_ar' => "قف ممسكاً دمبل بكل يد إلى جانبيك.\nمع ثني بسيط في المرفقين، ارفع الدمبلز إلى الجانبين.\nتوقف عندما تصبح ذراعاك موازيتين للأرض.\nأنزلهما ببطء إلى وضع البداية.",
                'tips_ar' => "استخدم أوزاناً خفيفة وأداءً صارماً.\nقُد الحركة بالمرفقين لا باليدين.\nلا ترفع كتفيك نحو أذنيك.",
            ],
            'Barbell Biceps Curl' => [
                'name_ar' => 'تبادل بايسبس بالبار',
                'equipment_ar' => 'بار',
                'description_ar' => 'التمرين الأساسي لبناء حجم وقوة البايسبس.',
                'instructions_ar' => "قف ممسكاً البار بقبضة سفلية بعرض الكتفين.\nأبقِ المرفقين قريبين من جانبيك.\nاثنِ ذراعيك لرفع البار نحو كتفيك.\nاعصر العضلة في الأعلى ثم أنزل البار ببطء.",
                'tips_ar' => "لا تؤرجح جسمك لرفع الوزن.\nتحكّم في مرحلة النزول.",
            ],
            'Hammer Curl' => [
                'name_ar' => 'تبادل هامر',
                'equipment_ar' => 'دمبلز',
                'description_ar' => 'يعمل على البايسبس والعضلة العضدية مع الساعد بقبضة محايدة.',
                'instructions_ar' => "امسك دمبل بكل يد وراحتا اليدين نحو جسمك.\nمع تثبيت المرفقين، ارفع الدمبلز للأعلى.\nتوقف لحظة في الأعلى ثم أنزلهما ببطء.",
                'tips_ar' => "حافظ على استقامة الرسغين طوال الحركة.",
            ],
            'Triceps Rope Pushdown' => [
                'name_ar' => 'دفع ترايسبس بالحبل',
                'equipment_ar' => 'جهاز كابل',
                'description_ar' => 'تمرين عزل يستهدف رؤوس الترايسبس الثلاثة.',
                'instructions_ar' => "ثبّت حبلاً على البكرة العلوية وامسكه وراحتا اليدين متقابلتان.\nثبّت المرفقين إلى جانبيك.\nادفع الحبل للأسفل حتى تستقيم ذراعاك تماماً مع فتح طرفيه.\nعد ببطء حتى يصبح الساعدان فوق مستوى الموازاة بقليل.",
                'tips_ar' => "يجب أن يتحرك الساعدان فقط.\nلا تنحنِ فوق الحبل لتدفع بوزن جسمك.",
            ],
            'Close-Grip Bench Press' => [
                'name_ar' => 'ضغط بنش بقبضة ضيقة',
                'equipment_ar' => 'بار، بنش مستوي',
                'description_ar' => 'نسخة مركبة من الضغط تركّز أكثر على الترايسبس.',
                'instructions_ar' => "استلقِ على بنش مستوي وامسك البار بعرض الكتفين.\nأنزل البار إلى أسفل الصدر مع إبقاء المرفقين قريبين من الجسم.\nادفع للأعلى حتى تستقيم ذراعاك.",
                'tips_ar' => "القبضة الضيقة جداً تجهد الرسغين، وعرض الكتفين يكفي.",
            ],
            'Barbell Back Squat' => [
                'name_ar' => 'سكوات خلفي بالبار',
                'equipment_ar' => 'بار، حامل سكوات',
                'description_ar' => 'ملك تمارين الأرجل، يبني الفخذ الأمامي والمؤخرة والقوة العامة.',
                'instructions_ar' => "ضع البار على أعلى ظهرك وقف والقدمان بعرض الكتفين.\nشدّ عضلات الجذع وخذ نفساً عميقاً.\nانزل بالورك للخلف وللأسفل مع إبقاء الصدر مرفوعاً والركبتين باتجاه أصابع القدمين.\nانزل حتى يصبح الفخذان موازيين للأرض على الأقل.\nادفع بكامل القدم لتقف من جديد.",
                'tips_ar' => "أبقِ الكعبين على الأرض.\nلا تدع الركبتين تنحرفان للداخل.\nاستخدم حواجز الأمان في الحامل.",
            ],
            'Leg Press' => [
                'name_ar' => 'دفع الأرجل على الجهاز',
                'equipment_ar' => 'جهاز دفع الأرجل',
                'description_ar' => 'تمرين على الجهاز لبناء الفخذ الأمامي والمؤخرة مع إسناد الظهر.',
                'instructions_ar' => "اجلس على الجهاز وضع قدميك على المنصة بعرض الكتفين.\nحرّر مقابض الأمان.\nأنزل المنصة حتى تصل الركبتان إلى زاوية 90° تقريباً.\nادفع المنصة للأعلى دون قفل الركبتين.",
                'tips_ar' => "لا تدع أسفل ظهرك يرتفع عن المقعد.\nلا تقفل ركبتيك تماماً أبداً.",
            ],
            'Romanian Deadlift' => [
                'name_ar' => 'الرفعة الرومانية',
                'equipment_ar' => 'بار',
                'description_ar' => 'حركة انحناء من الورك تستهدف بقوة أوتار الركبة الخلفية والمؤخرة.',
                'instructions_ar' => "قف ممسكاً البار بمستوى الورك مع ثني بسيط في الركبتين.\nادفع الوركين للخلف مع إنزال البار على امتداد الفخذين.\nانزل حتى تشعر بتمدد قوي في العضلات الخلفية.\nادفع الوركين للأمام لتعود إلى الوقوف.",
                'tips_ar' => "حافظ على ظهر مستقيم، فالحركة تأتي من الورك.\nأبقِ البار قريباً من رجليك.",
            ],
            'Barbell Hip Thrust' => [
                'name_ar' => 'دفع الورك بالبار',
                'equipment_ar' => 'بار، بنش',
                'description_ar' => 'من أكثر التمارين فعالية لبناء عضلات المؤخرة.',
                'instructions_ar' => "اجلس على الأرض وأسند أعلى ظهرك إلى بنش مع وضع بار مبطّن فوق الوركين.\nثبّت قدميك على الأرض بعرض الورك.\nادفع الوركين للأعلى حتى يشكّل جسمك خطاً مستقيماً من الكتفين إلى الركبتين.\nاعصر المؤخرة في الأعلى ثم انزل بتحكم.",
                'tips_ar' => "أخفض ذقنك وأبقِ الأضلاع للأسفل في أعلى الحركة.\nاستخدم وسادة للبار لراحة أكبر.",
            ],
            'Plank' => [
                'name_ar' => 'البلانك',
                'equipment_ar' => 'وزن الجسم',
                'description_ar' => 'تمرين ثابت لعضلات الجذع يبني الثبات والتحمّل.',
                'instructions_ar' => "استند على ساعديك والمرفقان تحت الكتفين.\nمدّ رجليك وارفع جسمك عن الأرض.\nحافظ على خط مستقيم من الرأس إلى الكعبين مع تنفس طبيعي.",
                'tips_ar' => "لا تدع الوركين يهبطان.\nاعصر عضلات المؤخرة لحماية أسفل الظهر.",
            ],
            'Hanging Leg Raise' => [
                'name_ar' => 'رفع الأرجل معلقاً',
                'equipment_ar' => 'بار العقلة',
                'description_ar' => 'تمرين صعب لأسفل البطن وعضلات ثني الورك.',
                'instructions_ar' => "تعلّق على بار العقلة والذراعان مستقيمتان.\nمع ضم الرجلين، ارفعهما حتى تصبحا موازيتين للأرض أو أعلى.\nأنزلهما ببطء دون أرجحة.",
                'tips_ar' => "اثنِ ركبتيك لتسهيل التمرين.\nتجنّب الاعتماد على الاندفاع.",
            ],
        ];
    }
}
