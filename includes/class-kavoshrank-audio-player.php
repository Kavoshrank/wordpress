<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Audio_Player {

    public function render_single_podcast_player( $content ) {
        if ( ! is_single() ) {
            return $content;
        }

        global $post;
        if ( ! $post ) {
            return $content;
        }

        $audio_url = get_post_meta( $post->ID, '_kavoshrank_audio_summary_url', true );
        if ( empty( $audio_url ) ) {
            $audio_url = get_post_meta( $post->ID, '_tsw_audio_summary_url', true );
        }

        if ( empty( $audio_url ) ) {
            return $content;
        }

        $player_html = '
        <div class="kavoshrank-glassmorphic-player" style="margin:25px 0;padding:18px 22px;background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);border-radius:14px;border:1px solid rgba(226,232,240,0.9);box-shadow:0 8px 32px rgba(0,0,0,0.05);direction:rtl;text-align:right;font-family:inherit;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <svg style="width:20px;height:20px;stroke:#00C582;fill:none;stroke-width:2;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10v2a7 7 0 01-14 0v-2M12 19v4m-4 0h8"/></svg>
                <span style="font-weight:bold;font-size:16px;color:#1e293b;">پادکست صوتی مقاله (کاوش‌رنک)</span>
            </div>
            <audio controls style="width:100%;outline:none;border-radius:8px;">
                <source src="' . esc_url( $audio_url ) . '" type="audio/wav">
                مرورگر شما از پخش فایل صوتی پشتیبانی نمی‌کند.
            </audio>
        </div>';

        return $player_html . $content;
    }
}
