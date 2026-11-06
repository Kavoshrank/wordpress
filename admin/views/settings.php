<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$secret_token      = (string) get_option( 'kavoshrank_secret_token', '' );
$api_url           = get_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' );
if ( empty( $api_url ) ) {
    $api_url = 'https://backend.kavoshrank.ir';
}
$disable_gutenberg = (bool) get_option( 'kavoshrank_disable_gutenberg', false );
$enable_eeat       = get_option( 'kavoshrank_enable_eeat_box', '0' ) === '1';
$eeat_badge        = get_option( 'kavoshrank_eeat_badge_text', __( 'بازبینی و ارزیابی محتوا', 'kavoshrank' ) );
$eeat_reviewer     = get_option( 'kavoshrank_eeat_reviewer_title', __( 'تیم تحریریه و کارشناسان', 'kavoshrank' ) );
$enable_cro        = get_option( 'kavoshrank_enable_cro_widgets', '0' ) === '1';
$contact_phone     = get_option( 'kavoshrank_contact_phone', '' );
$contact_url       = get_option( 'kavoshrank_contact_url', home_url( '/contact/' ) );
$enable_autolink   = get_option( 'kavoshrank_enable_auto_linking', '1' ) === '1';
$enable_schema     = get_option( 'kavoshrank_enable_schema', '0' ) === '1';
$override_schema   = get_option( 'kavoshrank_override_wc_schema', '0' ) === '1';
$return_days       = (string) get_option( 'kavoshrank_return_policy_days', '' );
$return_fees       = (string) get_option( 'kavoshrank_return_policy_fees', '' );
$shipping_rate     = (string) get_option( 'kavoshrank_shipping_rate', '' );
$shipping_handling = (string) get_option( 'kavoshrank_shipping_handling_days', '' );
$shipping_transit  = (string) get_option( 'kavoshrank_shipping_transit_days', '' );
$bot_firewall      = get_option( 'kavoshrank_bot_firewall_enabled', '0' ) === '1';
$bot_preset        = get_option( 'kavoshrank_bot_governor_preset', 'balanced' );
$preload_font      = get_option( 'kavoshrank_preload_vazirmatn', '1' ) === '1';
$webp_rewrite      = get_option( 'kavoshrank_enable_webp_rewrite', '1' ) === '1';
$enable_local_seo  = get_option( 'kavoshrank_enable_local_seo', '0' ) === '1';
$local_name        = (string) get_option( 'kavoshrank_local_business_name', '' );
$local_type        = (string) get_option( 'kavoshrank_local_business_type', 'LocalBusiness' );
$local_address     = (string) get_option( 'kavoshrank_local_business_address', '' );
$local_locality    = (string) get_option( 'kavoshrank_local_business_locality', '' );
$local_region      = (string) get_option( 'kavoshrank_local_business_region', '' );
$local_postal_code = (string) get_option( 'kavoshrank_local_business_postal_code', '' );
$local_country     = (string) get_option( 'kavoshrank_local_business_country', 'IR' );
$local_phone       = (string) get_option( 'kavoshrank_local_business_phone', '' );
$local_lat         = (string) get_option( 'kavoshrank_local_business_geo_lat', '' );
$local_lng         = (string) get_option( 'kavoshrank_local_business_geo_lng', '' );
$local_hours       = (string) get_option( 'kavoshrank_local_business_opening_hours', '' );
$local_logo        = (string) get_option( 'kavoshrank_local_business_logo', '' );
$local_price_range = (string) get_option( 'kavoshrank_local_business_price_range', '' );

$is_updated = isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true';
?>

<div class="wrap kavoshrank-settings-wrap <?php echo kavoshrank_is_rtl() ? 'kr-rtl' : 'kr-ltr'; ?>" dir="<?php echo kavoshrank_is_rtl() ? 'rtl' : 'ltr'; ?>">

    <!-- Top Header Banner -->
    <header class="kr-header-banner">
        <div class="kr-header-content">
            <div class="kr-header-icon-box">
                <svg class="kr-icon kr-icon-lg" viewBox="0 0 24 24">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                    <line x1="1" y1="14" x2="7" y2="14"></line>
                    <line x1="9" y1="8" x2="15" y2="8"></line>
                    <line x1="17" y1="16" x2="23" y2="16"></line>
                </svg>
            </div>
            <div class="kr-header-title-group">
                <h1>
                    <?php esc_html_e( 'تنظیمات و پل ارتباطی کاوش‌رنک', 'kavoshrank' ); ?>
                    <span class="kr-badge kr-badge-emerald">
                        <span class="kr-status-dot"></span>
                        <?php esc_html_e( 'نسخه', 'kavoshrank' ); ?> <?php echo esc_html( KAVOSHRANK_VERSION ); ?>
                    </span>
                </h1>
                <p><?php esc_html_e( 'پیکربندی ارتباط امن با پلتفرم مرکزی سئو، کنترل ویرایشگر و قواعد بهینه‌سازی زنده موتورهای جستجو', 'kavoshrank' ); ?></p>
            </div>
        </div>

        <div class="kr-header-actions">
            <!-- Multi-Language Switcher -->
            <a href="<?php echo esc_url( add_query_arg( 'kr_lang', kavoshrank_is_rtl() ? 'en_US' : 'fa_IR' ) ); ?>" class="kr-btn kr-btn-secondary" title="<?php echo kavoshrank_is_rtl() ? 'Switch interface to English' : 'تغییر زبان رابط به فارسی'; ?>">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span><?php echo kavoshrank_is_rtl() ? 'English' : 'فارسی'; ?></span>
            </a>
            <button type="button" id="kr-toggle-show-all" class="kr-btn kr-btn-secondary" title="<?php echo esc_attr__( 'تغییر نحوه نمایش بخش‌ها', 'kavoshrank' ); ?>">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span class="kr-view-text"><?php esc_html_e( 'نمایش یکپارچه', 'kavoshrank' ); ?></span>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank' ) ); ?>" class="kr-btn kr-btn-secondary">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                <?php esc_html_e( 'داشبورد کاوش‌رنک', 'kavoshrank' ); ?>
            </a>
        </div>
    </header>

    <!-- Success Notice upon WordPress Settings Save -->
    <?php if ( $is_updated ) : ?>
        <div class="kr-alert kr-alert-success">
            <div class="kr-alert-inner">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?php esc_html_e( 'تنظیمات با موفقیت ذخیره و در سرور اعمال گردید. تمامی ارتباطات و پارامترها به‌روزرسانی شدند.', 'kavoshrank' ); ?></span>
            </div>
            <button type="button" class="kr-alert-close" aria-label="<?php echo esc_attr__( 'بستن اعلان', 'kavoshrank' ); ?>">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    <?php endif; ?>

    <!-- Main Settings Form -->
    <form method="post" action="options.php" id="kavoshrank-settings-form">
        <?php
        settings_fields( 'kavoshrank_settings_group' );
        do_settings_sections( 'kavoshrank_settings_group' );
        ?>

        <div class="kr-settings-layout">

            <!-- Sidebar Navigation (Section-by-Section Tabs) -->
            <aside class="kr-sidebar-nav">
                <div class="kr-nav-header">
                    <h3>
                        <span><?php esc_html_e( 'بخش‌های تنظیمات', 'kavoshrank' ); ?></span>
                        <svg class="kr-icon kr-icon-sm" style="color:var(--kr-text-subtle);" viewBox="0 0 24 24">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </h3>
                </div>

                <div class="kr-nav-items">
                    <button type="button" class="kr-nav-btn active" data-target="section-platform">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'اتصال پلتفرم و احراز هویت', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'API Endpoint و کلید امنیتی', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-editor">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'ویرایشگر و نگارش محتوا', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'کلاسیک یا بلوکی گوتنبرگ', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-eeat">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <polyline points="9 12 11 14 15 10"></polyline>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'شاخص‌های کیفیت E-E-A-T', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'نشان اصالت و بازبینی محتوا', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-cro">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'ویجت‌های نرخ تبدیل (CRO)', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'باکس تماس و مشاوره مستقیم', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-seo">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                    <polyline points="2 17 12 22 22 17"></polyline>
                                    <polyline points="2 12 12 17 22 12"></polyline>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'سئو، اسکیما و لینک‌سازی', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'کدهای ساختاریافته و پیوندها', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-bots">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                    <circle cx="12" cy="5" r="2"></circle>
                                    <path d="M12 7v4"></path>
                                    <line x1="8" y1="16" x2="8.01" y2="16"></line>
                                    <line x1="16" y1="16" x2="16.01" y2="16"></line>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'فایروال ربات‌های هوش مصنوعی', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'کنترل robots.txt و اسکرپرها', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>

                    <button type="button" class="kr-nav-btn" data-target="section-performance">
                        <div class="kr-nav-btn-content">
                            <div class="kr-nav-btn-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                            </div>
                            <div class="kr-nav-btn-text">
                                <span class="kr-nav-btn-title"><?php esc_html_e( 'شتاب‌دهی و خودترمیمی زنده', 'kavoshrank' ); ?></span>
                                <span class="kr-nav-btn-sub"><?php esc_html_e( 'Site Healer و Core Web Vitals', 'kavoshrank' ); ?></span>
                            </div>
                        </div>
                        <span class="kr-nav-indicator"></span>
                    </button>
                </div>

                <div class="kr-sidebar-footer">
                    <div class="kr-sys-status-card">
                        <div class="kr-sys-status-row">
                            <span><?php esc_html_e( 'وضعیت اتصال:', 'kavoshrank' ); ?></span>
                            <span class="kr-sys-status-val" style="color:<?php echo ! empty( $api_url ) ? 'var(--kr-primary)' : 'var(--kr-amber)'; ?>">
                                <?php echo ! empty( $api_url ) ? esc_html__( 'پیکربندی شده', 'kavoshrank' ) : esc_html__( 'نیاز به پیکربندی', 'kavoshrank' ); ?>
                            </span>
                        </div>
                        <div class="kr-sys-status-row">
                            <span><?php esc_html_e( 'پروتکل امنیتی:', 'kavoshrank' ); ?></span>
                            <span class="kr-sys-status-val">AES-256 HMAC</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area with Section Cards -->
            <main class="kr-content-area">

                <!-- SECTION 1: PLATFORM API & AUTHENTICATION -->
                <section id="section-platform" class="kr-section-card">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'اتصال پلتفرم و احراز هویت (Platform Gateway)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'تنظیم آدرس سرور اصلی و کلید محرمانه رمزنگاری‌شده جهت تبادل امن و لحظه‌ای داده‌ها', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge <?php echo ! empty( $api_url ) ? 'kr-badge-emerald' : 'kr-badge-amber'; ?>">
                            <span class="kr-status-dot"></span>
                            <?php echo ! empty( $api_url ) ? esc_html__( 'فعال و متصل', 'kavoshrank' ) : esc_html__( 'تکمیل نشده', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- API URL Field -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_golang_api_url" class="kr-setting-label">
                                    <?php esc_html_e( 'آدرس سرور API پلتفرم', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'آدرس پایه سرویس هوش مصنوعی کاوش‌رنک جهت همگام‌سازی و تحلیل مداوم سئو.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_golang_api_url"
                                    type="url"
                                    id="kavoshrank_golang_api_url"
                                    value="<?php echo esc_attr( $api_url ); ?>"
                                    placeholder="https://backend.kavoshrank.ir"
                                    class="kr-input kr-input-ltr"
                                    required
                                />
                                <span class="kr-setting-desc"><?php esc_html_e( 'آدرس پیش‌فرض:', 'kavoshrank' ); ?> <code>https://backend.kavoshrank.ir</code> (<?php esc_html_e( 'امکان ویرایش در صورت تغییر دامنه یا سرور پلتفرم', 'kavoshrank' ); ?>)</span>
                            </div>
                        </div>

                        <!-- Secret Token Field -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_secret_token" class="kr-setting-label">
                                    <?php esc_html_e( 'کلید محرمانه اتصال (Secret Token)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'این کلید را از بخش ایجاد پروژه یا «تنظیمات اتصال به وردپرس» در پنل کاوش‌رنک کپی کرده و در این کادر جای‌گذاری نمایید.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <div class="kr-input-group">
                                    <input
                                        name="kavoshrank_secret_token"
                                        type="password"
                                        id="kavoshrank_secret_token"
                                        value="<?php echo esc_attr( $secret_token ); ?>"
                                        placeholder="<?php echo esc_attr__( 'کلید امنیتی ۶۴ کاراکتری تولیدشده در پنل کاوش‌رنک...', 'kavoshrank' ); ?>"
                                        class="kr-input kr-input-ltr kr-input-mono"
                                        required
                                    />
                                    <button type="button" id="kr-btn-toggle-token" class="kr-btn kr-btn-secondary kr-btn-icon-only" title="<?php echo esc_attr__( 'نمایش یا مخفی‌سازی کلید', 'kavoshrank' ); ?>">
                                        <span class="kr-icon-wrap">
                                            <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </span>
                                    </button>
                                    <button type="button" id="kr-btn-copy-token" class="kr-btn kr-btn-secondary" title="<?php echo esc_attr__( 'کپی در حافظه', 'kavoshrank' ); ?>">
                                        <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                        <?php esc_html_e( 'کپی', 'kavoshrank' ); ?>
                                    </button>
                                    <button type="button" id="kr-btn-paste-token" class="kr-btn kr-btn-secondary" title="<?php echo esc_attr__( 'جای‌گذاری از کلیپ‌بورد', 'kavoshrank' ); ?>">
                                        <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                        </svg>
                                        <?php esc_html_e( 'جای‌گذاری', 'kavoshrank' ); ?>
                                    </button>
                                </div>

                                <div style="margin-top:8px;">
                                    <button type="button" id="kr-btn-test-connection" class="kr-btn kr-btn-indigo">
                                        <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 14 14"></polyline>
                                        </svg>
                                        <?php esc_html_e( 'تست زنده اتصال به سرور (Ping Test)', 'kavoshrank' ); ?>
                                    </button>
                                </div>

                                <div id="kr-connection-test-result" style="display:none;margin-top:12px;"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2: EDITOR & WORKFLOW -->
                <section id="section-editor" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'ویرایشگر و گردش‌کار نگارش محتوا (Editor Preferences)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'انتخاب موتور نگارش مقالات جهت هماهنگی حداکثری با کلاینت‌های تولید هوشمند محتوا', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge <?php echo $disable_gutenberg ? 'kr-badge-emerald' : 'kr-badge-slate'; ?>">
                            <?php echo $disable_gutenberg ? esc_html__( 'ویرایشگر کلاسیک', 'kavoshrank' ) : esc_html__( 'گوتنبرگ بلوکی', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'موتور ویرایشگر نوشته‌ها', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'استفاده از ویرایشگر کلاسیک، پایداری استایل‌های وارد شده از اتوماسیون محتوا را تضمین می‌کند.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_disable_gutenberg">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_disable_gutenberg" value="0" />
                                        <input
                                            name="kavoshrank_disable_gutenberg"
                                            type="checkbox"
                                            id="kavoshrank_disable_gutenberg"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $disable_gutenberg ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'غیرفعال‌سازی گوتنبرگ و فعال‌سازی ویرایشگر کلاسیک', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'جلوگیری از تغییر ناخواسته بلوک‌های کدهای قالب سئو و جداول اطلاعاتی', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 3: E-E-A-T QUALITY SIGNALS -->
                <section id="section-eeat" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <polyline points="9 12 11 14 15 10"></polyline>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'شاخص‌های کیفیت و اصالت محتوا (E-E-A-T Signals)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'ارزیابی تخصص، تجربه و اعتبار محتوا مطابق به‌روزرسانی‌های هسته الگوریتم‌های گوگل', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge <?php echo $enable_eeat ? 'kr-badge-emerald' : 'kr-badge-slate'; ?>">
                            <?php echo $enable_eeat ? esc_html__( 'نشان فعال', 'kavoshrank' ) : esc_html__( 'غیرفعال', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- Toggle EEAT -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'نمایش نشان اصالت و اعتبارسنجی', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'تزریق باکس بازبینی کیفی و شناسه نویسنده/ویراستار در بالای مقالات وبلاگ.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_eeat_box">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_eeat_box" value="0" />
                                        <input
                                            name="kavoshrank_enable_eeat_box"
                                            type="checkbox"
                                            id="kavoshrank_enable_eeat_box"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $enable_eeat ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'فعال‌سازی نشان اعتماد E-E-A-T در سربرگ مقالات', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'سیگنال مستقیم به موتورهای جستجو مبنی بر کارشناسی بودن محتوا', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- EEAT Badge Title -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_eeat_badge_text" class="kr-setting-label">
                                    <?php esc_html_e( 'عنوان گواهی یا استاندارد کیفی', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'متن برچسب استاندارد در هدر مقاله.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_eeat_badge_text"
                                    type="text"
                                    id="kavoshrank_eeat_badge_text"
                                    value="<?php echo esc_attr( $eeat_badge ); ?>"
                                    class="kr-input"
                                />
                            </div>
                        </div>

                        <!-- EEAT Reviewer Title -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_eeat_reviewer_title" class="kr-setting-label">
                                    <?php esc_html_e( 'عنوان تیم یا کارشناس بازبین', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'عنوان هویت کارشناسی ارزیابی‌کننده مقاله برای استناد الگوریتم‌ها.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_eeat_reviewer_title"
                                    type="text"
                                    id="kavoshrank_eeat_reviewer_title"
                                    value="<?php echo esc_attr( $eeat_reviewer ); ?>"
                                    class="kr-input"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 4: CRO & ENGAGEMENT WIDGETS -->
                <section id="section-cro" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'ویجت‌های نرخ تبدیل و تماس مستقیم (CRO Widgets)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'هدایت کاربران و بازدیدکنندگان سئو به سمت تماس تلفنی و صفحات فروش خدمات', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge <?php echo $enable_cro ? 'kr-badge-emerald' : 'kr-badge-slate'; ?>">
                            <?php echo $enable_cro ? esc_html__( 'ویجت فعال', 'kavoshrank' ) : esc_html__( 'غیرفعال', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- Toggle CRO -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'باکس‌های دعوت به اقدام (CTA)', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'نمایش باکس شکیل مشاوره و دریافت پیشنهاد قیمت در انتهای مقالات مرتبط.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_cro_widgets">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_cro_widgets" value="0" />
                                        <input
                                            name="kavoshrank_enable_cro_widgets"
                                            type="checkbox"
                                            id="kavoshrank_enable_cro_widgets"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $enable_cro ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'فعال‌سازی بنرهای نرخ تبدیل در بدنه مقالات', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'افزایش نرخ تبدیل ترافیک ورودی جستجو به تماس مستقیم و لیدهای فروش', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Contact Phone -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_contact_phone" class="kr-setting-label">
                                    <?php esc_html_e( 'شماره تلفن مستقیم پشتیبانی / فروش', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'جهت درج در دکمه تماس با یک کلیک در ویجت مقالات.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_contact_phone"
                                    type="text"
                                    id="kavoshrank_contact_phone"
                                    value="<?php echo esc_attr( $contact_phone ); ?>"
                                    placeholder="02188888888"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Contact Page URL -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_contact_url" class="kr-setting-label">
                                    <?php esc_html_e( 'آدرس صفحه تماس با ما یا فرم ارتباط', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'مقصد دکمه‌های ثبت سفارش یا مشاوره آنلاین.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_contact_url"
                                    type="url"
                                    id="kavoshrank_contact_url"
                                    value="<?php echo esc_attr( $contact_url ); ?>"
                                    placeholder="<?php echo esc_attr( home_url( '/contact' ) ); ?>"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 5: SEO & SCHEMA & AUTO-LINKING -->
                <section id="section-seo" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                    <polyline points="2 17 12 22 22 17"></polyline>
                                    <polyline points="2 12 12 17 22 12"></polyline>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'سئو تکنیکال، اسکیما و پیوندهای داخلی (SEO & Schema)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'تنظیم ساختار داده‌های معنایی JSON-LD و مدیریت لینک‌سازی خودکار کلمات کلیدی', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge kr-badge-emerald">
                            <span class="kr-status-dot"></span>
                            <?php esc_html_e( 'فعال', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- Auto Linking -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'لینک‌سازی هوشمند داخلی', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'اتصال خودکار کلمات کلیدی موجود در متن به مقالات مرجع، دسته‌بندی‌ها و محصولات.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_auto_linking">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_auto_linking" value="0" />
                                        <input
                                            name="kavoshrank_enable_auto_linking"
                                            type="checkbox"
                                            id="kavoshrank_enable_auto_linking"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $enable_autolink ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'فعال‌سازی پیوندسازی خودکار در زمان رندر مقالات', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'افزایش PageRank داخلی و بهبود رتبه‌بندی کلمات کلیدی در سراسر سایت', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Schema Injection -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'داده‌های ساختاریافته (Schema.org)', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'تولید و تزریق خودکار اسکیماهای استاندارد Article و FAQPage در سربرگ مقالات.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_schema">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_schema" value="0" />
                                        <input
                                            name="kavoshrank_enable_schema"
                                            type="checkbox"
                                            id="kavoshrank_enable_schema"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $enable_schema ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'تزریق هوشمند داده‌های ساختاریافته JSON-LD', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'نمایش ریچ‌اسنیپت‌ها (سوالات متداول، تاریخ انتشار، تصاویر) در نتایج جستجوی گوگل', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- WooCommerce Schema Override -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'بهینه‌سازی اسکیمای محصولات ووکامرس', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'حذف اسکیمای محصول ووکامرس و جایگزینی آن با اسکیمای کاوش‌رنک. در حالت خاموش، اسکیمای ووکامرس دست‌نخورده باقی می‌ماند.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_override_wc_schema">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_override_wc_schema" value="0" />
                                        <input
                                            name="kavoshrank_override_wc_schema"
                                            type="checkbox"
                                            id="kavoshrank_override_wc_schema"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $override_schema ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'جایگزینی اسکیمای محصول ووکامرس (Product Schema)', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'تنها در صورتی فعال کنید که نمی‌خواهید اسکیمای ووکامرس در کنار اسکیمای کاوش‌رنک منتشر شود', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Product Return Window -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_return_policy_days" class="kr-setting-label">
                                    <?php esc_html_e( 'مهلت مرجوعی کالا (روز)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'تعداد روزهای مجاز برای مرجوعی طبق سیاست فروشگاه. در صورت خالی بودن، سیاست مرجوعی در اسکیمای محصول درج نمی‌شود.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_return_policy_days"
                                    type="text"
                                    inputmode="numeric"
                                    id="kavoshrank_return_policy_days"
                                    value="<?php echo esc_attr( $return_days ); ?>"
                                    placeholder="7"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Product Return Fees -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_return_policy_fees" class="kr-setting-label">
                                    <?php esc_html_e( 'هزینه ارسال کالای مرجوعی', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'تنها همراه با مهلت مرجوعی منتشر می‌شود.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <select name="kavoshrank_return_policy_fees" id="kavoshrank_return_policy_fees" class="kr-input">
                                    <option value="" <?php selected( $return_fees, '' ); ?>><?php esc_html_e( 'اعلام نشود', 'kavoshrank' ); ?></option>
                                    <option value="free" <?php selected( $return_fees, 'free' ); ?>><?php esc_html_e( 'رایگان (برعهده فروشگاه)', 'kavoshrank' ); ?></option>
                                    <option value="customer" <?php selected( $return_fees, 'customer' ); ?>><?php esc_html_e( 'برعهده مشتری', 'kavoshrank' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Flat Shipping Rate -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_shipping_rate" class="kr-setting-label">
                                    <?php esc_html_e( 'هزینه ارسال ثابت (به واحد پول فروشگاه)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'برای ارسال رایگان عدد 0 را وارد کنید. در صورت خالی بودن، اطلاعات ارسال در اسکیمای محصول درج نمی‌شود.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_shipping_rate"
                                    type="text"
                                    inputmode="decimal"
                                    id="kavoshrank_shipping_rate"
                                    value="<?php echo esc_attr( $shipping_rate ); ?>"
                                    placeholder="0"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Shipping Handling Time -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_shipping_handling_days" class="kr-setting-label">
                                    <?php esc_html_e( 'زمان آماده‌سازی سفارش (روز)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'تعداد روزهای آماده‌سازی سفارش پیش از تحویل به پست یا پیک.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_shipping_handling_days"
                                    type="text"
                                    inputmode="numeric"
                                    id="kavoshrank_shipping_handling_days"
                                    value="<?php echo esc_attr( $shipping_handling ); ?>"
                                    placeholder="1"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Shipping Transit Time -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_shipping_transit_days" class="kr-setting-label">
                                    <?php esc_html_e( 'زمان تحویل مرسوله (روز)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'تعداد روزهای حمل تا رسیدن به مشتری. زمان تحویل تنها در صورت تکمیل این فیلد در اسکیما درج می‌شود.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_shipping_transit_days"
                                    type="text"
                                    inputmode="numeric"
                                    id="kavoshrank_shipping_transit_days"
                                    value="<?php echo esc_attr( $shipping_transit ); ?>"
                                    placeholder="3"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Local SEO Toggle -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'اسکیمای سئوی محلی (LocalBusiness Schema)', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'تولید داده‌های ساختاریافته کسب‌وکار محلی بر اساس مشخصات رسمی ثبت‌شده فروشگاه برای گوگل.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_local_seo">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_local_seo" value="0" />
                                        <input
                                            name="kavoshrank_enable_local_seo"
                                            type="checkbox"
                                            id="kavoshrank_enable_local_seo"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $enable_local_seo ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'فعال‌سازی سئوی محلی و کارت دانش کسب‌وکار (Google Knowledge Panel)', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'تنها در صورت تکمیل نام کسب‌وکار، نشانی و شماره تماس درج خواهد شد.', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Local Business Name -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_name" class="kr-setting-label">
                                    <?php esc_html_e( 'نام رسمی کسب‌وکار یا برند', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'نام تجاری جهت درج در فیلد name اسکیما.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_name"
                                    type="text"
                                    id="kavoshrank_local_business_name"
                                    value="<?php echo esc_attr( $local_name ); ?>"
                                    placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                                    class="kr-input"
                                />
                            </div>
                        </div>

                        <!-- Local Business Type -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_type" class="kr-setting-label">
                                    <?php esc_html_e( 'نوع کسب‌وکار (Schema.org Subtype)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'مانند LocalBusiness، Store، ProfessionalService و غیره.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_type"
                                    type="text"
                                    id="kavoshrank_local_business_type"
                                    value="<?php echo esc_attr( $local_type ); ?>"
                                    placeholder="LocalBusiness"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Local Business Phone -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_phone" class="kr-setting-label">
                                    <?php esc_html_e( 'تلفن تماس کسب‌وکار', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'شماره تلفن رسمی کسب‌وکار جهت درج در اسکیما.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_phone"
                                    type="text"
                                    id="kavoshrank_local_business_phone"
                                    value="<?php echo esc_attr( $local_phone ); ?>"
                                    placeholder="02188888888"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Local Business Address -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_address" class="kr-setting-label">
                                    <?php esc_html_e( 'نشانی دقیق خیابان (Street Address)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'آدرس فیزیکی دفتر یا فروشگاه.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_address"
                                    type="text"
                                    id="kavoshrank_local_business_address"
                                    value="<?php echo esc_attr( $local_address ); ?>"
                                    placeholder="<?php echo esc_attr__( 'تهران، خیابان ولیعصر...', 'kavoshrank' ); ?>"
                                    class="kr-input"
                                />
                            </div>
                        </div>

                        <!-- Locality & Region -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label class="kr-setting-label">
                                    <?php esc_html_e( 'شهر و استان', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'تفکیک شهر و استان برای استاندارد PostalAddress.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col" style="display:flex; gap:12px;">
                                <input
                                    name="kavoshrank_local_business_locality"
                                    type="text"
                                    id="kavoshrank_local_business_locality"
                                    value="<?php echo esc_attr( $local_locality ); ?>"
                                    placeholder="<?php echo esc_attr__( 'شهر (مثلا تهران)', 'kavoshrank' ); ?>"
                                    class="kr-input"
                                />
                                <input
                                    name="kavoshrank_local_business_region"
                                    type="text"
                                    id="kavoshrank_local_business_region"
                                    value="<?php echo esc_attr( $local_region ); ?>"
                                    placeholder="<?php echo esc_attr__( 'استان (مثلا تهران)', 'kavoshrank' ); ?>"
                                    class="kr-input"
                                />
                            </div>
                        </div>

                        <!-- Postal Code & Country -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label class="kr-setting-label">
                                    <?php esc_html_e( 'کد پستی و کشور', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'کد پستی ۱۰ رقمی و کد کشور (مثلا IR).', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col" style="display:flex; gap:12px;">
                                <input
                                    name="kavoshrank_local_business_postal_code"
                                    type="text"
                                    id="kavoshrank_local_business_postal_code"
                                    value="<?php echo esc_attr( $local_postal_code ); ?>"
                                    placeholder="<?php echo esc_attr__( 'کد پستی ۱۰ رقمی', 'kavoshrank' ); ?>"
                                    class="kr-input kr-input-ltr"
                                />
                                <input
                                    name="kavoshrank_local_business_country"
                                    type="text"
                                    id="kavoshrank_local_business_country"
                                    value="<?php echo esc_attr( $local_country ); ?>"
                                    placeholder="IR"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Coordinates (Geo) -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label class="kr-setting-label">
                                    <?php esc_html_e( 'مختصات جغرافیایی (Geo Coordinates)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'عرض و طول جغرافیایی برای نمایش در نقشه گوگل.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col" style="display:flex; gap:12px;">
                                <input
                                    name="kavoshrank_local_business_geo_lat"
                                    type="text"
                                    id="kavoshrank_local_business_geo_lat"
                                    value="<?php echo esc_attr( $local_lat ); ?>"
                                    placeholder="<?php echo esc_attr__( 'Latitude (مثلا 35.7219)', 'kavoshrank' ); ?>"
                                    class="kr-input kr-input-ltr"
                                />
                                <input
                                    name="kavoshrank_local_business_geo_lng"
                                    type="text"
                                    id="kavoshrank_local_business_geo_lng"
                                    value="<?php echo esc_attr( $local_lng ); ?>"
                                    placeholder="<?php echo esc_attr__( 'Longitude (مثلا 51.3347)', 'kavoshrank' ); ?>"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <!-- Opening Hours & Logo & Price Range -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_opening_hours" class="kr-setting-label">
                                    <?php esc_html_e( 'ساعات کاری (Opening Hours)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'فرمت استاندارد (مثلا Mo-Fr 08:30-18:30, Sa 08:30-14:00).', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_opening_hours"
                                    type="text"
                                    id="kavoshrank_local_business_opening_hours"
                                    value="<?php echo esc_attr( $local_hours ); ?>"
                                    placeholder="Mo-Fr 08:30-18:30, Sa 08:30-14:00"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_logo" class="kr-setting-label">
                                    <?php esc_html_e( 'آدرس آیکون یا لوگوی اختصاصی (Logo URL)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'در صورت خالی بودن، لوگوی پیش‌فرض قالب یا آیکون سایت درج خواهد شد.', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_logo"
                                    type="url"
                                    id="kavoshrank_local_business_logo"
                                    value="<?php echo esc_attr( $local_logo ); ?>"
                                    placeholder="<?php echo esc_attr( home_url( '/logo.png' ) ); ?>"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>

                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <label for="kavoshrank_local_business_price_range" class="kr-setting-label">
                                    <?php esc_html_e( 'محدوده قیمت (Price Range)', 'kavoshrank' ); ?>
                                </label>
                                <p class="kr-setting-desc"><?php esc_html_e( 'شاخص محدوده قیمتی خدمات یا محصولات (مثلا $ یا $$ یا $$$).', 'kavoshrank' ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <input
                                    name="kavoshrank_local_business_price_range"
                                    type="text"
                                    id="kavoshrank_local_business_price_range"
                                    value="<?php echo esc_attr( $local_price_range ); ?>"
                                    placeholder="$$"
                                    class="kr-input kr-input-ltr"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 6: AI BOTS GOVERNOR & FIREWALL -->
                <section id="section-bots" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                    <circle cx="12" cy="5" r="2"></circle>
                                    <path d="M12 7v4"></path>
                                    <line x1="8" y1="16" x2="8.01" y2="16"></line>
                                    <line x1="16" y1="16" x2="16.01" y2="16"></line>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'فایروال خزنده‌ها و ربات‌های هوش مصنوعی (AI Bots Governor)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'پیکربندی هوشمند فایروال robots.txt و محافظت از منابع پردازنده و پهنای‌باند سرور', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge <?php echo $bot_firewall ? 'kr-badge-emerald' : 'kr-badge-slate'; ?>">
                            <?php echo $bot_firewall ? esc_html__( 'فایروال فعال', 'kavoshrank' ) : esc_html__( 'غیرفعال', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- Toggle Bot Firewall -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'فایروال مسدودسازی زنده ترافیک مخرب', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'قطع ارتباط فوری و خودکار اسکرپرهای سنگین قبل از اجرای کدهای سنگین PHP و دیتابیس.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_bot_firewall_enabled">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_bot_firewall_enabled" value="0" />
                                        <input
                                            name="kavoshrank_bot_firewall_enabled"
                                            type="checkbox"
                                            id="kavoshrank_bot_firewall_enabled"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $bot_firewall ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'فعال‌سازی فایروال سروری ربات‌ها (Live Bot Firewall)', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'کاهش بار پردازشی سرور و مهار خزنده‌های بدون ورودی ترافیک', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Bot Governor Presets -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'حالت سیاست‌گذاری ربات‌ها (Preset)', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'انتخاب شیوه برخورد با موتورهای جستجوی هوش مصنوعی (Perplexity, ChatGPT, Gemini, Copilot).', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <div class="kr-preset-grid">
                                    <!-- Balanced -->
                                    <label class="kr-preset-card <?php echo $bot_preset === 'balanced' ? 'selected' : ''; ?>">
                                        <input
                                            type="radio"
                                            name="kavoshrank_bot_governor_preset"
                                            value="balanced"
                                            <?php checked( 'balanced', $bot_preset ); ?>
                                        />
                                        <div class="kr-preset-header">
                                            <span class="kr-preset-title">
                                                <?php esc_html_e( 'متعادل (پیشنهادی)', 'kavoshrank' ); ?>
                                            </span>
                                            <span class="kr-badge kr-badge-emerald"><?php esc_html_e( 'پیش‌فرض', 'kavoshrank' ); ?></span>
                                        </div>
                                        <p class="kr-preset-desc">
                                            <?php esc_html_e( 'اجازه به خزنده‌های استناد هوش مصنوعی جهت افزایش ترافیک، و مسدودسازی اسکرپرهای سنگین و بی‌استفاده.', 'kavoshrank' ); ?>
                                        </p>
                                    </label>

                                    <!-- Permissive -->
                                    <label class="kr-preset-card <?php echo $bot_preset === 'permissive' ? 'selected' : ''; ?>">
                                        <input
                                            type="radio"
                                            name="kavoshrank_bot_governor_preset"
                                            value="permissive"
                                            <?php checked( 'permissive', $bot_preset ); ?>
                                        />
                                        <div class="kr-preset-header">
                                            <span class="kr-preset-title">
                                                <?php esc_html_e( 'باز و تعاملی', 'kavoshrank' ); ?>
                                            </span>
                                            <span class="kr-badge kr-badge-indigo"><?php esc_html_e( 'حداکثر ایندکس', 'kavoshrank' ); ?></span>
                                        </div>
                                        <p class="kr-preset-desc">
                                            <?php esc_html_e( 'دسترسی تمام ربات‌های هوش مصنوعی و خزنده‌ها به تمام محتواها؛ مناسب سایت‌های اطلاع‌رسانی عمومی.', 'kavoshrank' ); ?>
                                        </p>
                                    </label>

                                    <!-- Strict -->
                                    <label class="kr-preset-card <?php echo $bot_preset === 'strict' ? 'selected' : ''; ?>">
                                        <input
                                            type="radio"
                                            name="kavoshrank_bot_governor_preset"
                                            value="strict"
                                            <?php checked( 'strict', $bot_preset ); ?>
                                        />
                                        <div class="kr-preset-header">
                                            <span class="kr-preset-title">
                                                <?php esc_html_e( 'محافظت کامل', 'kavoshrank' ); ?>
                                            </span>
                                            <span class="kr-badge kr-badge-amber"><?php esc_html_e( 'سخت‌گیرانه', 'kavoshrank' ); ?></span>
                                        </div>
                                        <p class="kr-preset-desc">
                                            <?php esc_html_e( 'مسدودسازی تمام ربات‌های آموزش LLM و خزنده‌های متفرقه هوش مصنوعی جهت حفظ کامل محرمانگی.', 'kavoshrank' ); ?>
                                        </p>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Live Robots.txt Preview -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'پیش‌نمایش زنده virtual robots.txt', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'قوانین اعمال‌شده لحظه‌ای که توسط وردپرس به خزنده‌ها ارسال می‌شوند.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <div class="kr-code-preview">
                                    <div class="kr-code-preview-header">
                                        <span>robots.txt (Live Generated)</span>
                                        <button type="button" id="kr-btn-copy-robots" class="kr-btn kr-btn-secondary" style="padding:4px 10px;font-size:11px;">
                                            <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                            <?php esc_html_e( 'کپی قوانین', 'kavoshrank' ); ?>
                                        </button>
                                    </div>
                                    <pre id="kr-robots-preview-content" style="margin:0;font-family:inherit;font-size:inherit;color:inherit;white-space:pre-wrap;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 7: RUNTIME HEALER & SITE SPEED -->
                <section id="section-performance" class="kr-section-card" style="display:none;">
                    <div class="kr-card-header">
                        <div class="kr-card-header-left">
                            <div class="kr-card-icon">
                                <svg class="kr-icon" viewBox="0 0 24 24">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                            </div>
                            <div class="kr-card-title-group">
                                <h2><?php esc_html_e( 'شتاب‌دهی و خودترمیمی زنده سایت (Site Healer)', 'kavoshrank' ); ?></h2>
                                <p><?php esc_html_e( 'بهینه‌سازی کدهای HTML خروجی، بهبود شاخص‌های سرعت Core Web Vitals و تصحیح تگ‌های ضروری', 'kavoshrank' ); ?></p>
                            </div>
                        </div>
                        <span class="kr-badge kr-badge-emerald">
                            <span class="kr-status-dot"></span>
                            <?php esc_html_e( 'بهینه‌ساز فعال', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-card-body">
                        <!-- Preload Vazirmatn -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'پیش‌بارگذاری فونت و ارتباطات سریع (Preload)', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'تزریق خودکار تگ‌های Preconnect و Preload فونت وزیرمتن در سربرگ صفحات برای رفع تاخیر نمایش متن (FOIT).', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_preload_vazirmatn">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_preload_vazirmatn" value="0" />
                                        <input
                                            name="kavoshrank_preload_vazirmatn"
                                            type="checkbox"
                                            id="kavoshrank_preload_vazirmatn"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $preload_font ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'پیش‌بارگذاری فونت فارسی استاندارد و اتصالات CDN', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'کاهش زمان بارگذاری Largest Contentful Paint (LCP) و جهش چیدمان (CLS)', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- WebP Rewrite -->
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php esc_html_e( 'بازنویسی هوشمند فرمت تصاویر به WebP', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'جایگزینی خودکار تصاویر موجود در متن مقالات با نسخه سبک WebP در صورت وجود فایل در هاست.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_enable_webp_rewrite">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_enable_webp_rewrite" value="0" />
                                        <input
                                            name="kavoshrank_enable_webp_rewrite"
                                            type="checkbox"
                                            id="kavoshrank_enable_webp_rewrite"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, $webp_rewrite ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'شتاب‌بخشی لود تصاویر رسانه‌ها', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'کاهش چشمگیر حجم دانلود صفحه در دستگاه‌های موبایل', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Runtime Healer: master switch and individually opt-in fixes (all default off) -->
                        <?php
                        $healer_toggles = array(
                            'kavoshrank_runtime_healer_enabled' => array(
                                __( 'خودترمیمی زنده خروجی HTML (Runtime Healer)', 'kavoshrank' ),
                                __( 'کلید اصلی بافر خروجی صفحه؛ تا زمانی که خاموش است هیچ‌یک از اصلاحات زیر اجرا نمی‌شوند.', 'kavoshrank' ),
                            ),
                            'kavoshrank_healer_font_preload'    => array(
                                __( 'Preconnect و Preload فونت', 'kavoshrank' ),
                                __( 'افزودن Preconnect فقط برای CDNهایی که صفحه از آن‌ها استفاده می‌کند و Preload فونت وزیرمتن در صورت استفاده.', 'kavoshrank' ),
                            ),
                            'kavoshrank_healer_lcp_preload'     => array(
                                __( 'اولویت تصویر LCP و Lazy Load', 'kavoshrank' ),
                                __( 'افزودن fetchpriority به نخستین تصویر محتوایی (به‌جز لوگو و تصاویر سربرگ) و Lazy Load برای تصاویر بعدی.', 'kavoshrank' ),
                            ),
                            'kavoshrank_healer_smart_alt'       => array(
                                __( 'ترمیم متن جایگزین تصاویر', 'kavoshrank' ),
                                __( 'افزودن متن جایگزین (alt) به تصاویری که فاقد آن هستند، به‌جز نشان‌های اعتماد.', 'kavoshrank' ),
                            ),
                            'kavoshrank_healer_aria_labels'     => array(
                                __( 'برچسب دسترسی دکمه‌های آیکونی', 'kavoshrank' ),
                                __( 'افزودن aria-label به همان تگ دکمه‌های آیکونی بدون برچسب، بدون تکرار یا تودرتو کردن دکمه.', 'kavoshrank' ),
                            ),
                        );
                        foreach ( $healer_toggles as $healer_option => $healer_text ) :
                            ?>
                        <div class="kr-setting-row">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label"><?php echo esc_html( $healer_text[0] ); ?></span>
                                <p class="kr-setting-desc"><?php echo esc_html( $healer_text[1] ); ?></p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="<?php echo esc_attr( $healer_option ); ?>">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="<?php echo esc_attr( $healer_option ); ?>" value="0" />
                                        <input
                                            name="<?php echo esc_attr( $healer_option ); ?>"
                                            type="checkbox"
                                            id="<?php echo esc_attr( $healer_option ); ?>"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, get_option( $healer_option, '0' ) === '1' ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Data Erasure on Uninstall -->
                        <div class="kr-setting-row" style="border-top:1px solid #fee2e2;padding-top:18px;">
                            <div class="kr-setting-label-col">
                                <span class="kr-setting-label" style="color:#b91c1c;"><?php esc_html_e( 'پاکسازی داده‌ها هنگام حذف افزونه', 'kavoshrank' ); ?></span>
                                <p class="kr-setting-desc">
                                    <?php esc_html_e( 'با فعال‌سازی این گزینه، با حذف نهایی (Uninstall) افزونه از وردپرس، تمامی تنظیمات، جداول پایگاه‌داده و متادیتاهای کاوش‌رنک به طور برگشت‌ناپذیر پاکسازی می‌شوند.', 'kavoshrank' ); ?>
                                </p>
                            </div>
                            <div class="kr-setting-field-col">
                                <label class="kr-switch-label" for="kavoshrank_erase_data_on_uninstall">
                                    <span class="kr-switch-box">
                                        <input type="hidden" name="kavoshrank_erase_data_on_uninstall" value="0" />
                                        <input
                                            name="kavoshrank_erase_data_on_uninstall"
                                            type="checkbox"
                                            id="kavoshrank_erase_data_on_uninstall"
                                            value="1"
                                            class="kr-switch-input"
                                            <?php checked( true, (bool) get_option( 'kavoshrank_erase_data_on_uninstall', 0 ) ); ?>
                                        />
                                        <span class="kr-switch-slider"></span>
                                    </span>
                                    <span class="kr-switch-text">
                                        <span class="kr-switch-title"><?php esc_html_e( 'حذف کامل داده‌ها و تنظیمات هنگام Uninstall', 'kavoshrank' ); ?></span>
                                        <span class="kr-switch-hint"><?php esc_html_e( 'پاکسازی جدول‌ها، آپشن‌های kavoshrank_ و متادیتاها در زمان حذف نهایی افزونه', 'kavoshrank' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STICKY SAVE ACTIONS BAR -->
                <div class="kr-save-bar">
                    <div class="kr-save-bar-info">
                        <svg class="kr-icon kr-icon-sm" style="color:var(--kr-primary);" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span><?php esc_html_e( 'تنظیمات بلافاصله پس از ذخیره روی موتور فعال وردپرس اعمال خواهند شد.', 'kavoshrank' ); ?></span>
                        <span id="kr-unsaved-badge" class="kr-badge kr-badge-amber" style="display:none;">
                            <?php esc_html_e( 'تغییرات ذخیره‌نشده دارید', 'kavoshrank' ); ?>
                        </span>
                    </div>

                    <div class="kr-save-bar-actions">
                        <button type="submit" class="kr-btn kr-btn-primary">
                            <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <?php esc_html_e( 'ذخیره تغییرات پیکربندی', 'kavoshrank' ); ?>
                        </button>
                    </div>
                </div>

            </main>
        </div>
    </form>
</div>
