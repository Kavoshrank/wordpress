<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Storefront Features Manager
 * 
 * Provides an inventory and centralized control of all front-end features that modify
 * the customer-facing storefront (HTML, Schema, bots, styling, assets).
 */
class KavoshRank_Storefront_Features {

    /**
     * Get inventory of all storefront features with their current status and metadata
     */
    public static function get_all_features() {
        return array(
            'local_seo' => array(
                'key'         => 'local_seo',
                'option'      => 'kavoshrank_enable_local_seo',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_enable_local_seo', '0' ) === '1',
                'title'       => __( 'اسکیما و سئوی محلی (LocalBusiness Schema)', 'kavoshrank' ),
                'category'    => __( 'Structured Data', 'kavoshrank' ),
                'impact'      => __( 'تزریق ساختار داده استاندارد LocalBusiness به صفحه نخست تنها در صورت تکمیل نشانی و تلفن.', 'kavoshrank' ),
                'location'    => __( 'HTML <head> (صفحه نخست)', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-local-seo' ),
            ),
            'schema_products' => array(
                'key'         => 'schema_products',
                'option'      => 'kavoshrank_enable_schema',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_enable_schema', '0' ) === '1',
                'title'       => __( 'اسکیما مقالات و محصولات (Schema.org)', 'kavoshrank' ),
                'category'    => __( 'Structured Data', 'kavoshrank' ),
                'impact'      => __( 'تولید ساختار داده منسجم TechArticle و Product برای نمایش ستاره‌ها و قیمت در نتایج گوگل.', 'kavoshrank' ),
                'location'    => __( 'HTML <head> (تمامی مقالات و محصولات)', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-schema' ),
            ),
            'override_wc_schema' => array(
                'key'         => 'override_wc_schema',
                'option'      => 'kavoshrank_override_wc_schema',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_override_wc_schema', '0' ) === '1',
                'title'       => __( 'جایگزینی اسکیمای پیش‌فرض ووکامرس', 'kavoshrank' ),
                'category'    => __( 'WooCommerce', 'kavoshrank' ),
                'impact'      => __( 'حذف اسکیمای محصول ووکامرس و جایگزینی آن با اسکیمای کاوش‌رنک. بدون فعال‌سازی، اسکیمای ووکامرس دست‌نخورده باقی می‌ماند.', 'kavoshrank' ),
                'location'    => __( 'WooCommerce Filters', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-schema' ),
            ),
            'eeat_badge' => array(
                'key'         => 'eeat_badge',
                'option'      => 'kavoshrank_enable_eeat_box',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_enable_eeat_box', '0' ) === '1',
                'title'       => __( 'باکس شفافیت و بازبینی محتوا (E-E-A-T)', 'kavoshrank' ),
                'category'    => __( 'Content Signals', 'kavoshrank' ),
                'impact'      => __( 'تزریق باکس شفاف هویت نویسنده، تاریخ به‌روزرسانی و بازبین کیفی در بالای مقالات وبلاگ.', 'kavoshrank' ),
                'location'    => __( 'ابتدای متن مقالات (the_content)', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-eeat' ),
            ),
            'cro_widgets' => array(
                'key'         => 'cro_widgets',
                'option'      => 'kavoshrank_enable_cro_widgets',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_enable_cro_widgets', '0' ) === '1',
                'title'       => __( 'ویجت‌های افزایش نرخ تبدیل (CRO Widgets)', 'kavoshrank' ),
                'category'    => __( 'Conversion', 'kavoshrank' ),
                'impact'      => __( 'نمایش دکمه‌های اقدام به عمل و مشاوره تلفنی متناسب با دسته مقاله بعد از پاراگراف سوم.', 'kavoshrank' ),
                'location'    => __( 'میانه مقالات (the_content)', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-cro' ),
            ),
            'bot_firewall' => array(
                'key'         => 'bot_firewall',
                'option'      => 'kavoshrank_bot_firewall_enabled',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_bot_firewall_enabled', '0' ) === '1',
                'title'       => __( 'فایروال ترافیک و ناظر ربات‌های AI (Bot Governor)', 'kavoshrank' ),
                'category'    => __( 'Bot Governance', 'kavoshrank' ),
                'impact'      => __( 'الحاق قوانین مجاز AI به robots.txt و مسدودسازی سریع اسکریپرهای متجاوز بدون مصرف منابع سرور.', 'kavoshrank' ),
                'location'    => __( 'Virtual robots.txt & HTTP Header', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-bot-firewall' ),
            ),
            'auto_linking' => array(
                'key'         => 'auto_linking',
                'option'      => 'kavoshrank_enable_auto_linking',
                'default'     => '1',
                'is_active'   => get_option( 'kavoshrank_enable_auto_linking', '1' ) === '1',
                'title'       => __( 'لینک‌سازی داخلی هوشمند (Auto Linking)', 'kavoshrank' ),
                'category'    => __( 'SEO Architecture', 'kavoshrank' ),
                'impact'      => __( 'پیوند خودکار و امن کلمات کلیدی مرتبط به صفحات هدف طبق معماری پیوند خوشه‌ای (Silo).', 'kavoshrank' ),
                'location'    => __( 'متن مقالات (the_content)', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-autolink' ),
            ),
            'webp_rewrite' => array(
                'key'         => 'webp_rewrite',
                'option'      => 'kavoshrank_enable_webp_rewrite',
                'default'     => '1',
                'is_active'   => get_option( 'kavoshrank_enable_webp_rewrite', '1' ) === '1',
                'title'       => __( 'تحویل نسل جدید تصاویر WebP', 'kavoshrank' ),
                'category'    => __( 'Performance', 'kavoshrank' ),
                'impact'      => __( 'جایگزینی هوشمند تگ‌های تصویر با نسخه فشرده WebP در خروجی HTML برای افزایش چشمگیر سرعت.', 'kavoshrank' ),
                'location'    => __( 'HTML Page Output', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'preload_vazirmatn' => array(
                'key'         => 'preload_vazirmatn',
                'option'      => 'kavoshrank_preload_vazirmatn',
                'default'     => '1',
                'is_active'   => get_option( 'kavoshrank_preload_vazirmatn', '1' ) === '1',
                'title'       => __( 'پیش‌بارگذاری فونت وزیرمتن (Typography Preload)', 'kavoshrank' ),
                'category'    => __( 'Performance', 'kavoshrank' ),
                'impact'      => __( 'پیش‌بارگذاری تگ link rel=preload برای فونت فارسی جهت حذف جهش چیدمان (CLS = 0).', 'kavoshrank' ),
                'location'    => __( 'HTML <head>', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'runtime_healer' => array(
                'key'         => 'runtime_healer',
                'option'      => 'kavoshrank_runtime_healer_enabled',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_runtime_healer_enabled', '0' ) === '1',
                'title'       => __( 'خودترمیمی زنده خروجی HTML (Runtime Healer)', 'kavoshrank' ),
                'category'    => __( 'Site Healer', 'kavoshrank' ),
                'impact'      => __( 'کلید اصلی بافر خروجی صفحه؛ تا زمانی که خاموش است هیچ‌یک از اصلاحات زیر اجرا نمی‌شوند.', 'kavoshrank' ),
                'location'    => __( 'HTML Page Output', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'healer_font_preload' => array(
                'key'         => 'healer_font_preload',
                'option'      => 'kavoshrank_healer_font_preload',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_healer_font_preload', '0' ) === '1',
                'title'       => __( 'خودترمیمی: Preconnect و Preload فونت', 'kavoshrank' ),
                'category'    => __( 'Site Healer', 'kavoshrank' ),
                'impact'      => __( 'افزودن Preconnect فقط برای CDNهایی که صفحه از آن‌ها استفاده می‌کند و Preload فونت وزیرمتن در صورت استفاده.', 'kavoshrank' ),
                'location'    => __( 'HTML <head>', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'healer_lcp_preload' => array(
                'key'         => 'healer_lcp_preload',
                'option'      => 'kavoshrank_healer_lcp_preload',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_healer_lcp_preload', '0' ) === '1',
                'title'       => __( 'خودترمیمی: اولویت تصویر LCP و Lazy Load', 'kavoshrank' ),
                'category'    => __( 'Site Healer', 'kavoshrank' ),
                'impact'      => __( 'افزودن fetchpriority به نخستین تصویر محتوایی (به‌جز لوگو و تصاویر سربرگ) و Lazy Load برای تصاویر بعدی.', 'kavoshrank' ),
                'location'    => __( 'HTML Page Output', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'healer_smart_alt' => array(
                'key'         => 'healer_smart_alt',
                'option'      => 'kavoshrank_healer_smart_alt',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_healer_smart_alt', '0' ) === '1',
                'title'       => __( 'خودترمیمی: ترمیم متن جایگزین تصاویر', 'kavoshrank' ),
                'category'    => __( 'Site Healer', 'kavoshrank' ),
                'impact'      => __( 'افزودن متن جایگزین (alt) به تصاویری که فاقد آن هستند، به‌جز نشان‌های اعتماد.', 'kavoshrank' ),
                'location'    => __( 'HTML Page Output', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'healer_aria_labels' => array(
                'key'         => 'healer_aria_labels',
                'option'      => 'kavoshrank_healer_aria_labels',
                'default'     => '0',
                'is_active'   => get_option( 'kavoshrank_healer_aria_labels', '0' ) === '1',
                'title'       => __( 'خودترمیمی: برچسب دسترسی دکمه‌های آیکونی', 'kavoshrank' ),
                'category'    => __( 'Site Healer', 'kavoshrank' ),
                'impact'      => __( 'افزودن aria-label به همان تگ دکمه‌های آیکونی بدون برچسب، بدون تکرار یا تودرتو کردن دکمه.', 'kavoshrank' ),
                'location'    => __( 'HTML Page Output', 'kavoshrank' ),
                'docs_url'    => admin_url( 'admin.php?page=kavoshrank-settings#kr-tab-speed' ),
            ),
            'llms_txt' => array(
                'key'         => 'llms_txt',
                'option'      => '',
                'default'     => '1',
                'is_active'   => true, // Standard virtual endpoint enabled via rewrite rules
                'title'       => __( 'استاندارد فیدهای هوش مصنوعی (llms.txt & llms-full.txt)', 'kavoshrank' ),
                'category'    => __( 'GEO', 'kavoshrank' ),
                'impact'      => __( 'ارائه فایل‌های خلاصه‌سازی استاندارد Markdown برای موتورهای جستجوی نسلی (ChatGPT, Perplexity).', 'kavoshrank' ),
                'location'    => __( '/llms.txt و /llms-full.txt', 'kavoshrank' ),
                'docs_url'    => home_url( '/llms.txt' ),
            ),
        );
    }

    /**
     * REST Handler: Get all storefront features status
     */
    public static function handle_get_features() {
        $features = self::get_all_features();
        $total    = count( $features );
        $active   = 0;

        foreach ( $features as $feat ) {
            if ( ! empty( $feat['is_active'] ) ) {
                $active++;
            }
        }

        return new WP_REST_Response( array(
            'status'         => 'success',
            'total_features' => $total,
            'active_count'   => $active,
            'inactive_count' => $total - $active,
            'features'       => array_values( $features ),
        ), 200 );
    }

    /**
     * REST Handler: Update one or more storefront features
     */
    public static function handle_update_features( WP_REST_Request $request ) {
        $params   = $request->get_json_params();
        $features = self::get_all_features();

        if ( ! is_array( $params ) ) {
            return new WP_REST_Response( array(
                'status'  => 'error',
                'message' => 'Invalid parameters format',
            ), 400 );
        }

        foreach ( $params as $key => $is_enabled ) {
            if ( isset( $features[ $key ] ) && ! empty( $features[ $key ]['option'] ) ) {
                $val = ! empty( $is_enabled ) ? '1' : '0';
                update_option( $features[ $key ]['option'], $val );
            }
        }

        return self::handle_get_features();
    }
}
