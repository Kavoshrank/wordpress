<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Client {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-jalali.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-rest-controller.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-post-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-content-audit-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-categories-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-product-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-media-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-links-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-redirects-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-comment-handler.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-audio-player.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-admin-notifier.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-social-publisher.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-api-client.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-page-optimizer.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-runtime-healer.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-404-monitor.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-schema.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-local-seo.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-eeat.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-cro.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-copilot.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-review-digest.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-diagnostics.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-llms-txt.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-category-seo.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-bot-governor.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-storefront-features.php';
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-updater.php';

        if ( is_admin() ) {
            require_once KAVOSHRANK_PATH . 'admin/class-kavoshrank-admin-pages.php';
        }
    }

    private function init_hooks() {
        add_action( 'rest_api_init', array( new KavoshRank_REST_Controller(), 'register_routes' ) );

        $diagnostics = new KavoshRank_Diagnostics();

        $llms_txt = new KavoshRank_LLMs_Txt();
        $llms_txt->init();

        $category_seo = new KavoshRank_Category_SEO();
        $category_seo->init();

        $bot_governor = new KavoshRank_Bot_Governor();
        $bot_governor->init();

        $audio_player = new KavoshRank_Audio_Player();
        add_filter( 'the_content', array( $audio_player, 'render_single_podcast_player' ) );

        $copilot = new KavoshRank_Copilot();
        $copilot->init();

        $review_digest = new KavoshRank_Review_Digest();
        $review_digest->init();

        $notifier = new KavoshRank_Admin_Notifier();
        $notifier->init();

        $social_publisher = new KavoshRank_Social_Publisher();
        $social_publisher->init();

        $page_optimizer = new KavoshRank_Page_Optimizer();
        $page_optimizer->init();

        $runtime_healer = new KavoshRank_Runtime_Healer();
        $runtime_healer->init();

        $monitor_404 = new KavoshRank_404_Monitor();
        $monitor_404->init();

        $schema = new KavoshRank_Schema();
        $schema->init();

        $local_seo = new KavoshRank_Local_SEO();
        $local_seo->init();

        $eeat = new KavoshRank_EEAT();
        $eeat->init();

        $cro = new KavoshRank_CRO();
        $cro->init();

        KavoshRank_Comment_REST_Handler::init();

        $updater = new KavoshRank_Updater();
        $updater->init();

        if ( is_admin() ) {
            $admin_pages = new KavoshRank_Admin_Pages();
            $admin_pages->init();
        }
    }
}
