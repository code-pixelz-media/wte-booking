<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor Booking Widget with inline calendar display.
 */
class Elementor_Booking_Widget extends \Elementor\Widget_Base
{
    /**
     * Constructor to enqueue scripts.
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_booking_scripts']);
    }

    /**
     * Enqueue required scripts and styles.
     */
    public function enqueue_booking_scripts()
    {
        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
        );

        wp_enqueue_style(
            'booking-widget-css',
            plugin_dir_url(__FILE__) . '../assets/css/booking-widget.css',
            ['flatpickr-css'],
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/booking-widget.css')
        );

        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js',
            [],
            false,
            true
        );

        // Enqueue the script for both editor and frontend
        wp_enqueue_script(
            'booking-widget-js',
            plugin_dir_url(__FILE__) . '../assets/js/booking-widget.js',
            ['jquery', 'flatpickr-js', 'elementor-frontend'],
            false,
            true
        );



        wp_localize_script('booking-widget-js', 'booking_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('booking_nonce'),
            'is_editor' => \Elementor\Plugin::$instance->editor->is_edit_mode()
        ]);

    }

    /**
     * Retrieve widget name.
     */
    public function get_name(): string
    {
        return 'booking';
    }

    /**
     * Retrieve widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Booking Calendar', 'elementor-booking-widget');
    }

    /**
     * Retrieve widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-calendar';
    }

    /**
     * Retrieve widget categories.
     */
    public function get_categories(): array
    {
        return ['general'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'elementor-booking-widget'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'calendar_title',
            [
                'label' => esc_html__('Calendar Title', 'elementor-booking-widget'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Select Your Booking Date', 'elementor-booking-widget'),
            ]
        );
        // $this->add_control(
        //     'trip_id',
        //     [
        //         'label' => esc_html__('Calendar Title', 'elementor-booking-widget'),
        //         'type' => \Elementor\Controls_Manager::TEXT,
        //         'default' => esc_html__('Select Your Booking Date', 'elementor-booking-widget'),
        //     ]
        // );
        $this->add_control(
            'default_date',
            [
                'label' => esc_html__('Default Date', 'elementor-booking-widget'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'today' => esc_html__('Today', 'elementor-booking-widget'),
                    'tomorrow' => esc_html__('Tomorrow', 'elementor-booking-widget'),
                    'none' => esc_html__('No default', 'elementor-booking-widget'),
                ],
                'default' => 'today',
            ]
        );

        $this->end_controls_section();

        // Style section for the calendar
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Calendar Styles', 'elementor-booking-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'calendar_width',
            [
                'label' => esc_html__('Calendar Width', 'elementor-booking-widget'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 600,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .booking-calendar-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }
    protected function content_template()
    {
?>
        <#
            var defaultDate=settings.default_date==='today' ? 'today' :
            (settings.default_date==='tomorrow' ? 'tomorrow' : '' );
            #>
            <div class="booking-widget">
                <# if (settings.calendar_title) { #>
                    <h3 class="booking-calendar-title">{{{ settings.calendar_title }}}</h3>
                    <# } #>

                        <div class="booking-calendar-container" style="border: 1px dashed #ccc; padding: 20px;">
                            <?php _e('Date picker will be displayed on the frontend', 'elementor-booking-widget'); ?>
                        </div>

                        <div id="packages-list"></div>
            </div>
        <?php
    }
    /**
     * Render the widget output on the frontend.
     */
    protected function render(): void 
    {
        $settings = $this->get_settings_for_display();
        $default_date = $settings['default_date'] === 'today' ? 'today' : ($settings['default_date'] === 'tomorrow' ? 'tomorrow' : '');
        ?>
            <div class="wte__custom-container">
                <div class="wte__custom-booking-form">
                    <div class="booking-widget">
                        <?php if (!empty($settings['calendar_title'])) : ?>
                            <h3 class="booking-calendar-title"><?php echo esc_html($settings['calendar_title']); ?></h3>
                        <?php endif; ?>
                        <div class="booking-calendar-container"
                            id="booking-calendar-<?php echo esc_attr($this->get_id()); ?>"
                            data-default-date="<?php echo esc_attr($default_date); ?>">
                        </div>
                        <input type="hidden" id="selected-booking-date" name="selected_date">
                        <div id="packages-list"></div>
                    </div>
                    <div class="booking-packages" >
                        1764{trip_id}==> packages_ids(meta_key) ==> a:3:{i:0;i:1766;i:1;i:1765;i:2;i:1901;}
                        The packages are stored in wp_posts tables with post_type of 'trip-packages'.
                    </div>
                    <div class="wte__custom-booking-btn-container">
                        <button class="btn btn-primary" id="wte__custom-booking-continue">Continue</button>
                    </div>
                </div>
                <div class="wte__custom-booking-summary">
                    <div class="wte__custom_booking-summary">
                        <div id="wte__custom_booking-summary">
                            <h5 class="wte__custom-booking-block-title">Booking Summary</h5>
                            <h2 class="wte__custom-booking-trip-title">Ha Giang Loop</h2>
                            <div class="wte__custom-booking-dates">
                                <p class="wte__custom-booking-starting-date">
                                    <strong>Starting Date:</strong><span class="wte__custom-booking-date"></span>
                                </p>
                            </div>
                            <div class="wte__custom-booking-summary-info">
                                <h5 class="wte__custom-booking-summary-info-title">Package: Ha Giang Loop + Thon Tha Village</h5>
                                <div class="wte__custom-booking-trip-info"></div>
                                <div class="total-amount">
                                    <p class="price">
                                        <span class="total-text">Total :</span>
                                        <span>
                                            <span class="wte__custom-currency-code currency">$</span>
                                            <strong class="wte__custom-price amount">0</strong>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <?php
    }
}
