{{--
  Muscle map: the body from the front and from the back, with the muscles an exercise works.
  $muscles = ['primary' => [...], 'secondary' => [...]] (keys of App\Models\Exercise::MUSCLES).
  Shapes are drawn for the left half and mirrored; 'c' ones sit in the middle.
--}}
@php
  use App\Models\Exercise;

  $primary = $muscles['primary'] ?? [];
  $secondary = $muscles['secondary'] ?? [];
  $state = fn ($m) => in_array($m, $primary, true) ? 'mm-on' : (in_array($m, $secondary, true) ? 'mm-soft' : '');

  $arm = [
    'upper' => 'M28 67C31 61 36 57 41 56C41 64 40 72 37 80C35 85 31 87 28 85C26 79 26 72 28 67Z',
    'fore'  => 'M27 88C30 90 34 89 37 84C37 94 35 104 32 114L26 114C24 105 24 96 27 88Z',
    'delt'  => 'M41 41C33 41 28 47 27 56C27 60 28 63 29 65C32 60 36 56 42 54C43 50 43 45 41 41Z',
  ];
  $skin = [
    'head'  => ['ellipse', ['cx' => 60, 'cy' => 17, 'rx' => 10, 'ry' => 12]],
    'neck'  => ['path', ['d' => 'M54 27Q60 31 66 27L67 37Q60 40 53 37Z']],
  ];
  $views = [
    'front' => [
      ['traps', 'M54 33C50 37 46 39 41 41L52 41Z'],
      ['shoulders', $arm['delt']],
      ['chest', 'M44 42L59 42L59 64C53 67 47 66 43 61L43 54C44 50 44 46 44 42Z'],
      ['biceps', $arm['upper']],
      ['forearms', $arm['fore']],
      ['obliques', 'M43 63C46 66 49 68 51 68L51 106L46 110C44 98 43 84 43 63Z'],
      ['abs', 'M52 68L68 68L68 106C64 110 56 110 52 106Z', 'c'],
      ['quads', 'M44 116C47 118 51 121 55 126C57 140 56 154 54 166L46 166C43 154 41 140 42 128C42 123 43 119 44 116Z'],
      ['adductors', 'M56 127L60 124L60 154C58 151 57 146 56 141Z'],
      ['calves', 'M45 177C44 190 45 204 47 216L53 216C55 204 56 190 55 177C52 179 48 179 45 177Z'],
    ],
    'back' => [
      ['traps', 'M60 28C57 33 50 37 41 41L50 45L60 60L70 45L79 41C70 37 63 33 60 28Z', 'c'],
      ['shoulders', $arm['delt']],
      ['middle_back', 'M50 46L60 61L70 46L71 62L65 74L55 74L49 62Z', 'c'],
      ['lats', 'M43 47L48 46L48 62L54 75L55 93C50 91 47 87 45 81C43 71 42 59 43 47Z'],
      ['lower_back', 'M56 76L64 76L65 92C66 98 67 102 68 106L52 106C53 102 54 98 55 92Z', 'c'],
      ['triceps', $arm['upper']],
      ['forearms', $arm['fore']],
      ['abductors', 'M45 104C48 102 51 104 52 106L51 113L44 114C43 110 44 106 45 104Z'],
      ['glutes', 'M44 115L52 114L60 110L60 136C54 139 47 137 44 131C42 126 43 120 44 115Z'],
      ['hamstrings', 'M44 137C48 140 54 141 59 139C58 149 57 158 55 166L46 166C43 156 42 147 44 137Z'],
      ['calves', 'M45 175C42 185 43 196 46 206C48 212 52 212 54 206C57 196 57 185 55 175C52 177 48 177 45 175Z'],
    ],
  ];
  // hands, knees, lower legs and feet, for both views
  $limbs = [
    ['ellipse', ['cx' => 29, 'cy' => 121, 'rx' => 4, 'ry' => 6]],
    ['ellipse', ['cx' => 50, 'cy' => 171, 'rx' => 5, 'ry' => 4.5]],
    ['path', ['d' => 'M46 218L54 218L57 226L44 226Z']],
  ];
  $mirror = 'matrix(-1 0 0 1 120 0)';
  $join = fn (array $keys) => collect($keys)->map(fn ($m) => Exercise::muscleName($m))->implode(app()->isLocale('ar') ? '، ' : ', ');
  $attrs = fn (array $a) => collect($a)->map(fn ($v, $k) => $k . '="' . e($v) . '"')->implode(' ');
@endphp

<figure class="muscle-map">
  <svg viewBox="0 0 260 240" role="img" aria-labelledby="mm-title-{{ $id ?? 'x' }}">
    <title id="mm-title-{{ $id ?? 'x' }}">{{ __('Muscles worked') }}</title>
    @foreach($views as $view => $shapes)
      <g transform="translate({{ $view === 'front' ? 0 : 140 }} 0)">
        @foreach($skin as [$tag, $a])<{{ $tag }} class="mm-skin" {!! $attrs($a) !!}/>@endforeach
        @foreach($limbs as [$tag, $a])
          <{{ $tag }} class="mm-skin" {!! $attrs($a) !!}/><{{ $tag }} class="mm-skin" transform="{{ $mirror }}" {!! $attrs($a) !!}/>
        @endforeach
        @if($view === 'front')
          <path class="mm-skin" d="M46 110L51 106C56 111 64 111 69 106L74 110L72 117L60 124L48 117Z"/>
        @else
          <path class="mm-skin" d="M47 208L53 208L54 217L46 217Z"/><path class="mm-skin" transform="{{ $mirror }}" d="M47 208L53 208L54 217L46 217Z"/>
        @endif
        @foreach($shapes as $shape)
          @php([$muscle, $d] = $shape)
          <path class="mm-part {{ $state($muscle) }}" data-muscle="{{ $muscle }}" d="{{ $d }}"><title>{{ Exercise::muscleName($muscle) }}</title></path>
          @if(($shape[2] ?? null) !== 'c')
            <path class="mm-part {{ $state($muscle) }}" data-muscle="{{ $muscle }}" transform="{{ $mirror }}" d="{{ $d }}"><title>{{ Exercise::muscleName($muscle) }}</title></path>
          @endif
        @endforeach
        @if($view === 'front')
          <path class="mm-line" d="M52 80H68M52 92H68M60 68V107"/>
        @endif
        <text x="60" y="238" class="mm-label">{{ $view === 'front' ? __('Front view') : __('Back view') }}</text>
      </g>
    @endforeach
  </svg>
  <figcaption class="mm-legend">
    @if($primary)
      <span><i class="mm-dot mm-on"></i> {{ __('Main') }}: <strong>{{ $join($primary) }}</strong></span>
    @endif
    @if($secondary)
      <span><i class="mm-dot mm-soft"></i> {{ __('Helping') }}: {{ $join($secondary) }}</span>
    @endif
  </figcaption>
</figure>
