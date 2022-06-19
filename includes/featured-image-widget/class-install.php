<?php

/**
 * @package   FFWP Login Fields Legend
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_FeaturedImageWidget_Install extends WP_Widget
{
    const DEFAULT_IMAGE_SIZES = [
        'thumbnail',
        'medium',
        'large'
    ];

    /** @var string $plugin_text_domain */
    private $plugin_text_domain = 'ffwp';

    /**
     * FFWP_LoginFieldsLegend_Insert constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'featured_image_widget',
            __('Featured Image Widget', $this->plugin_text_domain),
            [
                'description' => __('Display the featured image of the current post in a widget.', $this->plugin_text_domain)
            ]
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

?>
        <div class="featured-image-wrapper">
            <?php the_post_thumbnail($instance['image_size']); ?>
        </div>
    <?php

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $sizes    = get_intermediate_image_sizes();
        $selected = $instance['image_size'];
    ?>
        <p>
            <label for="<?php echo $this->get_field_id('image_size'); ?>"><?php _e('Image Size:', $this->plugin_text_domain); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('image_size'); ?>" name="<?php echo $this->get_field_name('image_size'); ?>">
                <?php foreach ($sizes as $size) : ?>
                    <option value="<?php echo $size; ?>" <?php $selected == $size ? 'selected' : ''; ?>><?php echo ucfirst($size); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
<?php
    }

    public function update($new, $old)
    {
        $instance = [];

        foreach ($new as $option => $new_value) {
            $instance[$option] = (!empty($new_value)) ? strip_tags($new_value) : '';
        }

        return $instance;
    }
}
