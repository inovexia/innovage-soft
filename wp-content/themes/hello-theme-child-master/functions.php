<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// Create Shortcode for search

function innovage_custom_search_shortcode() {
    $svg_url = 'https://innovagesoftware.website/dev/innovage/wp-content/uploads/2025/05/search-icon.svg';

    ob_start();
    ?>
    <style>
   .innovage-search-wrapper {
    display: flex;
    align-items: center;
    justify-content: flex-end; /* Keep the icon to the right */
    position: relative;
}

.innovage-search-form {
    display: flex;
    align-items: center;
    overflow: hidden;
    transition: width 0.3s ease;
    width: 24px;
    background: none;
    border: none;
    position: absolute;
    right: 0; /* Keep it at the right, but expand left */
}

.innovage-search-form.expanded {
    width: 150px; /* Adjust width as needed */
}

.innovage-search-form input[type="search"] {
    display: none;
    padding: 6px 8px;
    border: none;
    background: transparent;
    color: white;
    width: 100%;
    font-size: 14px;
    outline: none;
}

.innovage-search-form.expanded input[type="search"] {
    display: block;
}

.innovage-search-button {
    background: none;
    border: none;
    padding: 0;
    margin-left: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
}

.innovage-search-button img {
    width: 20px;
    height: 20px;
    filter: brightness(0) invert(1); /* make it white on dark bg */
}


    </style>

    <div class="innovage-search-wrapper">
        <form class="innovage-search-form" method="get" action="<?php echo home_url('/'); ?>" onsubmit="return handleInnovageSubmit(event)">
            <input type="search" name="s" placeholder="Search...">
            <button type="submit" class="innovage-search-button" aria-label="Search">
                <img src="<?php echo esc_url($svg_url); ?>" alt="Search Icon">
            </button>
        </form>
    </div>

    <script>
    function handleInnovageSubmit(event) {
        const form = event.target;
        const input = form.querySelector('input[name="s"]');
        const isExpanded = form.classList.contains('expanded');

        if (!isExpanded) {
            event.preventDefault();
            form.classList.add('expanded');
            input.focus();
            return false;
        } else if (input.value.trim() === '') {
            event.preventDefault();
            input.focus();
            return false;
        }

        return true;
    }

    document.addEventListener('click', function(e) {
        const form = document.querySelector('.innovage-search-form');
        if (!form.contains(e.target)) {
            form.classList.remove('expanded');
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('innovage_search', 'innovage_custom_search_shortcode');

// Custom JS file enque

function hello_child_enqueue_custom_scripts() {
    wp_enqueue_script(
        'custom-script', // Handle
        get_stylesheet_directory_uri() . '/assets/js/custom.js', // Path
        array('jquery'), // Dependencies
        filemtime(get_stylesheet_directory() . '/assets/js/custom.js'), // Version based on last modified time
        true // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'hello_child_enqueue_custom_scripts');

// Font Awesome css
function enqueue_custom_child_styles() {
    wp_enqueue_style(
        'fontawesome-style',
        get_stylesheet_directory_uri() . '/assets/css/font-awesome.min.css',
        array(),
        '1.0', 
        'all'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_child_styles');


// Get all services using shortcode
function list_services_shortcode() {
    ob_start(); // Start output buffering

    $args = array(
        'post_type'      => 'services',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'DESC'
    );

    $services = new WP_Query($args);

    if ($services->have_posts()) {
        echo '<div class="custom-services-list">';
        while ($services->have_posts()) {
            $services->the_post();

            // Get the icon URL (ACF returns a string since it's set to Image URL)
            $icon_url = get_field('service_icon_red');

            ?>
            <div class="service-item">
                <?php if ($icon_url): ?>
                    <div class="service-icon">
                        <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?> Icon" />
                    </div>
                <?php endif; ?>
				<h3><a href="<?php the_permalink(); ?>" class="read-more"><?php the_title(); ?></a></h3>
                <div class="service-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 44, '...'); ?></div>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No services found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('list_services', 'list_services_shortcode');
