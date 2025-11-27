<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * SEO and Schema Markup Output
 *
 * Generates and outputs Organization, WebSite, and Post/Page specific JSON-LD schema.
 *
 * @package buildx
 */

add_action( 'wp_head', 'buildx_output_jsonld_schema', 5 );

function buildx_output_jsonld_schema() {
    if ( is_admin() ) {
        return;
    }

    $schemas = [];

    // --- Organization / LocalBusiness ---
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type'    => ['Organization', 'LocalBusiness'],
        '@id'      => 'https://buildx.com/#organization',
        'name'     => 'BuildX',
        'url'      => 'https://buildx.com/',
        'telephone'=> '+1-781-627-7000',
        'logo'     => 'https://buildx.com/wp-content/uploads/2024/XX/buildx-header-logo.png',
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => '1 Marion Dr, Unit 2B',
            'addressLocality' => 'Carver',
            'addressRegion'   => 'MA',
            'postalCode'      => '02330',
            'addressCountry'  => 'US',
        ],
        'sameAs'   => [
            'https://www.facebook.com/BuildXUSA/',
            'https://www.instagram.com/buildx_usa/',
            'https://www.youtube.com/@BuildX_USA',
            'https://www.linkedin.com/company/buildxusa',
        ],
    ];

    // --- WebSite (mostly for homepage / global search) ---
    $schemas[] = [
        '@context'       => 'https://schema.org',
        '@type'          => 'WebSite',
        '@id'            => 'https://buildx.com/#website',
        'url'            => 'https://buildx.com/',
        'name'           => 'BuildX | Your Building Experts',
        'publisher'      => [ '@id' => 'https://buildx.com/#organization' ],
        'potentialAction'=> [
            '@type'       => 'SearchAction',
            'target'      => 'https://buildx.com/?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // --- Page / Post specific schema ---
    if ( is_singular() ) {
        $post_id    = get_the_ID();
        $title      = get_the_title( $post_id );
        $permalink  = get_permalink( $post_id );
        // Note: The original code uses get_the_excerpt() without wp_strip_all_tags, which is fine for schema but should be noted.
        $excerpt    = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : ''; 
        $published  = get_the_date( 'c', $post_id );
        $modified   = get_the_modified_date( 'c', $post_id );
        $author_id  = get_post_field( 'post_author', $post_id );
        $author_name= get_the_author_meta( 'display_name', $author_id );

        // Try to get a featured image
        $image_url = null;
        if ( has_post_thumbnail( $post_id ) ) {
            $image_data = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
            if ( ! empty( $image_data[0] ) ) {
                $image_url = $image_data[0];
            }
        }

        // Default WebPage node
        $page_schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebPage',
            '@id'             => $permalink . '#webpage',
            'url'             => $permalink,
            'headline'        => $title,
            'name'            => $title,
            'description'     => $excerpt,
            'isPartOf'        => [ '@id' => 'https://buildx.com/#website' ],
            'datePublished'   => $published,
            'dateModified'    => $modified,
            'mainEntityOfPage'=> $permalink,
        ];
        if ( $image_url ) {
            $page_schema['image'] = [
                '@type' => 'ImageObject',
                'url'   => $image_url,
            ];
        }
        $schemas[] = $page_schema;

        // For posts (Learning Center, testimonials, etc.), add BlogPosting
        if ( is_singular( 'post' ) ) {
            $blog_schema = [
                '@context'      => 'https://schema.org',
                '@type'         => 'BlogPosting',
                '@id'           => $permalink . '#blogposting',
                'headline'      => $title,
                'description'   => $excerpt,
                'url'           => $permalink,
                'datePublished' => $published,
                'dateModified'  => $modified,
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $author_name,
                ],
                'publisher'     => [
                    '@type' => 'Organization',
                    '@id'   => 'https://buildx.com/#organization',
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id'   => $permalink . '#webpage',
                ],
            ];
            if ( $image_url ) {
                $blog_schema['image'] = [
                    '@type' => 'ImageObject',
                    'url'   => $image_url,
                ];
            }
            $schemas[] = $blog_schema;
        }

        // Later: add a branch here for your ADU floor-plan CPT (e.g. 'floor_plan')
    }

    // Output all schemas in one JSON-LD block
    echo "\n<script type=\"application/ld+json\">\n";
    // PERFORMANCE FIX: Removed JSON_PRETTY_PRINT to reduce payload size.
    echo wp_json_encode( $schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); 
    echo "\n</script>\n";
}
// No PHP closing tag