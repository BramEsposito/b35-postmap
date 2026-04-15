<?php
/*
Plugin Name: Post Map
Plugin URI: http://bramesposito.com
Description: Displays a GitHub-style contribution map of published posts
Author: Bram Esposito
Author URI: http://bramesposito.com
Version: 1.0.0
Text Domain: b35-postmap
License: MIT License
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Query post counts grouped by date for the given date range.
 *
 * @param string $start_date Y-m-d
 * @param string $end_date   Y-m-d
 * @param array  $post_types
 * @return array  Associative array of date => count
 */
function b35_postmap_get_counts( string $start_date, string $end_date, array $post_types ): array {
    global $wpdb;

    $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
    $query_args   = array_merge( [ $start_date . ' 00:00:00', $end_date . ' 23:59:59' ], $post_types );

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(post_date) AS post_date, COUNT(*) AS count
             FROM {$wpdb->posts}
             WHERE post_status = 'publish'
               AND post_date >= %s
               AND post_date <= %s
               AND post_type IN ($placeholders)
             GROUP BY DATE(post_date)",
            ...$query_args
        ),
        ARRAY_A
    );

    $counts = [];
    foreach ( $results as $row ) {
        $counts[ $row['post_date'] ] = (int) $row['count'];
    }

    return $counts;
}

/**
 * Render the postmap grid HTML.
 */
function b35_postmap_render( array $atts ): string {
    $atts = shortcode_atts(
        [
            'weeks'      => 52,
            'post_types' => 'post',
        ],
        $atts,
        'postmap'
    );

    $weeks      = max( 1, (int) $atts['weeks'] );
    $post_types = array_map( 'trim', explode( ',', $atts['post_types'] ) );

    // Build the date range: end on the last Saturday so the grid ends on a full week.
    $today    = new DateTimeImmutable( 'today' );
    $end_date = $today->modify( 'Saturday this week' );
    if ( $end_date > $today ) {
        $end_date = $end_date->modify( '-7 days' );
    }
    $start_date = $end_date->modify( '-' . ( $weeks - 1 ) . ' weeks' )->modify( 'Sunday this week' );

    $counts = b35_postmap_get_counts(
        $start_date->format( 'Y-m-d' ),
        $end_date->format( 'Y-m-d' ),
        $post_types
    );

    $max_count = $counts ? max( $counts ) : 1;

    // Build week columns from start to end.
    $output  = '<div class="b35-postmap" role="img" aria-label="' . esc_attr__( 'Post activity map', 'b35-postmap' ) . '">';
    $output .= '<div class="b35-postmap__grid">';

    $current = clone $start_date;
    $interval = new DateInterval( 'P1D' );

    while ( $current <= $end_date ) {
        $output .= '<div class="b35-postmap__week">';

        for ( $d = 0; $d < 7; $d++ ) {
            $date_str = $current->format( 'Y-m-d' );
            $count    = $counts[ $date_str ] ?? 0;
            $level    = $count === 0 ? 0 : (int) ceil( ( $count / $max_count ) * 4 );
            $label    = $count === 0
                ? sprintf( esc_attr__( 'No posts on %s', 'b35-postmap' ), $current->format( 'M j, Y' ) )
                : sprintf( esc_attr__( '%d post(s) on %s', 'b35-postmap' ), $count, $current->format( 'M j, Y' ) );

            $output .= sprintf(
                '<div class="b35-postmap__day" data-level="%d" data-count="%d" title="%s"></div>',
                $level,
                $count,
                $label
            );

            $current = $current->add( $interval );
            if ( $current > $end_date && $d < 6 ) {
                // Pad remaining days in the last week with empty cells.
                for ( $p = $d + 1; $p < 7; $p++ ) {
                    $output .= '<div class="b35-postmap__day b35-postmap__day--empty"></div>';
                }
                break;
            }
        }

        $output .= '</div>'; // .b35-postmap__week
    }

    $output .= '</div>'; // .b35-postmap__grid
    $output .= '</div>'; // .b35-postmap

    return $output;
}
add_shortcode( 'postmap', 'b35_postmap_render' );

function b35_postmap_enqueue_styles(): void {
    wp_enqueue_style(
        'b35-postmap',
        plugins_url( 'assets/css/postmap.css', __FILE__ ),
        [],
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/postmap.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'b35_postmap_enqueue_styles' );
