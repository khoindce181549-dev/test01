<?php
namespace Quizably\Quiz;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the "starting values for new quizzes" saved on Settings -> Branding / Defaults into the
 * template, design and settings a freshly created quiz starts with.
 *
 * Pure (no WordPress calls, no database): it takes the stored settings map and returns plain
 * arrays, so it is unit-testable and QuizController::create() stays a thin caller.
 *
 * Only settings an admin actually saved and that hold a value the quiz builder understands are
 * applied. Anything else (never saved, empty, or a value from an older version of the Settings
 * screen) leaves the built-in default alone, so a site that never touched Settings creates
 * exactly the quizzes it always did.
 *
 * Applies to quizzes created from scratch. Preset imports bring their own design.
 */
final class NewQuizDefaults
{
    /** Typefaces the builder's Typography control offers ('default' = no override). */
    public const FONTS = [ 'default', 'geist', 'inter', 'system', 'georgia', 'courier' ];

    /** Button shapes the builder's Design tab offers. */
    public const BUTTON_STYLES = [ 'rounded', 'pill', 'sharp' ];

    /**
     * Form positions the Settings screen accepts. 'gate' and 'optional' are the legacy names for
     * 'start' and 'end' and stay valid so an already-saved setting is never rejected.
     */
    public const FORM_POSITIONS = [ 'start', 'end', 'none', 'gate', 'optional' ];

    private const LEGACY_POSITIONS = [ 'gate' => 'start', 'optional' => 'end' ];

    /**
     * @param array<string,mixed> $stored    The settings map (SettingsRepository::all()).
     * @param string              $requested Template the caller asked for ('' = not specified).
     * @return array{template:string,design:array<string,mixed>|null,settings:array<string,mixed>|null}
     */
    public static function build( array $stored, string $requested = '' ): array
    {
        return [
            'template' => self::template( $stored, $requested ),
            'design'   => self::design( $stored ),
            'settings' => self::settings( $stored ),
        ];
    }

    /**
     * Normalise a saved button-shape value. The Settings screen used to store a pixel radius
     * ('0', '6', '10', '14') or 'pill'; the builder only has rounded / pill / sharp, so those map
     * across (0 -> sharp, any other number -> rounded). Returns '' for anything unrecognised.
     */
    public static function button_style( $value ): string
    {
        $v = strtolower( trim( (string) $value ) );
        if ( in_array( $v, self::BUTTON_STYLES, true ) ) {
            return $v;
        }
        if ( '' !== $v && ctype_digit( $v ) ) {
            return 0 === (int) $v ? 'sharp' : 'rounded';
        }
        return '';
    }

    /** Canonical form position ('start' | 'end' | 'none') for a saved value, or '' if invalid. */
    public static function form_position( $value ): string
    {
        $v = (string) $value;
        $v = self::LEGACY_POSITIONS[ $v ] ?? $v;
        return in_array( $v, [ 'start', 'end', 'none' ], true ) ? $v : '';
    }

    /** @param array<string,mixed> $stored */
    private static function template( array $stored, string $requested ): string
    {
        $requested = trim( $requested );
        if ( '' !== $requested ) {
            return $requested;
        }
        $default = isset( $stored['default_template'] ) ? trim( (string) $stored['default_template'] ) : '';
        return ( '' !== $default && 1 === preg_match( '/^[a-z0-9_-]+$/i', $default ) ) ? $default : 'classic';
    }

    /**
     * @param array<string,mixed> $stored
     * @return array<string,mixed>|null
     */
    private static function design( array $stored ): ?array
    {
        $design = [];

        $colors = [];
        foreach ( [ 'primary' => 'branding_primary_color', 'accent' => 'branding_accent_color' ] as $slot => $key ) {
            $hex = isset( $stored[ $key ] ) ? trim( (string) $stored[ $key ] ) : '';
            if ( 1 === preg_match( '/^#[0-9a-f]{6}$/i', $hex ) ) {
                $colors[ $slot ] = $hex;
            }
        }
        if ( $colors ) {
            $design['colors'] = $colors;
        }

        $font = isset( $stored['defaults_font_family'] ) ? strtolower( trim( (string) $stored['defaults_font_family'] ) ) : '';
        if ( '' !== $font && 'default' !== $font && in_array( $font, self::FONTS, true ) ) {
            $design['font_family'] = $font;
        }

        $style = self::button_style( $stored['defaults_button_radius'] ?? '' );
        if ( '' !== $style ) {
            $design['button_style'] = $style;
        }

        return $design ?: null;
    }

    /**
     * @param array<string,mixed> $stored
     * @return array<string,mixed>|null
     */
    private static function settings( array $stored ): ?array
    {
        $position = self::form_position( $stored['default_optin_placement'] ?? '' );
        if ( '' === $position ) {
            return null;
        }

        $optin = [ 'placement' => $position ];

        // The default consent text only means something where the form is shown. Writing it into a
        // quiz with no form would make "has saved optin content" true and switch a form on.
        if ( 'none' !== $position ) {
            $text = isset( $stored['gdpr_default_consent_text'] ) ? trim( (string) $stored['gdpr_default_consent_text'] ) : '';
            if ( '' !== $text ) {
                $optin['gdpr_text'] = $text;
            }
        }

        return [ 'optin' => $optin ];
    }
}
