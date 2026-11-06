/**
 * KavoshRank Admin JS - Minimalist Section-by-Section Settings Controller
 * Zero-Emoji Directive: Strict Clean SVG Line Icons
 */

(function($) {
    'use strict';

    // Toast Notification Utility
    window.kavoshToast = function(message, type, duration) {
        type = type || 'success';
        duration = duration || 3200;

        let $toast = $('#kr-toast');
        if (!$toast.length) {
            $toast = $('<div id="kr-toast" class="kr-toast"><span class="kr-toast-icon"></span><span class="kr-toast-text"></span></div>');
            $('body').append($toast);
        }

        const successSvg = '<svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        const errorSvg = '<svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

        $toast.removeClass('kr-toast-success kr-toast-error')
              .addClass(type === 'error' ? 'kr-toast-error' : 'kr-toast-success');
        $toast.find('.kr-toast-icon').html(type === 'error' ? errorSvg : successSvg);
        $toast.find('.kr-toast-text').text(message);

        $toast.addClass('show');

        clearTimeout(window._krToastTimer);
        window._krToastTimer = setTimeout(function() {
            $toast.removeClass('show');
        }, duration);
    };

    $(document).ready(function() {
        const $settingsForm = $('#kavoshrank-settings-form');
        const $navButtons = $('.kr-nav-btn');
        const $sections = $('.kr-section-card');
        const $unsavedBadge = $('#kr-unsaved-badge');

        let isFormDirty = false;

        // -------------------------------------------------------------
        // 1. Section-by-Section Tab Navigation
        // -------------------------------------------------------------
        function activateSection(targetId, updateHash) {
            if (!targetId) return;

            // Remove # if present
            targetId = targetId.replace(/^#/, '');

            const $targetSection = $('#' + targetId);
            const $targetBtn = $('.kr-nav-btn[data-target="' + targetId + '"]');

            if ($targetSection.length) {
                // If not in "show all" mode
                if (!$('#kr-toggle-show-all').hasClass('active')) {
                    $sections.hide();
                    $targetSection.fadeIn(200);
                } else {
                    // Smooth scroll to section
                    $('html, body').animate({
                        scrollTop: $targetSection.offset().top - 60
                    }, 300);
                }

                $navButtons.removeClass('active');
                $targetBtn.addClass('active');

                if (updateHash !== false) {
                    if (history.replaceState) {
                        history.replaceState(null, null, '#' + targetId);
                    } else {
                        window.location.hash = targetId;
                    }
                }
            }
        }

        $navButtons.on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            activateSection(targetId, true);
        });

        // Toggle "Show All Sections" vs "Focused Tab View"
        $('#kr-toggle-show-all').on('click', function(e) {
            e.preventDefault();
            const $this = $(this);
            $this.toggleClass('active');

            if ($this.hasClass('active')) {
                $sections.show();
                $this.find('.kr-view-text').text('حالت تک‌بخش');
            } else {
                $this.find('.kr-view-text').text('نمایش یکپارچه');
                const currentActive = $('.kr-nav-btn.active').data('target') || 'section-platform';
                activateSection(currentActive, false);
            }
        });

        // Initialize based on URL hash or default to first tab
        const initialHash = window.location.hash;
        if (initialHash && $(initialHash).length) {
            activateSection(initialHash, false);
        } else {
            activateSection('section-platform', false);
        }

        // -------------------------------------------------------------
        // 2. Secret Token Controls (Show/Hide, Copy, Regenerate)
        // -------------------------------------------------------------
        const $tokenInput = $('#kavoshrank_secret_token');

        // Show / Hide Password Toggle
        $('#kr-btn-toggle-token').on('click', function(e) {
            e.preventDefault();
            const isPassword = $tokenInput.attr('type') === 'password';
            $tokenInput.attr('type', isPassword ? 'text' : 'password');

            const eyeOpen = '<svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            const eyeSlash = '<svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

            $(this).find('.kr-icon-wrap').html(isPassword ? eyeSlash : eyeOpen);
        });

        // Copy Token to Clipboard
        $('#kr-btn-copy-token').on('click', function(e) {
            e.preventDefault();
            const tokenVal = $tokenInput.val();
            if (!tokenVal) return;

            navigator.clipboard.writeText(tokenVal).then(function() {
                kavoshToast('کلید محرمانه اتصال در کلیپ‌بورد کپی شد.', 'success');
            }).catch(function() {
                $tokenInput.select();
                document.execCommand('copy');
                kavoshToast('کلید محرمانه کپی شد.', 'success');
            });
        });

        // Paste Token from Clipboard (One-click paste from KavoshRank Panel)
        $('#kr-btn-paste-token').on('click', function(e) {
            e.preventDefault();
            if (navigator.clipboard && navigator.clipboard.readText) {
                navigator.clipboard.readText().then(function(text) {
                    const clean = (text || '').trim();
                    if (clean) {
                        $tokenInput.val(clean).trigger('change');
                        $tokenInput.attr('type', 'text');
                        kavoshToast(kavoshRankAdmin.isRtl ? 'کلید محرمانه با موفقیت جای‌گذاری شد. لطفاً تغییرات را ذخیره نمایید.' : 'Secret token pasted from clipboard. Please save changes.', 'success');
                    } else {
                        kavoshToast(kavoshRankAdmin.isRtl ? 'کلیپ‌بورد خالی است.' : 'Clipboard is empty.', 'error');
                    }
                }).catch(function() {
                    $tokenInput.focus().select();
                });
            } else {
                $tokenInput.focus().select();
            }
        });

        // -------------------------------------------------------------
        // 3. Live API Ping & Connection Testing
        // -------------------------------------------------------------
        $('#kr-btn-test-connection').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $resultBox = $('#kr-connection-test-result');
            let apiUrl = $('#kavoshrank_golang_api_url').val();
            if (!apiUrl) {
                apiUrl = 'https://backend.kavoshrank.ir';
                $('#kavoshrank_golang_api_url').val(apiUrl);
            }
            const token = $tokenInput.val();

            const originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html(
                '<svg class="kr-icon kr-icon-sm kr-spinner" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> در حال بررسی ارتباط...'
            );

            $resultBox.hide().removeClass('kr-alert-success kr-alert-error');

            $.ajax({
                url: kavoshRankAdmin.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kavoshrank_test_connection',
                    nonce: kavoshRankAdmin.nonce,
                    api_url: apiUrl,
                    token: token
                },
                success: function(resp) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    if (resp && resp.success) {
                        const latency = resp.data && resp.data.latency_ms ? resp.data.latency_ms + 'ms' : '';
                        const msg = (resp.data && resp.data.message) || 'اتصال زنده با موفقیت برقرار شد.';
                        $resultBox.addClass('kr-alert kr-alert-success').html(
                            '<div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg><span>' +
                            msg + (latency ? ' <strong>(پینگ: ' + latency + ')</strong>' : '') +
                            '</span></div>'
                        ).fadeIn();
                        kavoshToast('اتصال با موفقیت تأیید شد.', 'success');
                    } else {
                        const errMsg = (resp && resp.data && resp.data.message) || 'عدم موفقیت در برقراری اتصال.';
                        $resultBox.addClass('kr-alert kr-alert-error').html(
                            '<div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>' +
                            errMsg + '</span></div>'
                        ).fadeIn();
                        kavoshToast(errMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    const errMsg = 'خطا در ارسال درخواست تست: ' + (error || status);
                    $resultBox.addClass('kr-alert kr-alert-error').html(
                        '<div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>' +
                        errMsg + '</span></div>'
                    ).fadeIn();
                    kavoshToast(errMsg, 'error');
                }
            });
        });

        // -------------------------------------------------------------
        // 4. AI Bots Governor Mode Selection & Live Robots.txt Preview
        // -------------------------------------------------------------
        const homeBase = (kavoshRankAdmin.homeUrl || window.location.origin + '/').replace(/\/$/, '');

        function generateRobotsPreview(preset) {
            let txt = '# ========================================================\n' +
                      '# KavoshRank AI Bots Governor & SEO Traffic Firewall\n' +
                      '# Mode: ' + preset.toUpperCase() + '\n' +
                      '# ========================================================\n\n' +
                      'User-agent: *\n' +
                      'Disallow: /wp-admin/\n' +
                      'Allow: /wp-admin/admin-ajax.php\n' +
                      'Allow: /llms.txt\n' +
                      'Allow: /llms-full.txt\n\n';

            if (preset === 'permissive') {
                txt += '# Allowed AI Search & Discovery Engines\n' +
                       'User-agent: PerplexityBot\nAllow: /\n\n' +
                       'User-agent: ChatGPT-User\nAllow: /\n\n' +
                       'User-agent: OAI-SearchBot\nAllow: /\n\n' +
                       'User-agent: Google-Extended\nAllow: /\n\n' +
                       'User-agent: Applebot-Extended\nAllow: /\n\n' +
                       '# Block Aggressive Scrapers (High Server Load, No Traffic)\n' +
                       'User-agent: ByteSpider\nDisallow: /\n\n' +
                       'User-agent: CCBot\nDisallow: /\n\n' +
                       'User-agent: Amazonbot\nDisallow: /\n\n' +
                       'User-agent: Scrapy\nDisallow: /\n\n';
            } else if (preset === 'strict') {
                txt += '# Block All AI Crawlers and LLM Bots\n' +
                       'User-agent: GPTBot\nDisallow: /\n\n' +
                       'User-agent: ChatGPT-User\nDisallow: /\n\n' +
                       'User-agent: OAI-SearchBot\nDisallow: /\n\n' +
                       'User-agent: ClaudeBot\nDisallow: /\n\n' +
                       'User-agent: anthropic-ai\nDisallow: /\n\n' +
                       'User-agent: PerplexityBot\nDisallow: /\n\n' +
                       'User-agent: Google-Extended\nDisallow: /\n\n' +
                       'User-agent: Applebot-Extended\nDisallow: /\n\n' +
                       'User-agent: ByteSpider\nDisallow: /\n\n' +
                       'User-agent: CCBot\nDisallow: /\n\n' +
                       'User-agent: Amazonbot\nDisallow: /\n\n';
            } else {
                txt += '# Permitted Generative AI Search & Citation Engines\n' +
                       'User-agent: PerplexityBot\nAllow: /\n\n' +
                       'User-agent: ChatGPT-User\nAllow: /\n\n' +
                       'User-agent: OAI-SearchBot\nAllow: /\n\n' +
                       'User-agent: Google-Extended\nAllow: /\n\n' +
                       'User-agent: Applebot-Extended\nAllow: /\n\n' +
                       '# Disallow Mass LLM Training Scrapers (No Direct Clicks)\n' +
                       'User-agent: GPTBot\nDisallow: /\n\n' +
                       'User-agent: ClaudeBot\nDisallow: /\n\n' +
                       'User-agent: anthropic-ai\nDisallow: /\n\n' +
                       'User-agent: ByteSpider\nDisallow: /\n\n' +
                       'User-agent: CCBot\nDisallow: /\n\n' +
                       'User-agent: Amazonbot\nDisallow: /\n\n';
            }

            txt += 'Sitemap: ' + homeBase + '/sitemap_index.xml\n' +
                   'Sitemap: ' + homeBase + '/wp-sitemap.xml';

            return txt;
        }

        function updateRobotsPreview() {
            const selectedPreset = $('input[name="kavoshrank_bot_governor_preset"]:checked').val() || 'balanced';
            $('#kr-robots-preview-content').text(generateRobotsPreview(selectedPreset));
        }

        $('.kr-preset-card').on('click', function() {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            $('.kr-preset-card').removeClass('selected');
            $(this).addClass('selected');
            updateRobotsPreview();
        });

        $('input[name="kavoshrank_bot_governor_preset"]').on('change', function() {
            updateRobotsPreview();
        });

        // Initialize robots preview
        updateRobotsPreview();

        // Copy Robots.txt preview to clipboard
        $('#kr-btn-copy-robots').on('click', function(e) {
            e.preventDefault();
            const content = $('#kr-robots-preview-content').text();
            navigator.clipboard.writeText(content).then(function() {
                kavoshToast('قوانین robots.txt در کلیپ‌بورد کپی شد.', 'success');
            });
        });

        // -------------------------------------------------------------
        // 5. Unsaved Changes Tracking
        // -------------------------------------------------------------
        $settingsForm.on('input change', 'input, select, textarea', function() {
            if (!isFormDirty) {
                isFormDirty = true;
                if ($unsavedBadge.length) {
                    $unsavedBadge.fadeIn();
                }
            }
        });

        $settingsForm.on('submit', function() {
            isFormDirty = false;
        });

        // Dismiss alert notices
        $(document).on('click', '.kr-alert-close', function(e) {
            e.preventDefault();
            $(this).closest('.kr-alert').fadeOut(200);
        });

        // -------------------------------------------------------------
        // 6. Dashboard Real-time Server Ping Test
        // -------------------------------------------------------------
        $('#kr-btn-dashboard-ping').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $resultBox = $('#kr-dashboard-ping-result');
            const apiUrl = (window.kavoshRankAdmin && window.kavoshRankAdmin.golangUrl) || '';

            if (!apiUrl) {
                if (window.kavoshToast) {
                    kavoshToast('آدرس API پلتفرم هنوز تنظیم نشده است. لطفاً به صفحه تنظیمات مراجعه نمایید.', 'error');
                }
                $resultBox.show().html(
                    '<div class="kr-alert kr-alert-error" style="margin:0;"><div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>آدرس سرور API تنظیم نشده است. لطفاً ابتدا در صفحه تنظیمات آن را وارد و ذخیره نمایید.</span></div></div>'
                ).fadeIn();
                return;
            }

            const originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html(
                '<svg class="kr-icon kr-icon-sm kr-spinner" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> در حال بررسی ارتباط...'
            );

            $resultBox.hide();

            $.ajax({
                url: kavoshRankAdmin.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kavoshrank_test_connection',
                    nonce: kavoshRankAdmin.nonce,
                    api_url: apiUrl
                },
                success: function(resp) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    if (resp && resp.success) {
                        const latency = resp.data && resp.data.latency_ms ? resp.data.latency_ms + 'ms' : '';
                        const msg = (resp.data && resp.data.message) || 'اتصال زنده با پلتفرم کاوش‌رنک با موفقیت برقرار شد.';
                        $resultBox.html(
                            '<div class="kr-alert kr-alert-success" style="margin:0;"><div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg><span>' +
                            msg + (latency ? ' <strong>(تاخیر پینگ: ' + latency + ')</strong>' : '') +
                            '</span></div></div>'
                        ).fadeIn();
                        if (window.kavoshToast) {
                            kavoshToast('ارتباط با پلتفرم با موفقیت تأیید شد.', 'success');
                        }
                    } else {
                        const errMsg = (resp && resp.data && resp.data.message) || 'عدم موفقیت در برقراری اتصال با سرور پلتفرم.';
                        $resultBox.html(
                            '<div class="kr-alert kr-alert-error" style="margin:0;"><div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>' +
                            errMsg + '</span></div></div>'
                        ).fadeIn();
                        if (window.kavoshToast) {
                            kavoshToast(errMsg, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    const errMsg = 'خطا در برقراری ارتباط با سرور: ' + (error || status);
                    $resultBox.html(
                        '<div class="kr-alert kr-alert-error" style="margin:0;"><div class="kr-alert-inner"><svg class="kr-icon kr-icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>' +
                        errMsg + '</span></div></div>'
                    ).fadeIn();
                    if (window.kavoshToast) {
                        kavoshToast(errMsg, 'error');
                    }
                }
            });
        });
    });

})(jQuery);
