<?php
namespace Quizably\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Everything about turning an admin-authored email template into an email.
 *
 * The body is written in a rich-text editor, so it is HTML; the subject is
 * plain text. Both may contain "tokens" - {quiz_title}, {user_name}, ... - that
 * are replaced with real values when the email is sent.
 *
 * Kept separate from Mailer so the rules that matter for safety live in one
 * small, testable place:
 *   - a value put into an HTML body is HTML-escaped (a visitor who types
 *     "<script>" as their name must not be able to inject markup into the
 *     admin's mailbox);
 *   - a stored body is reduced to an email-safe subset of tags on the way in
 *     AND again on the way out;
 *   - a template with no HTML in it (everything saved before the editor
 *     existed) is still sent as plain text, unchanged.
 */
final class EmailTemplate
{
    /**
     * The details an author can insert, in the order the admin lists them.
     * Written {name} in a template.
     */
    public const TOKENS = [
        'quiz_title',
        'result_title',
        'score',
        'user_name',
        'user_email',
        'submitted_at',
        'site_title',
        'admin_url',
    ];

    /**
     * Old names for the same values. Templates saved when the mailer documented
     * {{double-brace}} tokens (lead_email, lead_name) keep working. Every token
     * is accepted as {name} or {{name}}.
     */
    private const ALIASES = [
        'lead_email' => 'user_email',
        'lead_name'  => 'user_name',
    ];

    /**
     * The only CSS properties a `style` attribute may keep in an email body.
     * These are what the editor produces (colour, size, alignment) plus plain
     * text emphasis. No url(), no positioning, no backgrounds.
     */
    private const SAFE_STYLES = [
        'color',
        'background-color',
        'font-size',
        'font-weight',
        'font-style',
        'text-align',
        'text-decoration',
        'line-height',
    ];

    /** Does this template contain HTML (i.e. was it written in the editor)? */
    public static function is_html(string $template): bool
    {
        // A real tag: "<p>", "</p>", "<br/>", "<a href=...>". Deliberately NOT
        // "<{user_email}>" or "<john@doe.com>" - a plain-text template may use
        // angle brackets around an address.
        return 1 === preg_match('/<\/?[a-z][a-z0-9]*(?:\s[^>]*)?\/?>/i', $template);
    }

    /**
     * Replace tokens with values.
     *
     * @param array<string,string> $vars  token name => raw value
     * @param bool                 $html  true when $template is HTML: values are escaped
     */
    public static function render(string $template, array $vars, bool $html): string
    {
        $map = [];
        $put = static function (string $name, string $value) use (&$map, $html): void {
            $value = $html ? esc_html($value) : $value;
            $map['{{' . $name . '}}'] = $value;
            $map['{' . $name . '}']   = $value;
        };

        foreach ($vars as $name => $value) {
            $put((string) $name, (string) $value);
        }
        foreach (self::ALIASES as $legacy => $canonical) {
            if (array_key_exists($canonical, $vars)) {
                $put($legacy, (string) $vars[$canonical]);
            }
        }

        // strtr() makes a single pass and prefers the longest key, so "{{x}}" is
        // never half-replaced, and a value that itself contains "{user_email}"
        // is NOT expanded a second time.
        return strtr($template, $map);
    }

    /** Subject line: tokens filled in, and never spanning lines (header safety). */
    public static function subject(string $template, array $vars): string
    {
        $subject = self::render($template, $vars, false);

        return trim((string) preg_replace('/[\r\n]+/', ' ', $subject));
    }

    /**
     * Reduce a body to what an email client can be trusted with.
     *
     * HTML keeps paragraphs, line breaks, bold/italic/underline/strike, lists,
     * quotes, headings, links and simple inline styles (colour, size,
     * alignment); scripts, event handlers and javascript: links are removed.
     * A body with no HTML in it is treated as plain text and only cleaned.
     */
    public static function sanitize_body(string $body): string
    {
        $body = trim($body);
        if ('' === $body) {
            return '';
        }
        if (! self::is_html($body)) {
            return sanitize_textarea_field($body);
        }

        // wp_kses lets `style` through but WordPress's default list of safe CSS
        // properties is broad (position, background with a remote url(), ...):
        // enough for a layout overlay or a tracking pixel. An email body needs
        // colour, size and alignment - so narrow the list, for this call only.
        $narrow = static function ($allowed) {
            return array_values(array_intersect((array) $allowed, self::SAFE_STYLES));
        };
        add_filter('safe_style_css', $narrow, PHP_INT_MAX);
        try {
            $clean = wp_kses($body, self::allowed_html());
        } finally {
            remove_filter('safe_style_css', $narrow, PHP_INT_MAX);
        }

        return trim($clean);
    }

    /**
     * Readable plain-text version of an HTML body. Sent alongside the HTML so
     * clients that don't render HTML (and spam filters that like a text part)
     * get a proper message rather than a wall of tags.
     */
    public static function to_text(string $html): string
    {
        $text = $html;

        // Links: "label (url)", or just the url when the label is the url.
        $text = (string) preg_replace_callback(
            '#<a\s[^>]*?href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            static function (array $m): string {
                $url   = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $label = trim(html_entity_decode(wp_strip_all_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return ('' === $label || $label === $url) ? $url : $label . ' (' . $url . ')';
            },
            $text
        );

        // The editor wraps list items in <p>; unwrap them so bullets stay tight.
        $text = (string) preg_replace('#(<li[^>]*>)\s*<p[^>]*>#i', '$1', $text);
        $text = (string) preg_replace('#</p>\s*(</li>)#i', '$1', $text);

        $text = (string) preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = (string) preg_replace('#<li[^>]*>#i', '- ', $text);
        $text = (string) preg_replace('#</li>#i', "\n", $text);
        $text = (string) preg_replace('#<hr\s*/?>#i', "\n---\n", $text);
        $text = (string) preg_replace('#</(p|div|h[1-6]|blockquote|ul|ol|pre)>#i', "\n\n", $text);

        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace("/[ \t]+\n/", "\n", $text);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Wrap a body fragment in a minimal, email-client-friendly document. Inline
     * styles only - clients strip <style> blocks and external CSS.
     */
    public static function wrap_html(string $fragment): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"></head>'
            . '<body style="margin:0;padding:24px;background:#f4f5f7;">'
            . '<div style="max-width:600px;margin:0 auto;padding:24px;background:#ffffff;'
            . 'border-radius:8px;font-family:-apple-system,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;'
            . 'font-size:15px;line-height:1.6;color:#1f2933;">'
            . $fragment
            . '</div></body></html>';
    }

    /**
     * Tags and attributes an email body may keep. Mirrors what the editor can
     * produce; anything else is removed by wp_kses (which also drops
     * javascript: URLs and filters `style` down to safe properties).
     *
     * @return array<string,array<string,bool>>
     */
    private static function allowed_html(): array
    {
        $style = ['style' => true];

        return [
            'p'          => $style + ['align' => true],
            'br'         => [],
            'hr'         => [],
            'strong'     => [],
            'b'          => [],
            'em'         => [],
            'i'          => [],
            'u'          => [],
            's'          => [],
            'strike'     => [],
            'del'        => [],
            'code'       => [],
            'pre'        => [],
            'blockquote' => $style,
            'ul'         => $style,
            'ol'         => $style + ['start' => true],
            'li'         => $style,
            'h1'         => $style,
            'h2'         => $style,
            'h3'         => $style,
            'h4'         => $style,
            'span'       => $style,
            'div'        => $style,
            'a'          => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'style' => true],
        ];
    }
}
