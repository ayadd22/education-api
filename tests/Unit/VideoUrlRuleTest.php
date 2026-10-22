<?php

namespace Tests\Unit;

use App\Rules\VideoUrlRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VideoUrlRule.
 *
 * These tests exercise the rule in isolation — no HTTP requests, no database.
 * Each case calls validate() directly and checks whether $fail was invoked.
 */
class VideoUrlRuleTest extends TestCase
{
    private function passes(string $value): bool
    {
        $failed = false;
        (new VideoUrlRule())->validate('video_url', $value, function () use (&$failed) {
            $failed = true;
        });
        return ! $failed;
    }

    private function fails(string $value): bool
    {
        return ! $this->passes($value);
    }

    // ─── Valid YouTube URLs ───────────────────────────────────────────────────

    public function test_youtube_com_with_www(): void
    {
        $this->assertTrue($this->passes('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
    }

    public function test_youtube_com_without_www(): void
    {
        $this->assertTrue($this->passes('https://youtube.com/watch?v=dQw4w9WgXcQ'));
    }

    public function test_youtu_be_short_url(): void
    {
        $this->assertTrue($this->passes('https://youtu.be/dQw4w9WgXcQ'));
    }

    public function test_youtube_http_scheme(): void
    {
        $this->assertTrue($this->passes('http://www.youtube.com/watch?v=abc'));
    }

    public function test_youtube_embed_url(): void
    {
        $this->assertTrue($this->passes('https://www.youtube.com/embed/dQw4w9WgXcQ'));
    }

    public function test_youtube_shorts_url(): void
    {
        $this->assertTrue($this->passes('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
    }

    // ─── Valid Vimeo URLs ─────────────────────────────────────────────────────

    public function test_vimeo_com_without_www(): void
    {
        $this->assertTrue($this->passes('https://vimeo.com/123456789'));
    }

    public function test_vimeo_com_with_www(): void
    {
        $this->assertTrue($this->passes('https://www.vimeo.com/123456789'));
    }

    public function test_vimeo_http_scheme(): void
    {
        $this->assertTrue($this->passes('http://vimeo.com/123456789'));
    }

    // ─── Deceptive hostnames — must be REJECTED ───────────────────────────────

    public function test_rejects_youtube_dot_com_dot_evil(): void
    {
        // hostname is "youtube.com.evil.com", not "youtube.com"
        $this->assertTrue($this->fails('https://youtube.com.evil.com/watch?v=abc'));
    }

    public function test_rejects_evil_dash_youtube(): void
    {
        $this->assertTrue($this->fails('https://evil-youtube.com/watch?v=abc'));
    }

    public function test_rejects_subdomain_of_evil_vimeo(): void
    {
        // "vimeo.com.hacker.org" is not "vimeo.com"
        $this->assertTrue($this->fails('https://vimeo.com.hacker.org/123'));
    }

    public function test_rejects_extra_subdomain_on_youtube(): void
    {
        // Only "www." prefix is allowed; deeper subdomains are rejected.
        $this->assertTrue($this->fails('https://api.youtube.com/watch?v=abc'));
    }

    public function test_rejects_extra_subdomain_on_vimeo(): void
    {
        $this->assertTrue($this->fails('https://player.vimeo.com/video/123'));
    }

    public function test_rejects_youtu_be_with_subdomain(): void
    {
        $this->assertTrue($this->fails('https://www.youtu.be/dQw4w9WgXcQ'));
    }

    // ─── Unsupported domains ──────────────────────────────────────────────────

    public function test_rejects_example_com(): void
    {
        $this->assertTrue($this->fails('https://example.com/video.mp4'));
    }

    public function test_rejects_dailymotion(): void
    {
        $this->assertTrue($this->fails('https://www.dailymotion.com/video/x123'));
    }

    public function test_rejects_twitch(): void
    {
        $this->assertTrue($this->fails('https://www.twitch.tv/videos/123'));
    }

    // ─── Malformed / non-URL strings ─────────────────────────────────────────

    public function test_rejects_ftp_scheme(): void
    {
        $this->assertTrue($this->fails('ftp://youtube.com/video'));
    }

    public function test_rejects_plain_text(): void
    {
        $this->assertTrue($this->fails('not a url at all'));
    }

    public function test_rejects_url_without_scheme(): void
    {
        // parse_url('youtube.com/watch') returns path, not host
        $this->assertTrue($this->fails('youtube.com/watch?v=abc'));
    }

    public function test_rejects_empty_string(): void
    {
        $this->assertTrue($this->fails(''));
    }

    public function test_rejects_whitespace_only(): void
    {
        $this->assertTrue($this->fails('   '));
    }

    public function test_rejects_integer_value(): void
    {
        $failed = false;
        (new VideoUrlRule())->validate('video_url', 12345, function () use (&$failed) {
            $failed = true;
        });
        $this->assertTrue($failed);
    }

    // ─── Error message is in Arabic ───────────────────────────────────────────

    public function test_fail_message_is_arabic(): void
    {
        $message = null;
        (new VideoUrlRule())->validate('video_url', 'https://example.com/video', function (string $m) use (&$message) {
            $message = $m;
        });
        $this->assertNotNull($message);
        // Confirm message contains Arabic characters (range U+0600–U+06FF)
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $message);
    }
}
