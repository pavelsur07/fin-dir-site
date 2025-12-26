<?php if (!defined('ABSPATH')) exit; ?>
<?php
// Expected args:
// - kicker (string)
// - title (string)
// - lead (string) допускаем <br>
// - primary: ['label','url']
// - secondary: ['label','url']
// - media: ['type' => 'placeholder'|'image', ...]
//   placeholder: ['label','sub','ratio' => 'wide']
//   image: ['desktop','tablet','mobile','alt','ratio' => 'wide']

$kicker = isset($args['kicker']) ? (string)$args['kicker'] : '';
$title  = isset($args['title']) ? (string)$args['title'] : get_the_title();
$lead   = isset($args['lead']) ? (string)$args['lead'] : (get_the_excerpt() ?: '');

$primary = isset($args['primary']) && is_array($args['primary']) ? $args['primary'] : [];
$secondary = isset($args['secondary']) && is_array($args['secondary']) ? $args['secondary'] : [];

$media = isset($args['media']) && is_array($args['media']) ? $args['media'] : ['type' => 'placeholder'];
$media_type = isset($media['type']) ? (string)$media['type'] : 'placeholder';

$ratio = isset($media['ratio']) ? (string)$media['ratio'] : 'wide'; // wide = 16:9
$ratio_class = ($ratio === 'wide') ? 'vf-hero3__media--wide' : 'vf-hero3__media--wide';
?>

<section class="vf-hero3" aria-labelledby="vf-hero3-title">
    <div class="container">
        <div class="vf-hero3__card">
            <div class="vf-hero3__grid">
                <!-- Content -->
                <div class="vf-hero3__content">
                    <?php if ($kicker !== ''): ?>
                        <span class="vf-hero3__kicker"><?php echo esc_html($kicker); ?></span>
                    <?php endif; ?>

                    <h1 class="vf-hero3__title" id="vf-hero3-title"><?php echo esc_html($title); ?></h1>

                    <?php if ($lead !== ''): ?>
                        <p class="vf-hero3__lead">
                            <?php echo wp_kses_post(nl2br(esc_html($lead))); ?>
                        </p>
                    <?php endif; ?>

                    <div class="vf-hero3__actions">
                        <?php if (!empty($primary['label']) && !empty($primary['url'])): ?>
                            <a class="btn btn-cta" href="<?php echo esc_url($primary['url']); ?>">
                                <?php echo esc_html($primary['label']); ?>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($secondary['label']) && !empty($secondary['url'])): ?>
                            <a class="btn btn-cta-secondary" href="<?php echo esc_url($secondary['url']); ?>">
                                <?php echo esc_html($secondary['label']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Media -->
                <div class="vf-hero3__media">
                    <div class="vf-hero3__media-wrap">
                        <?php if ($media_type === 'image' && !empty($media['desktop'])): ?>
                            <?php
                            $desktop = (string)$media['desktop'];
                            $tablet  = !empty($media['tablet']) ? (string)$media['tablet'] : $desktop;
                            $mobile  = !empty($media['mobile']) ? (string)$media['mobile'] : $tablet;
                            $alt     = !empty($media['alt']) ? (string)$media['alt'] : '';
                            ?>
                            <picture class="vf-hero3__picture <?php echo esc_attr($ratio_class); ?>">
                                <source media="(max-width: 575.98px)" srcset="<?php echo esc_url($mobile); ?>">
                                <source media="(max-width: 991.98px)" srcset="<?php echo esc_url($tablet); ?>">
                                <img
                                    class="vf-hero3__image"
                                    src="<?php echo esc_url($desktop); ?>"
                                    alt="<?php echo esc_attr($alt); ?>"
                                    loading="eager"
                                    decoding="async"
                                    fetchpriority="high"
                                >
                            </picture>
                        <?php else: ?>
                            <?php
                            $pl_label = !empty($media['label']) ? (string)$media['label'] : 'Фото 16:9';
                            $pl_sub   = !empty($media['sub']) ? (string)$media['sub'] : '';
                            ?>
                            <div class="vf-hero3__placeholder <?php echo esc_attr($ratio_class); ?>" role="img" aria-label="<?php echo esc_attr($pl_label); ?>">
                                <div class="vf-hero3__placeholder-inner">
                                    <div class="vf-hero3__placeholder-title"><?php echo esc_html($pl_label); ?></div>
                                    <?php if ($pl_sub !== ''): ?>
                                        <span class="vf-hero3__placeholder-sub"><?php echo esc_html($pl_sub); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
