<?php
/**
 * The chrome-takeover template: ChattyPage header fragment → WordPress content → ChattyPage
 * footer fragment. WordPress decides WHAT renders (it routed the request and owns the loop);
 * ChattyPage decides how it LOOKS. wp_head()/wp_footer() run normally so other plugins work.
 *
 * Content branches:
 *  - singular (page/post): title + the_content inside the shared .chatty-article container
 *    (styled by the article-css fragment; sections placed in the content render through their
 *    own block/shortcode/widget as always).
 *  - archives/blog index/search: the loop as a .chatty-article-list (the same list vocabulary
 *    article.css already styles for our latest-articles block).
 *  - 404: a minimal styled message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'chattypage-takeover' ); ?>>
<?php wp_body_open(); ?>

<div class="chattypage-section" data-chattypage-fragment="header"><?php
	echo ChattyPage_Renderer::fragment( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- own design fragment, same trust model as a theme file
?></div>

<main class="chattypage-takeover__content">
<?php if ( is_singular() ) : ?>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<div class="chatty-article" data-chatty-article>
				<header class="chatty-article__header">
					<h1 class="chatty-article__title"><?php the_title(); ?></h1>
					<?php if ( 'post' === get_post_type() ) : ?>
						<div class="chatty-article__meta"><?php echo esc_html( get_the_date() ); ?></div>
					<?php endif; ?>
				</header>
				<div class="chatty-article__body"><?php the_content(); ?></div>
			</div>
		</article>
		<?php
		if ( 'post' === get_post_type() && ( comments_open() || get_comments_number() ) ) {
			// Comments live in the same reading column + typography as the article body.
			echo '<div class="chatty-article"><div class="chatty-article__body">';
			comments_template();
			echo '</div></div>';
		}
	endwhile;
	?>
<?php elseif ( have_posts() ) : ?>
	<div class="chatty-article">
		<header class="chatty-article__header">
			<h1 class="chatty-article__title"><?php
				if ( is_search() ) {
					/* translators: %s: search query */
					printf( esc_html__( 'Search results for "%s"', 'chattypage' ), esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					esc_html_e( 'Latest posts', 'chattypage' );
				}
			?></h1>
		</header>
		<div class="chatty-article-list">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<a class="chatty-article-list__item" href="<?php the_permalink(); ?>">
					<span class="chatty-article-list__title"><?php the_title(); ?></span>
					<span class="chatty-article-list__date"><?php echo esc_html( get_the_date() ); ?></span>
				</a>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	</div>
<?php else : ?>
	<div class="chatty-article">
		<header class="chatty-article__header">
			<h1 class="chatty-article__title"><?php esc_html_e( 'Nothing here', 'chattypage' ); ?></h1>
		</header>
		<div class="chatty-article__body"><p><?php esc_html_e( 'The page you are looking for does not exist.', 'chattypage' ); ?></p></div>
	</div>
<?php endif; ?>
</main>

<div class="chattypage-section" data-chattypage-fragment="footer"><?php
	echo ChattyPage_Renderer::fragment( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- own design fragment
?></div>

<?php wp_footer(); ?>
</body>
</html>
