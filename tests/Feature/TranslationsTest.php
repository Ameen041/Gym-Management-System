<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/** The site is Arabic first: every text shown to people needs its Arabic line in lang/ar.json. */
class TranslationsTest extends TestCase
{
    private array $arabic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arabic = json_decode(file_get_contents(base_path('lang/ar.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Source files without comments, so examples in docblocks don't count. */
    private function sources(array $dirs, string $pattern): iterable
    {
        foreach (Finder::create()->files()->in(array_map('base_path', $dirs))->name($pattern) as $file) {
            $code = $file->getContents();
            if (str_ends_with($file->getFilename(), '.php') && !str_ends_with($file->getFilename(), '.blade.php')) {
                $code = collect(token_get_all($code))
                    ->reject(fn ($t) => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))
                    ->map(fn ($t) => is_array($t) ? $t[1] : $t)->implode('');
            }
            yield $file->getRelativePathname() => $code;
        }
    }

    private function missing(iterable $sources, array $patterns): array
    {
        $missing = [];
        foreach ($sources as $file => $code) {
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $code, $m);
                foreach ($m[1] as $key) {
                    $key = stripslashes($key);
                    if (!str_contains($key, '$') && !array_key_exists($key, $this->arabic)) {
                        $missing[$key] = $file;
                    }
                }
            }
        }

        return $missing;
    }

    public function test_every_text_in_pages_and_code_has_an_arabic_translation(): void
    {
        $missing = $this->missing($this->sources(['resources/views', 'app'], '*.php'), [
            "/(?:__|trans_choice|@lang)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/",
            '/(?:__|trans_choice|@lang)\\(\\s*"((?:[^"\\\\]|\\\\.)*)"/',
        ]);

        $this->assertSame([], $missing, 'Add these to lang/ar.json');
    }

    public function test_every_notification_has_an_arabic_translation(): void
    {
        $missing = $this->missing($this->sources(['app'], '*.php'), [
            "/Notify::user\\(\\s*[^,]+,\\s*'((?:[^'\\\\]|\\\\.)*)'/",
            "/Notify::admins\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/",
        ]);

        $this->assertSame([], $missing, 'Add these to lang/ar.json');
    }

    public function test_arabic_lines_keep_their_placeholders(): void
    {
        $broken = [];
        foreach ($this->arabic as $english => $arabic) {
            preg_match_all('/:([a-zA-Z_]+)/', $english, $want);
            preg_match_all('/:([a-zA-Z_]+)/', $arabic, $have);
            $lost = array_diff($want[1], $have[1]);
            if (str_contains($english, '|')) {
                $lost = array_diff($lost, ['count']);   // "{1} one day" needs no number
            }
            if ($lost) {
                $broken[$english] = $arabic;
            }
        }

        $this->assertSame([], $broken, 'These Arabic lines lost a :placeholder');
    }
}
