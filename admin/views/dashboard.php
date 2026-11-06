<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_count        = wp_count_posts( 'post' )->publish ?? 0;
$product_count     = class_exists( 'WooCommerce' ) ? ( wp_count_posts( 'product' )->publish ?? 0 ) : 0;
$classic_editor_on = (bool) get_option( 'kavoshrank_disable_gutenberg', false );
$configured_url    = (string) get_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' );
if ( empty( $configured_url ) ) {
    $configured_url = 'https://backend.kavoshrank.ir';
}
$updater_inst    = new KavoshRank_Updater();
$kr_remote_info  = $updater_inst->get_remote_update_info();
$kr_has_update   = $kr_remote_info && ! empty( $kr_remote_info['version'] ) && version_compare( KAVOSHRANK_VERSION, $kr_remote_info['version'], '<' );
?>

<div class="wrap kavoshrank-admin-wrap <?php echo kavoshrank_is_rtl() ? 'kr-rtl' : 'kr-ltr'; ?>" dir="<?php echo kavoshrank_is_rtl() ? 'rtl' : 'ltr'; ?>">

    <!-- Top Header Banner -->
    <header class="kr-header-banner">
        <div class="kr-header-content">
            <div class="kr-header-icon-box">
                <svg class="kr-icon kr-icon-lg" viewBox="0 0 24 24">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="kr-header-title-group">
                <h1>
                    <?php esc_html_e( 'کلاینت هوشمند سئو و اتوماسیون محتوا کاوش‌رنک (KavoshRank)', 'kavoshrank' ); ?>
                    <span class="kr-badge kr-badge-emerald">
                        <span class="kr-status-dot kr-pulse-dot"></span>
                        <?php esc_html_e( 'نسخه', 'kavoshrank' ); ?> <?php echo esc_html( KAVOSHRANK_VERSION ); ?>
                    </span>
                    <?php if ( $kr_has_update ) : ?>
                    <span class="kr-badge kr-badge-amber" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                        <?php printf( esc_html__( 'نسخه جدید %s در دسترس است', 'kavoshrank' ), esc_html( $kr_remote_info['version'] ) ); ?>
                    </span>
                    <?php endif; ?>
                </h1>
                <p><?php esc_html_e( 'پل ارتباطی، موتور خودترمیمی زنده سایت (Site Healer) و دروازه اختصاصی اتصال به پلتفرم مرکزی کاوش‌رنک', 'kavoshrank' ); ?></p>
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
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings' ) ); ?>" class="kr-btn kr-btn-primary">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
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
                <span><?php esc_html_e( 'تنظیمات اتصال', 'kavoshrank' ); ?></span>
            </a>
        </div>
    </header>

    <?php if ( $kr_has_update ) :
        $package_url = ! empty( $kr_remote_info['package'] ) ? $kr_remote_info['package'] : ( ! empty( $kr_remote_info['download_url'] ) ? $kr_remote_info['download_url'] : 'https://kavoshrank.ir/download/kavoshrank-connector.zip' );
        $update_url  = wp_nonce_url(
            self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( KavoshRank_Updater::PLUGIN_BASENAME ) ),
            'upgrade-plugin_' . KavoshRank_Updater::PLUGIN_BASENAME
        );
    ?>
    <div style="margin-bottom: 24px; padding: 18px 24px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.08);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fef3c7; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: center; color: #b45309; flex-shrink: 0;">
                <svg class="kr-icon" viewBox="0 0 24 24" style="width: 22px; height: 22px;">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 800; color: #92400e;">
                    <?php esc_html_e( 'بروزرسانی جدید افزونه کاوش‌رنک منتشر شد', 'kavoshrank' ); ?>
                </h4>
                <p style="margin: 0; font-size: 12px; color: #78350f;">
                    <?php printf( esc_html__( 'نسخه جدید %1$s آماده دریافت است (نسخه فعلی شما: %2$s). لطفاً جهت ارتقای هوش مصنوعی و هماهنگی کامل با پنل، افزونه را بروزرسانی فرمایید.', 'kavoshrank' ), '<b>' . esc_html( $kr_remote_info['version'] ) . '</b>', '<code>' . esc_html( KAVOSHRANK_VERSION ) . '</code>' ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="<?php echo esc_url( $update_url ); ?>" class="kr-btn kr-btn-primary" style="background: #059669;">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span><?php esc_html_e( 'بروزرسانی خودکار در وردپرس', 'kavoshrank' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $package_url ); ?>" class="kr-btn kr-btn-secondary" download="kavoshrank-connector.zip">
                <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span><?php esc_html_e( 'دانلود فایل ZIP', 'kavoshrank' ); ?></span>
            </a>
        </div>
    </div>
    <?php endif; ?>


    <!-- Key Metrics & Status Cards Grid -->
    <div class="kavoshrank-stats-grid">
        <!-- 1. Posts Metric Card -->
        <div class="kavoshrank-stat-card kr-stat-card-sky">
            <div class="kr-stat-header">
                <h3 class="kr-stat-title">
                    <?php esc_html_e( 'نوشته‌های وب‌سایت', 'kavoshrank' ); ?>
                </h3>
                <div class="kr-stat-icon-box kr-stat-icon-sky">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="kavoshrank-stat-num"><?php echo esc_html( number_format_i18n( $post_count ) ); ?></div>
            <p class="kr-stat-desc"><?php esc_html_e( 'پست‌های فعال وردپرس', 'kavoshrank' ); ?></p>
            <div class="kr-stat-footer">
                <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'مدیریت نوشته‌ها', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- 2. WooCommerce Metric Card -->
        <div class="kavoshrank-stat-card kr-stat-card-purple">
            <div class="kr-stat-header">
                <h3 class="kr-stat-title">
                    <?php esc_html_e( 'محصولات ووکامرس', 'kavoshrank' ); ?>
                </h3>
                <div class="kr-stat-icon-box kr-stat-icon-purple">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
            </div>
            <div class="kavoshrank-stat-num"><?php echo esc_html( number_format_i18n( $product_count ) ); ?></div>
            <p class="kr-stat-desc"><?php esc_html_e( 'محصولات فروشگاه', 'kavoshrank' ); ?></p>
            <div class="kr-stat-footer">
                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="kr-stat-link">
                        <span><?php esc_html_e( 'مدیریت محصولات', 'kavoshrank' ); ?></span>
                        <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                <?php else : ?>
                    <span class="kr-stat-footer-subtle"><?php esc_html_e( 'ووکامرس نصب/فعال نیست', 'kavoshrank' ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Post Editor Engine Card -->
        <div class="kavoshrank-stat-card kr-stat-card-amber">
            <div class="kr-stat-header">
                <h3 class="kr-stat-title">
                    <?php esc_html_e( 'ویرایشگر نوشته‌ها', 'kavoshrank' ); ?>
                </h3>
                <div class="kr-stat-icon-box kr-stat-icon-amber">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
            </div>
            <div class="kr-stat-status-badge">
                <?php if ( $classic_editor_on ) : ?>
                    <span class="kr-badge kr-badge-emerald">
                        <span class="kr-status-dot"></span>
                        <?php esc_html_e( 'ویرایشگر کلاسیک (فعال)', 'kavoshrank' ); ?>
                    </span>
                    <p class="kr-stat-desc"><?php esc_html_e( 'گوتنبرگ غیرفعال شده است', 'kavoshrank' ); ?></p>
                <?php else : ?>
                    <span class="kr-badge kr-badge-slate">
                        <span class="kr-status-dot"></span>
                        <?php esc_html_e( 'گوتنبرگ بلوکی (فعال)', 'kavoshrank' ); ?>
                    </span>
                    <p class="kr-stat-desc"><?php esc_html_e( 'ویرایشگر بلوکی پیش‌فرض وردپرس', 'kavoshrank' ); ?></p>
                <?php endif; ?>
            </div>
            <div class="kr-stat-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings#section-editor' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'تغییر به ویرایشگر کلاسیک', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- 4. Central Platform Connection Card -->
        <div class="kavoshrank-stat-card kr-stat-card-emerald">
            <div class="kr-stat-header">
                <h3 class="kr-stat-title">
                    <?php esc_html_e( 'پلتفرم مرکزی کاوش‌رنک', 'kavoshrank' ); ?>
                </h3>
                <div class="kr-stat-icon-box kr-stat-icon-emerald">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </div>
            </div>
            <div class="kr-stat-status-badge">
                <?php if ( ! empty( $configured_url ) ) : ?>
                    <span class="kr-badge kr-badge-emerald">
                        <span class="kr-status-dot kr-pulse-dot"></span>
                        <?php esc_html_e( 'متصل و آماده به کار (Active)', 'kavoshrank' ); ?>
                    </span>
                    <p class="kr-stat-desc kr-url-mono" title="<?php echo esc_attr( $configured_url ); ?>"><?php echo esc_html( $configured_url ); ?></p>
                <?php else : ?>
                    <span class="kr-badge kr-badge-amber">
                        <span class="kr-status-dot"></span>
                        <?php esc_html_e( 'نیاز به پیکربندی', 'kavoshrank' ); ?>
                    </span>
                    <p class="kr-stat-desc" style="color:var(--kr-rose);font-weight:600;"><?php esc_html_e( 'آدرس API تنظیم نشده است', 'kavoshrank' ); ?></p>
                <?php endif; ?>
            </div>
            <div class="kr-stat-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings#section-platform' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'تنظیمات اتصال به پلتفرم', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions Deck -->
    <div class="kr-card kr-dashboard-actions-card">
        <div class="kr-card-header">
            <div class="kr-card-header-right">
                <div class="kr-card-icon-box" style="background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <div class="kr-card-title-group">
                    <h2><?php esc_html_e( 'اقدامات و دسترسی سریع', 'kavoshrank' ); ?></h2>
                    <p><?php esc_html_e( 'میانبرهای پرکاربرد جهت مدیریت محتوا، بررسی ارتباط و تنظیمات پلتفرم', 'kavoshrank' ); ?></p>
                </div>
            </div>
        </div>
        <div class="kr-card-body" style="padding-top:18px;">
            <div class="kr-actions-row">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings' ) ); ?>" class="kr-btn kr-btn-primary">
                    <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
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
                    <span><?php esc_html_e( 'تنظیمات اتصال به پلتفرم', 'kavoshrank' ); ?></span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="kr-btn kr-btn-secondary">
                    <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span><?php esc_html_e( 'لیست نوشته‌ها', 'kavoshrank' ); ?></span>
                </a>

                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="kr-btn kr-btn-secondary">
                    <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span><?php esc_html_e( 'لیست محصولات', 'kavoshrank' ); ?></span>
                </a>
                <?php endif; ?>

                <button type="button" id="kr-btn-dashboard-ping" class="kr-btn kr-btn-secondary">
                    <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <span><?php esc_html_e( 'تست زنده اتصال به سرور (Ping Test)', 'kavoshrank' ); ?></span>
                </button>

                <a href="https://panel.kavoshrank.ir" target="_blank" rel="noopener noreferrer" class="kr-btn kr-btn-secondary">
                    <svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span><?php esc_html_e( 'ورود به پنل مرکزی کاوش‌رنک', 'kavoshrank' ); ?></span>
                </a>
            </div>

            <div id="kr-dashboard-ping-result" style="display:none;margin-top:16px;"></div>
        </div>
    </div>

    <!-- Platform Engines & Status Grid -->
    <div class="kr-engines-grid">
        <!-- Engine 1: Site Healer -->
        <div class="kr-card kr-engine-card">
            <div class="kr-engine-header">
                <div class="kr-stat-icon-box kr-stat-icon-emerald">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="kr-engine-title-group">
                    <div class="kr-engine-title-row">
                        <h3 class="kr-engine-title"><?php esc_html_e( 'شتاب‌دهی و خودترمیمی زنده', 'kavoshrank' ); ?></h3>
                        <span class="kr-badge kr-badge-emerald"><?php esc_html_e( 'Site Healer', 'kavoshrank' ); ?></span>
                    </div>
                    <span class="kr-engine-sub"><?php esc_html_e( 'Site Healer و Core Web Vitals', 'kavoshrank' ); ?></span>
                </div>
            </div>
            <ul class="kr-engine-feature-list">
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'کش هوشمند بافرهای خروجی و کاهش بار سرور', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'پیش‌بارگذاری فونت‌ها و المان‌های حیاتی LCP', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'جایگزینی و تبدیل خودکار به تصاویر WebP', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'تزریق هوشمند ویژگی‌های Alt و برچسب‌های دسترس‌پذیری', 'kavoshrank' ); ?></span>
                </li>
            </ul>
            <div class="kr-engine-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings#section-sitehealer' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'پیکربندی Site Healer', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>

        <!-- Engine 2: SEO & Schema -->
        <div class="kr-card kr-engine-card">
            <div class="kr-engine-header">
                <div class="kr-stat-icon-box kr-stat-icon-sky">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div class="kr-engine-title-group">
                    <div class="kr-engine-title-row">
                        <h3 class="kr-engine-title"><?php esc_html_e( 'اتوماسیون محتوا و سئو معنایی', 'kavoshrank' ); ?></h3>
                        <span class="kr-badge kr-badge-indigo"><?php esc_html_e( 'SEO Engine', 'kavoshrank' ); ?></span>
                    </div>
                    <span class="kr-engine-sub"><?php esc_html_e( 'Schema.org و استانداردهای رتبه‌بندی', 'kavoshrank' ); ?></span>
                </div>
            </div>
            <ul class="kr-engine-feature-list">
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'تولید ساختار معنایی Schema مقالات و محصولات', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'مهار هوشمند خزنده‌های هوش مصنوعی (AI Bots Governor)', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'باکس اعتبارسنجی تخصص و تجربه تحریریه (E-E-A-T)', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'لینک‌سازی هوشمند خودکار در کلمات کلیدی هدف', 'kavoshrank' ); ?></span>
                </li>
            </ul>
            <div class="kr-engine-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings#section-schema' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'پیکربندی اسکیما و سئو', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>

        <!-- Engine 3: Gateway & Platform Security -->
        <div class="kr-card kr-engine-card">
            <div class="kr-engine-header">
                <div class="kr-stat-icon-box kr-stat-icon-purple">
                    <svg class="kr-icon" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="kr-engine-title-group">
                    <div class="kr-engine-title-row">
                        <h3 class="kr-engine-title"><?php esc_html_e( 'امنیت و ارتباط پلتفرم مرکزی', 'kavoshrank' ); ?></h3>
                        <span class="kr-badge kr-badge-amber"><?php esc_html_e( 'Secure Gateway', 'kavoshrank' ); ?></span>
                    </div>
                    <span class="kr-engine-sub"><?php esc_html_e( 'ارتباط رمزنگاری‌شده و پایش سلامت', 'kavoshrank' ); ?></span>
                </div>
            </div>
            <ul class="kr-engine-feature-list">
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'کلید امنیتی اتصال اختصاصی (Secret Token Guard)', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'اتصال پرسرعت به سرور بک‌اند مرکزی Go', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'فونت‌های کاملاً لوکال با صفر وابستگی به CDN خارجی', 'kavoshrank' ); ?></span>
                </li>
                <li class="kr-engine-feature-item">
                    <svg class="kr-icon kr-icon-sm kr-check-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php esc_html_e( 'پایش لحظه‌ای تاخیر و پینگ سرور (Real-time Telemetry)', 'kavoshrank' ); ?></span>
                </li>
            </ul>
            <div class="kr-engine-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kavoshrank-settings#section-platform' ) ); ?>" class="kr-stat-link">
                    <span><?php esc_html_e( 'مدیریت کلید امنیتی', 'kavoshrank' ); ?></span>
                    <svg class="kr-icon kr-icon-sm kr-arrow-icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>
    </div>
</div>
