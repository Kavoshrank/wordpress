<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_CRO {

    public function init() {
        if ( ! is_admin() && get_option( 'kavoshrank_enable_cro_widgets', '0' ) === '1' ) {
            add_filter( 'the_content', array( $this, 'inject_contextual_cro_widget' ), 30 );
        }
    }

    /**
     * Injects contextual high-conversion widget after 3rd paragraph
     */
    public function inject_contextual_cro_widget( $content ) {
        if ( ! is_singular( 'post' ) || in_the_loop() !== true || ! is_main_query() ) {
            return $content;
        }

        $phone = get_option( 'kavoshrank_contact_phone', '' );
        $contact_url = get_option( 'kavoshrank_contact_url', home_url( '/contact/' ) );
        $widget_data = $this->resolve_contextual_data();

        $call_button_html = '';
        if ( ! empty( $phone ) ) {
            $call_button_html = '
            <a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; background: #059669; color: #ffffff; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 12px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);">
                <svg style="width: 15px; height: 15px; fill: currentColor;" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 4V3z"></path></svg>
                <span>تماس با کارشناسان (' . esc_html( $phone ) . ')</span>
            </a>';
        }

        $cro_html = '
        <div class="kavoshrank-cro-widget" style="margin: 32px 0; padding: 22px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; color: #ffffff; font-family: inherit; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 3px 10px; border-radius: 9999px; font-weight: 700; border: 1px solid rgba(52, 211, 153, 0.3);">
                        ' . esc_html( $widget_data['badge'] ) . '
                    </span>
                    <span style="font-size: 11px; color: #94a3b8;">مشاوره و راهنمای تخصصی</span>
                </div>
                <h4 style="margin: 0; font-size: 16px; font-weight: 800; color: #f8fafc; line-height: 1.5;">
                    ' . esc_html( $widget_data['headline'] ) . '
                </h4>
                <p style="margin: 0; font-size: 12px; color: #cbd5e1; line-height: 1.7;">
                    ' . esc_html( $widget_data['description'] ) . '
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px;">
                    ' . $call_button_html . '
                    <a href="' . esc_url( $contact_url ) . '" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #f1f5f9; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 12px;">
                        <span>مشاهده خدمات و ارتباط با ما</span>
                    </a>
                </div>
            </div>
        </div>';

        // Split after 3rd paragraph
        $paragraphs = explode( '</p>', $content );
        if ( count( $paragraphs ) > 3 ) {
            $paragraphs[2] .= '</p>' . $cro_html;
            return implode( '</p>', $paragraphs );
        }

        return $content . $cro_html;
    }

    private function resolve_contextual_data() {
        $categories = get_the_category();
        $cat_name   = ! empty( $categories ) ? $categories[0]->name : '';

        if ( stripos( $cat_name, 'آموزش' ) !== false || stripos( $cat_name, 'راهنما' ) !== false ) {
            return array(
                'badge'       => 'مشاوره و راهنمای تخصصی',
                'headline'    => 'نیاز به راهنمایی در پیاده‌سازی یا انتخاب بهترین خدمات دارید؟',
                'description' => 'کارشناسان ما آماده پاسخگویی و ارائه مشاوره‌های راهبردی و کاربردی متناسب با نیاز شما هستند.',
            );
        }

        if ( stripos( $cat_name, 'محصول' ) !== false || stripos( $cat_name, 'خرید' ) !== false ) {
            return array(
                'badge'       => 'تضمین اصالت و کیفیت',
                'headline'    => 'خرید مستقیم با بالاترین استاندارد و ضمانت اصالت',
                'description' => 'تامین مطمئن، ارسال سریع به سراسر کشور و پشتیبانی ۲۴ ساعته برای تمامی سفارش‌ها.',
            );
        }

        return array(
            'badge'       => 'خدمات و پشتیبانی تخصصی',
            'headline'    => 'بهترین راهکارها و محصولات را با ما تجربه کنید',
            'description' => 'کیفیت برتر، ضمانت معتبر و پشتیبانی فنی حرفه‌ای در تمامی مراحل خدمت‌رسانی.',
        );
    }
}
