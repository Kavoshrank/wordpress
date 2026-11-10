<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Audio Player
 *
 * Renders modern, responsive, styled audio players for article podcasts.
 * Supports Glassmorphic Dark, Minimalist Light, and Waveform themes.
 */
class KavoshRank_Audio_Player {

    public function init() {
        add_filter( 'the_content', array( $this, 'render_single_podcast_player' ) );
        add_shortcode( 'kavoshrank_podcast', array( $this, 'render_podcast_shortcode' ) );
    }

    /**
     * Auto-injects the podcast player at the beginning of single posts if enabled.
     */
    public function render_single_podcast_player( $content ) {
        if ( ! is_single() ) {
            return $content;
        }

        global $post;
        if ( ! $post ) {
            return $content;
        }

        // Check if player auto-injection is enabled
        $auto_insert = get_post_meta( $post->ID, '_kavoshrank_audio_auto_insert', true );
        if ( '' === $auto_insert ) {
            $auto_insert = get_option( 'kavoshrank_audio_auto_insert', '1' );
        }
        if ( '0' === $auto_insert || false === $auto_insert ) {
            return $content;
        }

        $audio_url = get_post_meta( $post->ID, '_kavoshrank_audio_summary_url', true );
        if ( empty( $audio_url ) ) {
            $audio_url = get_post_meta( $post->ID, '_tsw_audio_summary_url', true );
        }

        if ( empty( $audio_url ) ) {
            return $content;
        }

        $player_html = $this->generate_player_html( $post->ID, $audio_url );

        return $player_html . $content;
    }

    /**
     * Shortcode [kavoshrank_podcast] handler.
     */
    public function render_podcast_shortcode( $atts ) {
        global $post;
        $post_id = ! empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : ( $post ? $post->ID : 0 );
        if ( ! $post_id ) {
            return '';
        }

        $audio_url = ! empty( $atts['url'] ) ? esc_url( $atts['url'] ) : '';
        if ( empty( $audio_url ) ) {
            $audio_url = get_post_meta( $post_id, '_kavoshrank_audio_summary_url', true );
            if ( empty( $audio_url ) ) {
                $audio_url = get_post_meta( $post_id, '_tsw_audio_summary_url', true );
            }
        }

        if ( empty( $audio_url ) ) {
            return '';
        }

        $override_theme = ! empty( $atts['theme'] ) ? sanitize_text_field( $atts['theme'] ) : '';

        return $this->generate_player_html( $post_id, $audio_url, $override_theme );
    }

    /**
     * Builds the standalone HTML, CSS, and JS for the customized audio player.
     */
    public function generate_player_html( $post_id, $audio_url, $override_theme = '' ) {
        // 1. Resolve Theme
        $theme = $override_theme;
        if ( empty( $theme ) ) {
            $theme = get_post_meta( $post_id, '_kavoshrank_audio_theme', true );
        }
        if ( empty( $theme ) ) {
            $theme = get_option( 'kavoshrank_audio_player_theme', 'glassmorphic' );
        }
        if ( ! in_array( $theme, array( 'glassmorphic', 'minimal', 'waveform' ), true ) ) {
            $theme = 'glassmorphic';
        }

        // 2. Resolve Speaker Voice
        $voice = get_post_meta( $post_id, '_kavoshrank_audio_voice', true );
        if ( empty( $voice ) ) {
            $voice = get_option( 'kavoshrank_audio_speaker_voice', 'Puck' );
        }
        $voice_labels = array(
            'Puck'   => 'پاک (Puck)',
            'Charon' => 'شارون (Charon)',
            'Kore'   => 'کوره (Kore)',
            'Fenrir' => 'فنریر (Fenrir)',
            'Aoede'  => 'ائوده (Aoede)',
        );
        $voice_display = isset( $voice_labels[ $voice ] ) ? $voice_labels[ $voice ] : $voice;

        // 3. Resolve Playback Speed
        $speed = get_post_meta( $post_id, '_kavoshrank_audio_speed', true );
        if ( empty( $speed ) ) {
            $speed = get_option( 'kavoshrank_audio_speech_rate', '1.0' );
        }
        $speed_float = floatval( $speed );
        if ( $speed_float <= 0 ) {
            $speed_float = 1.0;
        }

        // 4. Resolve Duration String
        $duration_str = get_post_meta( $post_id, '_kavoshrank_audio_duration_str', true );
        if ( empty( $duration_str ) ) {
            $duration_sec = (float) get_post_meta( $post_id, '_kavoshrank_audio_duration', true );
            if ( $duration_sec > 0 ) {
                $mins = floor( $duration_sec / 60 );
                $secs = floor( $duration_sec % 60 );
                $duration_str = sprintf( '%02d:%02d', $mins, $secs );
            } else {
                $duration_str = '01:30';
            }
        }

        $uid = 'kr-player-' . $post_id . '-' . wp_rand( 100, 999 );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $uid ); ?>" class="kavoshrank-podcast-player kr-theme-<?php echo esc_attr( $theme ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" data-speed="<?php echo esc_attr( $speed_float ); ?>" dir="rtl">
            <?php if ( 'glassmorphic' === $theme ) : ?>
                <!-- Glassmorphic Dark Theme -->
                <div class="kr-card kr-glass-card">
                    <div class="kr-row-header">
                        <div class="kr-col-info">
                            <button type="button" class="kr-play-btn kr-glass-btn" aria-label="پخش پادکست">
                                <span class="kr-icon-play">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                                </span>
                                <span class="kr-icon-pause" style="display:none;">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                </span>
                            </button>
                            <div class="kr-text-box">
                                <span class="kr-title">پادکست و خلاصه صوتی این مطلب</span>
                                <span class="kr-subtitle">سرعت پخش: <span class="kr-speed-val"><?php echo esc_html( $speed_float ); ?>x</span></span>
                            </div>
                        </div>
                        <div class="kr-col-controls">
                            <button type="button" class="kr-speed-btn" title="تغییر سرعت پخش">
                                <span class="kr-speed-btn-text"><?php echo esc_html( $speed_float ); ?>x</span>
                            </button>
                            <span class="kr-time-box">
                                <span class="kr-time-curr">00:00</span> / <span class="kr-time-dur"><?php echo esc_html( $duration_str ); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="kr-progress-wrap">
                        <div class="kr-progress-track">
                            <div class="kr-progress-fill"></div>
                        </div>
                    </div>
                </div>

            <?php elseif ( 'minimal' === $theme ) : ?>
                <!-- Minimalist Light Theme -->
                <div class="kr-card kr-minimal-card">
                    <div class="kr-row-header">
                        <div class="kr-col-info">
                            <button type="button" class="kr-play-btn kr-minimal-btn" aria-label="پخش پادکست">
                                <span class="kr-icon-play">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                                </span>
                                <span class="kr-icon-pause" style="display:none;">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                </span>
                            </button>
                            <div class="kr-text-box">
                                <span class="kr-title">شنیدن نسخه صوتی مقاله</span>
                                <span class="kr-subtitle">سرعت پخش: <span class="kr-speed-val"><?php echo esc_html( $speed_float ); ?>x</span></span>
                            </div>
                        </div>
                        <div class="kr-col-controls">
                            <button type="button" class="kr-speed-btn" title="تغییر سرعت پخش">
                                <span class="kr-speed-btn-text"><?php echo esc_html( $speed_float ); ?>x</span>
                            </button>
                            <span class="kr-time-box">
                                <span class="kr-time-curr">00:00</span> / <span class="kr-time-dur"><?php echo esc_html( $duration_str ); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="kr-progress-wrap">
                        <div class="kr-progress-track">
                            <div class="kr-progress-fill"></div>
                        </div>
                    </div>
                </div>

            <?php else : ?>
                <!-- Waveform Futuristic Theme -->
                <div class="kr-card kr-waveform-card">
                    <div class="kr-row-header">
                        <div class="kr-col-info">
                            <button type="button" class="kr-play-btn kr-waveform-btn" aria-label="پخش پادکست">
                                <span class="kr-icon-play">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                                </span>
                                <span class="kr-icon-pause" style="display:none;">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                </span>
                            </button>
                            <div class="kr-text-box">
                                <span class="kr-title">پلیر امواج صوتی اختصاصی</span>
                                <span class="kr-subtitle">سرعت پخش: <span class="kr-speed-val"><?php echo esc_html( $speed_float ); ?>x</span></span>
                            </div>
                        </div>
                        <div class="kr-col-controls">
                            <!-- Animated Waveform Visualizer -->
                            <div class="kr-waveform-bars" aria-hidden="true">
                                <span class="kr-bar" style="height:35%;"></span>
                                <span class="kr-bar" style="height:70%;"></span>
                                <span class="kr-bar" style="height:45%;"></span>
                                <span class="kr-bar" style="height:90%;"></span>
                                <span class="kr-bar" style="height:60%;"></span>
                                <span class="kr-bar" style="height:100%;"></span>
                                <span class="kr-bar" style="height:50%;"></span>
                                <span class="kr-bar" style="height:80%;"></span>
                                <span class="kr-bar" style="height:40%;"></span>
                                <span class="kr-bar" style="height:65%;"></span>
                                <span class="kr-bar" style="height:85%;"></span>
                                <span class="kr-bar" style="height:30%;"></span>
                            </div>
                            <button type="button" class="kr-speed-btn" title="تغییر سرعت پخش">
                                <span class="kr-speed-btn-text"><?php echo esc_html( $speed_float ); ?>x</span>
                            </button>
                            <span class="kr-time-box">
                                <span class="kr-time-curr">00:00</span> / <span class="kr-time-dur"><?php echo esc_html( $duration_str ); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="kr-progress-wrap">
                        <div class="kr-progress-track">
                            <div class="kr-progress-fill"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hidden HTML5 Audio Element -->
            <audio class="kr-native-audio" preload="metadata" style="display:none !important;" src="<?php echo esc_url( $audio_url ); ?>"></audio>

            <!-- Self-Contained Inline Styles -->
            <style>
                #<?php echo esc_attr( $uid ); ?> {
                    margin: 28px 0;
                    clear: both;
                    width: 100%;
                    box-sizing: border-box;
                    font-family: inherit;
                }
                #<?php echo esc_attr( $uid ); ?> * {
                    box-sizing: border-box;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-card {
                    padding: 16px 20px;
                    border-radius: 16px;
                    transition: box-shadow 0.2s ease, border-color 0.2s ease;
                }
                /* 1. Glassmorphic Dark */
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card {
                    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #0f172a 100%);
                    border: 1px solid rgba(148, 163, 184, 0.22);
                    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.35), 0 2px 8px rgba(0, 0, 0, 0.2);
                    color: #ffffff;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-btn {
                    background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
                    box-shadow: 0 4px 14px rgba(147, 51, 234, 0.45);
                    border-radius: 12px;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-btn:hover {
                    background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
                    transform: scale(1.04);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-title {
                    color: #f8fafc;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-subtitle {
                    color: #c084fc;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-time-box {
                    color: #94a3b8;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-speed-btn {
                    background: rgba(255, 255, 255, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.16);
                    color: #e2e8f0;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-speed-btn:hover {
                    background: rgba(255, 255, 255, 0.16);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-progress-track {
                    background: rgba(51, 65, 85, 0.85);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-glass-card .kr-progress-fill {
                    background: linear-gradient(90deg, #a855f7, #6366f1);
                }

                /* 2. Minimalist Light */
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card {
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
                    color: #0f172a;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-btn {
                    background: #059669;
                    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
                    border-radius: 9999px;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-btn:hover {
                    background: #047857;
                    transform: scale(1.04);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-title {
                    color: #0f172a;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-subtitle {
                    color: #64748b;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-time-box {
                    color: #64748b;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-speed-btn {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    color: #475569;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-speed-btn:hover {
                    background: #f1f5f9;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-progress-track {
                    background: #f1f5f9;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-minimal-card .kr-progress-fill {
                    background: #10b981;
                }

                /* 3. Waveform Futuristic */
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card {
                    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
                    border: 1px solid #e9d5ff;
                    box-shadow: 0 6px 20px rgba(147, 51, 234, 0.08);
                    color: #0f172a;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-btn {
                    background: linear-gradient(135deg, #9333ea 0%, #6366f1 100%);
                    box-shadow: 0 4px 14px rgba(147, 51, 234, 0.35);
                    border-radius: 12px;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-btn:hover {
                    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
                    transform: scale(1.04);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-title {
                    color: #0f172a;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-subtitle {
                    color: #7e22ce;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-time-box {
                    color: #6b21a8;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-speed-btn {
                    background: rgba(147, 51, 234, 0.08);
                    border: 1px solid rgba(147, 51, 234, 0.2);
                    color: #7e22ce;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-speed-btn:hover {
                    background: rgba(147, 51, 234, 0.15);
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-progress-track {
                    background: #e9d5ff;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-card .kr-progress-fill {
                    background: linear-gradient(90deg, #9333ea, #ec4899);
                }

                /* Waveform Equalizer Bars */
                #<?php echo esc_attr( $uid ); ?> .kr-waveform-bars {
                    display: flex;
                    align-items: center;
                    gap: 2.5px;
                    height: 22px;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-bar {
                    width: 3px;
                    border-radius: 3px;
                    background: linear-gradient(to top, #9333ea, #ec4899);
                    transition: height 0.15s ease, opacity 0.15s ease;
                    opacity: 0.45;
                }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar {
                    opacity: 1;
                    animation: kr-pulse-wave 0.8s ease-in-out infinite alternate;
                }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(2) { animation-delay: 0.1s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(3) { animation-delay: 0.2s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(4) { animation-delay: 0.15s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(5) { animation-delay: 0.25s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(6) { animation-delay: 0.3s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(7) { animation-delay: 0.05s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(8) { animation-delay: 0.18s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(9) { animation-delay: 0.28s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(10) { animation-delay: 0.12s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(11) { animation-delay: 0.22s; }
                #<?php echo esc_attr( $uid ); ?>.kr-playing .kr-bar:nth-child(12) { animation-delay: 0.32s; }

                @keyframes kr-pulse-wave {
                    0% { transform: scaleY(0.4); }
                    100% { transform: scaleY(1.15); }
                }

                /* Layout structure */
                #<?php echo esc_attr( $uid ); ?> .kr-row-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    flex-wrap: wrap;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-col-info {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    min-width: 0;
                    flex: 1 1 240px;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-play-btn {
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 42px;
                    height: 42px;
                    flex-shrink: 0;
                    padding: 0;
                    color: #ffffff;
                    transition: transform 0.15s ease, background 0.15s ease;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-text-box {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                    min-width: 0;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-title {
                    font-size: 13.5px;
                    font-weight: 700;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    line-height: 1.4;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-subtitle {
                    font-size: 11px;
                    font-weight: 500;
                    line-height: 1.3;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-col-controls {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-shrink: 0;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-speed-btn {
                    padding: 3px 8px;
                    font-size: 11px;
                    font-weight: 700;
                    border-radius: 8px;
                    cursor: pointer;
                    font-family: inherit;
                    transition: all 0.15s ease;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-time-box {
                    font-size: 11px;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    direction: ltr;
                    unicode-bidi: isolate;
                    white-space: nowrap;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-progress-wrap {
                    margin-top: 12px;
                    width: 100%;
                    cursor: pointer;
                    padding: 4px 0;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-progress-track {
                    width: 100%;
                    height: 6px;
                    border-radius: 9999px;
                    position: relative;
                    overflow: hidden;
                }
                #<?php echo esc_attr( $uid ); ?> .kr-progress-fill {
                    width: 0%;
                    height: 100%;
                    border-radius: 9999px;
                    transition: width 0.1s linear;
                }

                @media (max-width: 520px) {
                    #<?php echo esc_attr( $uid ); ?> .kr-row-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 12px;
                    }
                    #<?php echo esc_attr( $uid ); ?> .kr-col-controls {
                        width: 100%;
                        justify-content: space-between;
                    }
                }
            </style>

            <!-- Self-Contained Controller Script -->
            <script>
                (function() {
                    function initPlayer() {
                        var root = document.getElementById('<?php echo esc_js( $uid ); ?>');
                        if (!root || root.__kr_initialized) return;
                        root.__kr_initialized = true;

                        var audio = root.querySelector('.kr-native-audio');
                        var playBtn = root.querySelector('.kr-play-btn');
                        var iconPlay = root.querySelector('.kr-icon-play');
                        var iconPause = root.querySelector('.kr-icon-pause');
                        var progressWrap = root.querySelector('.kr-progress-wrap');
                        var progressFill = root.querySelector('.kr-progress-fill');
                        var timeCurr = root.querySelector('.kr-time-curr');
                        var timeDur = root.querySelector('.kr-time-dur');
                        var speedBtn = root.querySelector('.kr-speed-btn');
                        var speedBtnText = root.querySelector('.kr-speed-btn-text');
                        var speedVal = root.querySelector('.kr-speed-val');

                        if (!audio || !playBtn) return;

                        var initialSpeed = parseFloat(root.getAttribute('data-speed')) || 1.0;
                        audio.playbackRate = initialSpeed;

                        function formatTime(sec) {
                            if (!sec || isNaN(sec) || sec < 0) return '00:00';
                            var m = Math.floor(sec / 60);
                            var s = Math.floor(sec % 60);
                            return (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
                        }

                        function setPlayingUI(playing) {
                            if (playing) {
                                root.classList.add('kr-playing');
                                if (iconPlay) iconPlay.style.display = 'none';
                                if (iconPause) iconPause.style.display = 'inline-flex';
                            } else {
                                root.classList.remove('kr-playing');
                                if (iconPlay) iconPlay.style.display = 'inline-flex';
                                if (iconPause) iconPause.style.display = 'none';
                            }
                        }

                        // Toggle Play / Pause
                        playBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (audio.paused || audio.ended) {
                                // Pause other players on the same page
                                document.querySelectorAll('.kr-native-audio').forEach(function(other) {
                                    if (other !== audio && !other.paused) {
                                        other.pause();
                                    }
                                });
                                audio.play().then(function() {
                                    setPlayingUI(true);
                                }).catch(function() {
                                    setPlayingUI(false);
                                });
                            } else {
                                audio.pause();
                                setPlayingUI(false);
                            }
                        });

                        audio.addEventListener('play', function() { setPlayingUI(true); });
                        audio.addEventListener('pause', function() { setPlayingUI(false); });
                        audio.addEventListener('ended', function() {
                            setPlayingUI(false);
                            if (progressFill) progressFill.style.width = '0%';
                            if (timeCurr) timeCurr.textContent = '00:00';
                        });

                        audio.addEventListener('timeupdate', function() {
                            if (!audio.duration || isNaN(audio.duration)) return;
                            var pct = (audio.currentTime / audio.duration) * 100;
                            if (progressFill) progressFill.style.width = Math.min(100, Math.max(0, pct)) + '%';
                            if (timeCurr) timeCurr.textContent = formatTime(audio.currentTime);
                        });

                        audio.addEventListener('loadedmetadata', function() {
                            if (audio.duration && !isNaN(audio.duration)) {
                                if (timeDur) timeDur.textContent = formatTime(audio.duration);
                            }
                        });

                        // Seek on progress track click
                        if (progressWrap) {
                            progressWrap.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                if (!audio.duration || isNaN(audio.duration)) return;
                                var rect = progressWrap.getBoundingClientRect();
                                var clickX = e.clientX - rect.left;
                                var pct = clickX / rect.width;
                                if (pct < 0) pct = 0;
                                if (pct > 1) pct = 1;
                                audio.currentTime = pct * audio.duration;
                            });
                        }

                        // Cycle speed button
                        if (speedBtn) {
                            var speeds = [1.0, 1.25, 1.5, 0.8];
                            speedBtn.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                var current = audio.playbackRate || 1.0;
                                var nextIdx = 0;
                                for (var i = 0; i < speeds.length; i++) {
                                    if (Math.abs(speeds[i] - current) < 0.05) {
                                        nextIdx = (i + 1) % speeds.length;
                                        break;
                                    }
                                }
                                var nextSpeed = speeds[nextIdx];
                                audio.playbackRate = nextSpeed;
                                if (speedBtnText) speedBtnText.textContent = nextSpeed + 'x';
                                if (speedVal) speedVal.textContent = nextSpeed + 'x';
                            });
                        }
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initPlayer);
                    } else {
                        initPlayer();
                    }
                })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}
