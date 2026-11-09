<?php

namespace App\Support;

use App\Models\Exercise;

/**
 * The text the admin pastes into Gemini to make an exercise's pictures, in the style of the bench press
 * pictures: a light-gray 3D figure, the main muscles bright red, the helping ones light red.
 * One prompt for the start position, then a short follow-up in the same chat for the end position,
 * so both pictures show the same person from the same angle.
 */
class PicturePrompt
{
    private const MUSCLES = [
        'chest'       => 'chest (pectorals)',
        'shoulders'   => 'shoulders (deltoids)',
        'traps'       => 'upper trapezius',
        'lats'        => 'lats (latissimus dorsi)',
        'middle_back' => 'middle back (rhomboids)',
        'lower_back'  => 'lower back (erector spinae)',
        'biceps'      => 'biceps',
        'triceps'     => 'triceps',
        'forearms'    => 'forearms',
        'abs'         => 'abdominal muscles',
        'obliques'    => 'obliques',
        'glutes'      => 'glutes',
        'quads'       => 'quadriceps (front of the thighs)',
        'hamstrings'  => 'hamstrings (back of the thighs)',
        'calves'      => 'calves',
        'adductors'   => 'inner thighs (adductors)',
        'abductors'   => 'outer hips (gluteus medius)',
    ];

    /** Start and end for exercises whose steps don't put the position first: name => [start, end]. */
    private const POSITIONS = [
        'Bench Lying Leg Raise' => ['Lying on his back on a flat bench, hips at the end, legs straight out level with the bench, hands holding the bench.', 'Straight legs raised until they point up toward the ceiling.'],
        'Bicycle Crunch' => ['Lying on his back, hands lightly by his head, shoulders slightly off the floor, both knees bent up.', 'Twisting so his right elbow meets his left knee while the right leg extends straight out.'],
        'Cable Wood Chop' => ['Standing side-on to a high pulley, both hands gripping the handle above his near shoulder, arms extended.', 'Handle pulled down and across his body to the outside of the opposite knee, torso rotated, back foot pivoted.'],
        'Dead Bug' => ['Lying on his back, arms pointing straight up, hips and knees bent at 90 degrees.', 'One arm reaching back overhead and the opposite leg extended straight just above the floor, lower back flat.'],
        'Hanging Leg Raise' => ['Hanging from a pull-up bar with straight arms, legs straight down.', 'Straight legs raised until they are parallel to the floor.'],
        'Kneeling Barbell Rollout' => ['Kneeling on a mat, hands on a barbell with round plates on the floor below his shoulders.', 'Bar rolled forward, arms stretched out in front of his head, body extended in a straight line from knees to hands.'],
        'Side Plank' => ['Lying on his side, propped on one forearm, legs straight and stacked.', 'Side plank: hips lifted so his body forms a straight line from head to feet, supported on one forearm.'],
        'Bent-Over Barbell Row' => ['Bent over with a flat back, torso about 45 degrees to the floor, knees slightly bent, bar hanging at arm\'s length.', 'Bar rowed to his lower chest, elbows driven back, same torso angle.'],
        'Close-Grip Lat Pulldown' => ['Seated at the lat pulldown, thighs under the pads, arms straight up holding the bar at shoulder width.', 'Bar pulled down to his upper chest, elbows driven down and back.'],
        'Deadlift' => ['Bottom position: hips hinged, back flat, gripping the bar on the floor just outside his legs.', 'Standing tall with the bar at his thighs, hips and knees fully extended, shoulders back.'],
        'Reverse-Grip Barbell Row' => ['Bent over with a flat back, holding the bar at arm\'s length with an underhand grip.', 'Bar rowed to his lower stomach with an underhand grip, elbows close to his body.'],
        'Straight-Arm Cable Pulldown' => ['Facing a high pulley, slight hip hinge, arms nearly straight holding the bar at eye level.', 'Bar pulled down in an arc to his thighs, arms kept straight.'],
        'V-Bar Lat Pulldown' => ['Seated at the lat pulldown, arms straight up holding the V-bar handle.', 'V-bar pulled down to his upper chest, elbows close to his sides, chest up.'],
        'Cable Chest Fly' => ['Standing in a staggered stance between two shoulder-height pulleys, arms open wide, elbows slightly bent.', 'Handles brought together in front of his chest in a wide hugging arc.'],
        'Cable Crossover' => ['Standing in the middle of a cable station with high pulleys, torso leaning slightly forward, arms open wide and slightly bent.', 'Handles brought down and together in front of his lower chest.'],
        'Dumbbell Fly' => ['Lying on a flat bench, dumbbells pressed up over his chest, palms facing each other.', 'Arms opened wide in an arc, dumbbells lowered to chest level, elbows slightly bent.'],
        'Incline Dumbbell Fly' => ['Lying on an incline bench, dumbbells above his upper chest, palms facing each other.', 'Arms opened wide in an arc, dumbbells lowered out to the sides at chest level, elbows slightly bent.'],
        'Incline Push-Up' => ['Hands on a bench, body in a straight line from head to heels, arms straight.', 'Chest lowered toward the bench, elbows at about 45 degrees, body still straight.'],
        'Low Cable Crossover' => ['Standing in a staggered stance between two low pulleys, arms down and slightly out to the sides.', 'Handles swept up and together in front of his upper chest.'],
        'Farmer\'s Walk' => ['Standing tall holding a heavy dumbbell in each hand at his sides.', 'Walking forward with short steps, shoulders back, dumbbells at his sides.'],
        'Barbell Clean and Press' => ['Bar on the floor, hips hinged, back flat, gripping the bar just outside his knees.', 'Standing tall with the bar pressed overhead, arms locked out.'],
        'Power Clean' => ['Bar on the floor, hips hinged, back flat, gripping the bar just outside his knees.', 'Bar caught on the front of his shoulders in a quarter squat, elbows high.'],
        'Single-Arm Kettlebell Swing' => ['Kettlebell hiked back between his legs with one hand, hips hinged, back flat.', 'Kettlebell swung up to chest height with a straight arm, hips fully extended, standing tall.'],
        'Barbell Glute Bridge' => ['Lying on his back, knees bent, feet flat, a padded barbell over his hips held with both hands.', 'Hips driven up so his body forms a straight line from shoulders to knees, glutes squeezed.'],
        'Single-Leg Glute Bridge' => ['Lying on his back, one foot flat on the floor, the other leg raised straight.', 'Hips lifted on one leg into a straight line from shoulders to knee, the other leg still raised.'],
        'Barbell Back Squat' => ['Standing tall with the bar on his upper back, feet shoulder-width apart.', 'Bottom of the squat: thighs about parallel to the floor, chest up, knees over the toes, bar on his upper back.'],
        'Barbell Walking Lunge' => ['Standing tall with a barbell on his upper back, feet together.', 'Lunge step: front knee bent at 90 degrees, back knee just above the floor, torso upright.'],
        'Bodyweight Squat' => ['Standing with feet shoulder-width apart, arms extended in front of him.', 'Bottom of the squat: thighs parallel to the floor, arms extended in front, chest up.'],
        'Hip Adduction Machine' => ['Seated in the hip adduction machine, legs open wide against the pads.', 'Legs squeezed together against the pads.'],
        'Leg Extension' => ['Seated in the leg extension machine, knees bent at 90 degrees, the ankle pad on his lower shins.', 'Legs extended straight out in front, quads squeezed.'],
        'Seated Calf Raise' => ['Seated in the calf raise machine, balls of his feet on the platform, heels lowered.', 'Heels raised as high as possible, calves squeezed.'],
        'Smith Machine Squat' => ['Standing tall in a Smith machine with the bar across his upper back.', 'Bottom of the squat in the Smith machine: thighs about parallel to the floor, chest up.'],
        'Standing Machine Calf Raise' => ['Standing upright in the calf raise machine, shoulders under the pads, heels hanging off the platform.', 'Risen high onto the balls of his feet, heels lifted as high as possible.'],
        'Sumo Deadlift' => ['Wide stance with toes out, hips down, gripping the bar inside his knees, back flat.', 'Standing tall with the bar at his thighs, hips locked out, wide stance.'],
        'Dumbbell Shrug' => ['Standing upright holding a dumbbell in each hand at his sides, shoulders relaxed down.', 'Shoulders shrugged straight up toward his ears.'],
        'Standing Military Press' => ['Standing with the bar resting on his upper chest, grip just outside shoulder width.', 'Bar pressed overhead, arms locked out, standing tall.'],
        'Dumbbell Triceps Kickback' => ['Bent over a flat bench with one hand and knee on it, working upper arm parallel to the floor, elbow bent at 90 degrees.', 'Working arm fully extended back, straight and in line with his torso.'],
        'Overhead Rope Triceps Extension' => ['Staggered stance facing away from the cable, rope held behind his head, elbows bent and pointing forward.', 'Arms extended forward and up, elbows straight, rope ends spread apart.'],
        'Bench Dips' => ['Hands on the edge of a bench behind him, legs extended in front, arms straight.', 'Hips lowered in front of the bench until his elbows reach about 90 degrees.'],
        // Gemini drew the start with bent arms: the dead hang is spelled out
        'Chin-Up' => ['Dead hang: arms COMPLETELY straight, elbows locked, his head clearly BELOW the bar, legs straight down.', 'Pulled up until his chin is above the bar, elbows bent and close to his sides, chest close to the bar, legs straight down.'],
        'Pull-Up' => ['Dead hang: arms COMPLETELY straight, elbows locked, his head clearly BELOW the bar, legs straight down.', 'Pulled up until his chin is above the bar, elbows pointing down to his sides, chest close to the bar.'],
        'Plank' => ['Kneeling, resting on his forearms with elbows under his shoulders.', 'Forearm plank: body in a straight line from head to heels, supported on forearms and toes.'],
        'Treadmill Running' => ['Running on a treadmill mid-stride, right leg forward, arms swinging.', 'The opposite stride: left leg forward, right arm forward.'],
        'Treadmill Walking' => ['Walking briskly on a treadmill, right foot forward, arms swinging naturally.', 'The next step: left foot forward.'],
        'Stationary Bike' => ['Seated on a stationary bike, hands on the handlebars, right pedal at the top.', 'Right pedal pushed down to the bottom, left pedal at the top.'],
        'Elliptical Trainer' => ['Standing on an elliptical trainer holding the moving handles, right foot forward.', 'Left foot forward, the opposite arm pulling its handle back.'],
        'Rowing Machine' => ['Catch position on a rowing machine: knees bent, shins vertical, arms straight reaching forward to the handle.', 'Finish position: legs straight, torso leaning back slightly, handle pulled to his lower ribs.'],
        'Jump Rope' => ['Jumping rope, feet together just off the floor, rope passing under his feet.', 'Landing softly on the balls of his feet, rope swinging overhead.'],
        'Decline Dumbbell Bench Press' => ['Lying on his back on a decline bench, dumbbells at the sides of his lower chest, palms facing his feet, elbows bent.', 'Dumbbells pressed up until his arms are straight above his lower chest.'],
        'Decline Barbell Bench Press' => ['Lying on his back on a decline bench, holding the bar with straight arms above his lower chest.', 'Bar lowered under control to his lower chest, elbows at a moderate angle from his body.'],
        'Decline Push-Up' => ['Feet raised on a bench behind him, hands on the floor slightly wider than his shoulders, arms straight, body in a straight line.', 'Chest lowered close to the floor, elbows bent at about 45 degrees from his body, feet still on the bench, body still straight.'],
    ];

    // muscles that only show from behind: the picture is taken from a back angle
    private const BACK_MUSCLES = ['lats', 'middle_back', 'lower_back', 'glutes', 'hamstrings', 'traps'];

    // the first line that moves (the setup lines before it describe the start position)
    private const MOVE = '/^(press|pull|lower|raise|curl|lift|push|drive|bend|squat|step|row|extend|bring|hinge|crunch|rotate|twist|kick|swing|jump|sit back|sink|descend|slowly|sit down|stand up|reach|roll|walk|run|pedal|tuck|sweep)\\b/i';

    public static function start(Exercise $exercise): string
    {
        [$start] = self::positions($exercise);

        return self::describe($exercise, 'Start position: ' . $start);
    }

    /**
     * The end position as a full description of its own: asked to change the first picture in the same chat,
     * Gemini kept sending the first picture back, so it draws a new one in the same style instead.
     */
    public static function end(Exercise $exercise): string
    {
        [, $end] = self::positions($exercise);

        return 'A new picture, not the previous one. ' . self::describe($exercise, 'End position of the movement: ' . $end);
    }

    /**
     * Both positions in one picture, side by side: drawn together, they have the same person, grip and machine
     * (drawn apart or edited, they didn't). The pictures page cuts it in two: left the start, right the end.
     */
    public static function pair(Exercise $exercise): string
    {
        [$start, $end] = self::positions($exercise);
        $muscles = $exercise->muscles;
        $back = self::fromBehind($exercise);

        $equipment = trim((string) $exercise->equipment);
        $what = $exercise->name . ($equipment !== '' && stripos($equipment, 'bodyweight') === false ? ' (' . $equipment . ')' : '');
        $direction = preg_replace(['/^Direction: /', '/ Keep exactly this direction, do not mirror the picture\.$/'], ['', ''], self::direction($exercise, $back));

        $text = 'Clean anatomical fitness illustration, 3D render style. ONE wide 16:9 image made of TWO panels side by side, '
            . 'separated by a thin vertical white gap, showing two moments of the ' . $what . '. '
            . 'LEFT panel, the start position: ' . $start . ' '
            . 'RIGHT panel, the end position: ' . $end . ' '
            . 'The two panels are like two frames of the same video: the same scene, the same camera position, angle and zoom, '
            . 'the same equipment in the same place with exactly the same shape and size (it does not move or change), and the '
            . 'same muscular male athlete with smooth light-gray skin and black shorts with the same grip; only his pose changes. '
            . 'Seen ' . self::angle($exercise, $back) . '; in both panels ' . rtrim($direction, '.') . '. '
            . self::grip($exercise)
            . (($note = self::note($exercise)) ? $note . ' ' : '')
            . 'In both panels, highlighted in bright red: the ' . self::list($muscles['primary']);
        if ($muscles['secondary']) {
            $text .= '. In light red: the ' . self::list($muscles['secondary']);
        }

        return $text . '. Every other muscle stays gray. Correct, safe technique. Plain white background, dark-gray gym equipment, '
            . 'the whole athlete and all the equipment inside each panel with white space around them. No text, no numbers, '
            . 'no arrows, no logos.';
    }

    /**
     * The end picture as an edit of the start picture (attached to the message): drawn from scratch, the grip and the
     * machine came out different, so only the pose is changed and everything else is kept.
     */
    public static function edit(Exercise $exercise): string
    {
        [, $end] = self::positions($exercise);

        return 'Edit the attached picture. Change only the pose: show the END position of the movement. ' . $end . ' '
            . self::grip($exercise)
            . 'The weight or handles move together with his hands. Everything else stays exactly as in the attached picture: '
            . 'the same person and body, the same grip and hand position, the same equipment, machine and bench with the same '
            . 'shape, size and place, the same camera angle and distance, the same direction he faces, the same colors, red '
            . 'muscles, lighting and white background. Do not redraw the scene, do not mirror it, do not add or remove anything.';
    }

    /** Sent with the start picture (or right after it in the same chat): Gemini turns it into a short looping video. */
    public static function video(Exercise $exercise): string
    {
        [$start, $end] = self::positions($exercise);

        return 'Animate this image into a short video: the same athlete performs slow, controlled repetitions of the '
            . $exercise->name . ', moving smoothly from the start position (' . rtrim($start, '.') . ') to the end position ('
            . rtrim($end, '.') . ') and back, with correct technique. Keep the camera still, the same style, the same red '
            . 'muscles and the plain white background. No text, no music.';
    }

    private static function describe(Exercise $exercise, string $position): string
    {
        $muscles = $exercise->muscles;
        $back = self::fromBehind($exercise);

        $equipment = trim((string) $exercise->equipment);
        $what = $exercise->name . ($equipment !== '' && stripos($equipment, 'bodyweight') === false ? ' (' . $equipment . ')' : '');

        $text = 'Clean anatomical fitness illustration, 3D render style. A muscular male athlete with smooth light-gray skin '
            . 'and black shorts performs the ' . $what . ', seen ' . self::angle($exercise, $back) . '. '
            . self::direction($exercise, $back) . ' '
            . $position . ' '
            . self::grip($exercise)
            . (($note = self::note($exercise)) ? $note . ' ' : '')
            . 'Highlighted in bright red: the ' . self::list($muscles['primary']);
        if ($muscles['secondary']) {
            $text .= '. In light red: the ' . self::list($muscles['secondary']);
        }

        return $text . '. Every other muscle stays gray. Correct, safe technique. Plain white background, dark-gray gym equipment, '
            . 'full body visible, square image, no text, no logos.';
    }

    /**
     * Which way the athlete faces, the same in the start and the end picture: without it Gemini picked a side at
     * random and the end picture came out mirrored.
     */
    private static function direction(Exercise $exercise, bool $back): string
    {
        [$start] = self::positions($exercise);
        $flat = (preg_match('/\b(lie|lies|lying|lay|plank|push-ups?|all fours|face down|on his back|on his side)\b/i', $start)
                || preg_match('/\b(bench press|pullover|skull crusher|hyperextension|back extension|hip thrust|glute bridge|rollout|push-up|lying|plank)\b/i', $exercise->name))
            // the arms lie on the pad, the athlete sits or stands
            && !preg_match('/\b(preacher|spider) curl\b/i', $exercise->name);

        if ($flat) {
            $where = 'his head is on the LEFT side of the picture and his feet are on the RIGHT side';
        } elseif ($back) {
            $where = 'we see him from behind, turned toward the RIGHT side of the picture';
        } else {
            $where = 'he is turned toward the RIGHT side of the picture';
        }

        return 'Direction: ' . $where . '. Keep exactly this direction, do not mirror the picture.';
    }

    // drawn from the front although they work the back: the grip is what tells them apart
    private const FROM_THE_FRONT = ['Chin-Up'];

    /** The grip, spelled out where it is what makes the exercise (Gemini drew a chin-up with an overhand grip). */
    private const GRIPS = [
        'Chin-Up'                                => 'UNDERHAND (supinated): palms facing toward his face, hands about shoulder-width apart',
        'Pull-Up'                                => 'OVERHAND (pronated): palms facing away from him, hands slightly wider than his shoulders',
        'Lat Pulldown'                           => 'OVERHAND (pronated): palms facing away from him, hands slightly wider than his shoulders',
        'Close-Grip Lat Pulldown'                => 'OVERHAND (pronated): palms facing away from him, hands about shoulder-width apart',
        'V-Bar Lat Pulldown'                     => 'NEUTRAL: palms facing each other on the V-bar handle',
        'Bent-Over Barbell Row'                  => 'OVERHAND (pronated): palms facing his body, hands slightly wider than his shoulders',
        'Reverse-Grip Barbell Row'               => 'UNDERHAND (supinated): palms facing forward, away from his body, hands about shoulder-width apart',
        'Reverse-Grip Triceps Pushdown'          => 'UNDERHAND (supinated): palms facing up, hands about shoulder-width apart',
        'Single-Arm Reverse-Grip Cable Pushdown' => 'UNDERHAND (supinated): palm facing up',
        'Reverse-Grip Barbell Curl'              => 'OVERHAND (pronated): palms facing down the whole time, also at the top of the curl',
        'Hammer Curl'                            => 'NEUTRAL: palms facing each other, thumbs up, the whole time',
        'Cable Rope Hammer Curl'                 => 'NEUTRAL: palms facing each other, thumbs up, the whole time',
        'Barbell Biceps Curl'                    => 'UNDERHAND (supinated): palms facing forward and up',
        'EZ-Bar Curl'                            => 'UNDERHAND (supinated): palms facing up on the angled grips',
        'Barbell Preacher Curl'                  => 'UNDERHAND (supinated): palms facing up',
        'Standing Cable Curl'                    => 'UNDERHAND (supinated): palms facing up',
        'Barbell Wrist Curl'                     => 'palms facing UP, forearms resting on the bench',
        'Reverse Barbell Wrist Curl'             => 'palms facing DOWN, forearms resting on the bench',
    ];

    private static function fromBehind(Exercise $exercise): bool
    {
        return !in_array($exercise->name, self::FROM_THE_FRONT, true)
            && (bool) array_intersect($exercise->muscles['primary'], self::BACK_MUSCLES);
    }

    private static function grip(Exercise $exercise): string
    {
        return isset(self::GRIPS[$exercise->name]) ? 'Grip: ' . self::GRIPS[$exercise->name] . '. ' : '';
    }

    /** A decline bench seen at an angle is easily drawn the wrong way: those are drawn exactly from the side. */
    private static function angle(Exercise $exercise, bool $back): string
    {
        if (str_contains($exercise->name, 'Decline') && !str_contains($exercise->name, 'Push-Up')) {
            return 'exactly from the side (a profile view, head on the left, so the slope of the bench is obvious)';
        }

        if (in_array($exercise->name, self::FROM_THE_FRONT, true)) {
            return 'from a 3/4 front angle, so his face, chest and both hands on the bar are clearly visible';
        }

        return $back ? 'from a 3/4 back angle so the back of the body is visible' : 'from a 3/4 side angle';
    }

    /**
     * Image models mix up incline and decline: the position of the head is spelled out.
     */
    private static function note(Exercise $exercise): ?string
    {
        $name = $exercise->name;

        return match (true) {
            $name === 'Decline Push-Up' => 'Important: his feet are raised on a bench behind him, so his head is clearly lower than his feet.',
            $name === 'Incline Push-Up' => 'Important: his hands are on a raised bench, so his head is clearly higher than his feet.',
            $name === 'Incline Reverse Dumbbell Fly' => 'Important: he lies chest-down on a bench raised to about 30 degrees, facing the floor.',
            str_contains($name, 'Decline') => 'Important: this is a DECLINE bench, not a flat or incline one. The bench is tilted about 30 degrees: '
                . 'the end under his head is low, close to the floor, and the end under his knees is high. So his head is clearly LOWER than his hips, '
                . 'his hips are LOWER than his knees, and his lower legs are hooked under the foot pads at the high end.',
            str_contains($name, 'Incline') => 'Important: this is an INCLINE bench. The backrest is raised to about 30–45 degrees, '
                . 'so his head and shoulders are clearly HIGHER than his hips.',
            default => null,
        };
    }

    /** [start, end] position, in words, from the exercise's English steps. */
    private static function positions(Exercise $exercise): array
    {
        if (isset(self::POSITIONS[$exercise->name])) {
            return self::POSITIONS[$exercise->name];
        }
        if ($exercise->muscle_group === 'cardio') {
            return [
                'in steady motion on the ' . strtolower((string) ($exercise->equipment ?: $exercise->name)) . ', first half of the stride.',
                'the opposite half of the stride (the other leg forward).',
            ];
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $exercise->instructions))));
        if (!$lines) {
            return ['the starting position.', 'the finishing position, at the peak of the movement.'];
        }

        // the first line that moves, once its lead-in ("Exhale and", "With a slight bend in your elbows,") is left out
        $move = null;
        foreach ($lines as $i => $line) {
            if ($i > 0 && preg_match(self::MOVE, self::action($line))) {
                $move = $i;
                break;
            }
        }
        $move ??= min(1, count($lines) - 1);
        $setup = array_slice($lines, 0, max(1, min($move, 2)));
        $end = self::action($lines[$move] ?? $lines[0]);

        return [self::third(self::sentence(implode(' ', $setup))), self::third(self::sentence(ucfirst($end)))];
    }

    /** The action of a step, without "Exhale and" or a lead-in like "With a slight bend in your elbows,". */
    private static function action(string $line): string
    {
        $line = preg_replace('/^(exhale|inhale|breathe out|breathe in)( and|,)?\s+/i', '', trim($line));
        $line = preg_replace('/^(with|keeping|while)\b[^,]{0,80},\s*/i', '', $line);

        return ucfirst($line);
    }

    /** The steps speak to the trainee ("your knees"); the picture describes the athlete ("his knees"). */
    private static function third(string $text): string
    {
        return preg_replace(['/\byourself\b/i', '/\byour\b/', '/\bYour\b/', '/\byou\b/', '/\bYou\b/'], ['himself', 'his', 'His', 'he', 'He'], $text);
    }

    private static function list(array $keys): string
    {
        $names = array_map(fn ($k) => self::MUSCLES[$k] ?? str_replace('_', ' ', $k), $keys);
        if (count($names) < 2) {
            return $names[0] ?? 'target muscles';
        }

        return implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
    }

    private static function sentence(string $text): string
    {
        $text = trim($text);

        return preg_match('/[.!?]$/', $text) ? $text : $text . '.';
    }
}
