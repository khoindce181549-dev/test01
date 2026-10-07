<?php
namespace Quizably\Frontend;

use Quizably\Optin\DoubleOptin;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the link in the double opt-in confirmation email (`/?quizably_doi=<token>`).
 *
 * The token is verified, the lead is marked confirmed, and the visitor sees a short
 * confirmation screen with a link back to the site.
 */
final class OptinConfirmation
{
    public function register(): void
    {
        add_filter( 'query_vars', [ $this, 'add_query_var' ] );
        add_action( 'template_redirect', [ $this, 'maybe_confirm' ] );
    }

    /** @param array<int,string> $vars */
    public function add_query_var( array $vars ): array
    {
        $vars[] = DoubleOptin::QUERY_VAR;
        return $vars;
    }

    public function maybe_confirm(): void
    {
        $token = (string) get_query_var( DoubleOptin::QUERY_VAR, '' );
        if ( '' === $token ) {
            return;
        }

        $lead_id = DoubleOptin::lead_id_from_token( $token );
        $outcome = null === $lead_id ? 'missing' : DoubleOptin::confirm( $lead_id );

        $args = [
            'response'  => 'missing' === $outcome ? 400 : 200,
            'link_url'  => home_url( '/' ),
            'link_text' => __( 'Back to the site', 'quizably' ),
        ];

        if ( 'missing' === $outcome ) {
            wp_die(
                esc_html__( 'This confirmation link is not valid. It may have been copied incorrectly.', 'quizably' ),
                esc_html__( 'Confirmation link not valid', 'quizably' ),
                $args
            );
        }

        wp_die(
            esc_html(
                'already' === $outcome
                    ? __( 'This email address is already confirmed.', 'quizably' )
                    : __( 'Thank you. Your email address is confirmed.', 'quizably' )
            ),
            esc_html__( 'Subscription confirmed', 'quizably' ),
            $args
        );
    }
}
