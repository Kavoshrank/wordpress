<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_EEAT {

    public function init() {
        if ( ! is_admin() && get_option( 'kavoshrank_enable_eeat_box', '0' ) === '1' ) {
            add_filter( 'the_content', array( $this, 'inject_eeat_verification_box' ), 20 );
        }
    }

    /**
     * Inject E-E-A-T trust and fact-checking badge into single blog posts
     */
    public function inject_eeat_verification_box( $content ) {
        if ( ! is_singular( 'post' ) || in_the_loop() !== true || ! is_main_query() ) {
            return $content;
        }

        $modified_date  = get_the_modified_date( 'j F Y' );
        $site_name      = get_bloginfo( 'name' ) ?: 'کاوش‌رنک';
        $author_name    = get_the_author() ?: ( 'تیم تحریریه ' . $site_name );
        $badge_standard = get_option( 'kavoshrank_eeat_badge_text', 'بازبینی و ارزیابی محتوا' );
        $reviewer_title = get_option( 'kavoshrank_eeat_reviewer_title', 'تیم تحریریه و کارشناسان' );

        $box_html = '
        <div class="kavoshrank-eeat-card" style="margin: 28px 0; padding: 18px 22px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, rgba(6, 78, 59, 0.03) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 14px; font-family: inherit; font-size: 13px; color: #1e293b; line-height: 1.8; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid rgba(16, 185, 129, 0.15); padding-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #047857;">
                    <svg style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>اطلاعات نگارش و بازبینی محتوا</span>
                </div>
                <span style="font-size: 11px; background: rgba(16, 185, 129, 0.12); color: #065f46; padding: 2px 8px; border-radius: 6px; font-weight: 600;">' . esc_html( $badge_standard ) . '</span>
            </div>
            <p style="margin: 0 0 8px 0; color: #334155;">
                این مقاله توسط <strong>' . esc_html( $author_name ) . '</strong> تدوین و نگارش یافته و بر اساس اصول کیفیت و راهنماهای سئو، مورد بازبینی و ارزیابی قرار گرفته است.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 14px; font-size: 11px; color: #64748b; margin-top: 6px;">
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <svg style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8z"/></svg>
                    بازبین محتوا: <strong>' . esc_html( $reviewer_title ) . '</strong>
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <svg style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    آخرین بروزرسانی: <strong>' . esc_html( $modified_date ) . '</strong>
                </span>
            </div>
        </div>';

        // Prepend box before main article body
        return $box_html . $content;
    }
}
