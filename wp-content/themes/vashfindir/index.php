<?php
/**
 * Minimal placeholder theme template for vashfindir.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        the_content();
    }
} else {
    echo '<main>';
    echo '<h1>' . esc_html__( 'Vashfindir theme placeholder', 'vashfindir' ) . '</h1>';
    echo '<p>' . esc_html__( 'Content will appear here.', 'vashfindir' ) . '</p>';
    echo '</main>';
}

wp_footer();
?>
</body>
</html>
