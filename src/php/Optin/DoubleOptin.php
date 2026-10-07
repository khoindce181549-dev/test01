<?php
namespace Quizably\Optin;

use Quizably\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Double opt-in for quiz leads.
 *
 * Flow: the visitor submits the lead form on a quiz that has double opt-in on. The lead is saved
 * as pending (double_optin_verified = 0) and a confirmation email is sent. Everything that
 * "counts" a lead (the lead-captured webhook, the owner's new-lead email, analytics) waits for
 * the click: confirm() marks the lead verified and then fires quizably_lead_captured.
 *
 * The confirmation link is a signed token (HMAC of the lead id with a site secret). The link is
 * handled by Quizably\Frontend\OptinConfirmation; the REST route /public/confirm-optin is a
 * second way to complete the same step.
 */
final class DoubleOptin
{
    public const SECRET_OPTION = 'quizably_doi_secret';
    public const QUERY_VAR     = 'quizably_doi';

    /**
     * Whether this quiz asks leads to confirm their email.
     *
     * @param array<string,mixed>|null $quiz Quiz row (settings may still be a JSON string).
     */
    public static function is_enabled( ?array $quiz ): bool
    {
        if ( ! $quiz || empty( $quiz['settings'] ) ) {
            return false;
        }
        $settings = is_array( $quiz['settings'] ) ? $quiz['settings'] : json_decode( (string) $quiz['settings'], true );
        if ( ! is_array( $settings ) ) {
            return false;
        }
        return ! empty( $settings['optin']['double_optin'] );
    }

    /**
     * Signing secret. Created on first use when $create is true. An empty string means "no secret
     * exists yet", and verification must never succeed in that case (an empty HMAC key would let
     * anyone forge a valid token).
     */
    private static function secret( bool $create ): string
    {
        $secret = (string) get_option( self::SECRET_OPTION, '' );
        if ( '' === $secret && $create ) {
            $secret = wp_generate_password( 32, false );
            update_option( self::SECRET_OPTION, $secret, false );
        }
        return $secret;
    }

    /** URL-safe signed token: base64url(lead_id) . '.' . hmac. */
    public static function token_for( int $lead_id ): string
    {
        $encoded = strtr( base64_encode( (string) $lead_id ), '+/=', '-_~' );
        $sig     = hash_hmac( 'sha256', (string) $lead_id, self::secret( true ) );
        return $encoded . '.' . $sig;
    }

    /** The lead id a token was issued for, or null when the token is malformed or not signed by this site. */
    public static function lead_id_from_token( string $token ): ?int
    {
        $parts = explode( '.', $token, 2 );
        if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
            return null;
        }

        $secret = self::secret( false );
        if ( '' === $secret ) {
            return null;
        }

        $decoded = base64_decode( strtr( $parts[0], '-_~', '+/=' ), true );
        if ( false === $decoded || '' === $decoded || ! ctype_digit( $decoded ) ) {
            return null;
        }
        $lead_id = (int) $decoded;
        if ( $lead_id < 1 ) {
            return null;
        }

        $expected = hash_hmac( 'sha256', (string) $lead_id, $secret );
        return hash_equals( $expected, $parts[1] ) ? $lead_id : null;
    }

    public static function confirm_url( int $lead_id ): string
    {
        return home_url( '/?' . self::QUERY_VAR . '=' . rawurlencode( self::token_for( $lead_id ) ) );
    }

    /**
     * Email the confirmation link.
     *
     * @param array<string,mixed>      $lead
     * @param array<string,mixed>|null $quiz
     */
    public static function send_confirmation( array $lead, ?array $quiz ): bool
    {
        $email = (string) ( $lead['email'] ?? '' );
        $id    = (int) ( $lead['id'] ?? 0 );
        if ( '' === $email || $id < 1 ) {
            return false;
        }

        $subject = __( 'Confirm your subscription', 'quizably' );
        $message = sprintf(
            /* translators: %s: confirmation URL */
            __( "Please click the link below to confirm your subscription:\n\n%s", 'quizably' ),
            self::confirm_url( $id )
        );

        /**
         * Filter the confirmation email.
         *
         * @param array{subject:string,message:string} $mail
         * @param array<string,mixed>                  $lead
         * @param array<string,mixed>|null             $quiz
         */
        $mail = apply_filters( 'quizably_doi_email', [ 'subject' => $subject, 'message' => $message ], $lead, $quiz );

        return (bool) wp_mail( $email, (string) $mail['subject'], (string) $mail['message'] );
    }

    /**
     * Mark a pending lead as confirmed, then release everything that waited for the confirmation.
     *
     * @return string 'confirmed' (just now), 'already' (confirmed earlier) or 'missing' (no such
     *                lead, or the lead never asked for double opt-in).
     */
    public static function confirm( int $lead_id ): string
    {
        $repos = Plugin::instance()->services()['repos'] ?? [];
        if ( empty( $repos['leads'] ) ) {
            return 'missing';
        }

        $lead = $repos['leads']->find( $lead_id );
        if ( ! $lead || null === ( $lead['double_optin_verified'] ?? null ) ) {
            return 'missing';
        }
        if ( 1 === (int) $lead['double_optin_verified'] ) {
            return 'already';
        }

        $repos['leads']->set_double_optin_status( $lead_id, 1 );

        /**
         * Fires once when a lead confirms their email address.
         *
         * @param int                 $lead_id
         * @param array<string,mixed> $lead
         */
        do_action( 'quizably_lead_confirmed', $lead_id, $lead );

        // The lead is only "captured" now: this is what webhooks and the owner's new-lead email wait for.
        $sub = ! empty( $repos['submissions'] ) ? $repos['submissions']->find_latest_any_by_lead_id( $lead_id ) : null;
        if ( $sub ) {
            do_action( 'quizably_lead_captured', $lead_id, $sub, null );
        }

        return 'confirmed';
    }
}
