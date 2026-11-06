/**
 * KavoshRank Storefront AI Copilot Widget Script
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const config = window.kavoshrankCopilotConfig || {};
        const widget = document.getElementById('kr-copilot-widget');
        if (!widget) return;

        const launcher = document.getElementById('kr-copilot-launcher');
        const card = document.getElementById('kr-copilot-card');
        const closeBtn = document.getElementById('kr-copilot-close');
        const messagesContainer = document.getElementById('kr-copilot-messages');
        const input = document.getElementById('kr-copilot-input');
        const sendBtn = document.getElementById('kr-copilot-send');
        const pillsContainer = document.getElementById('kr-copilot-pills');

        let chatHistory = [];
        const storageKey = 'kr_copilot_history';
        const unavailableMessage = config.unavailable_message || 'دستیار در حال حاضر در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.';

        // Load saved session history
        try {
            const saved = sessionStorage.getItem(storageKey);
            if (saved) {
                chatHistory = JSON.parse(saved);
            }
        } catch (e) {
            chatHistory = [];
        }

        // Apply brand color variable
        if (config.brand_color) {
            widget.style.setProperty('--kr-brand-color', config.brand_color);
        }

        // Toggle Widget
        function toggleWidget() {
            const isOpen = card.classList.contains('kr-open');
            if (isOpen) {
                card.classList.remove('kr-open');
            } else {
                card.classList.add('kr-open');
                if (chatHistory.length === 0) {
                    renderGreeting();
                }
                setTimeout(() => input.focus(), 150);
            }
        }

        launcher.addEventListener('click', toggleWidget);
        closeBtn.addEventListener('click', toggleWidget);

        // Initial Greeting
        function renderGreeting() {
            const greeting = config.greeting_message || 'سلام! من مشاور هوشمند شما هستم. چطور می‌توانم در انتخاب محصول به شما کمک کنم؟';
            appendMessage('assistant', greeting);
            renderPills(config.suggested_questions || []);
        }

        // Render previous messages
        if (chatHistory.length > 0) {
            chatHistory.forEach(item => {
                appendMessageToDOM(item.role, item.content, item.recommendations);
            });
            renderPills(config.suggested_questions || []);
        }

        // Append message to DOM and memory
        function appendMessage(role, content, recommendations) {
            chatHistory.push({ role, content, recommendations });
            try {
                sessionStorage.setItem(storageKey, JSON.stringify(chatHistory.slice(-20)));
            } catch (e) {}

            appendMessageToDOM(role, content, recommendations);
        }

        function appendMessageToDOM(role, content, recommendations) {
            const msgEl = document.createElement('div');
            msgEl.className = 'kr-msg kr-msg-' + role;
            msgEl.textContent = content;

            if (recommendations && recommendations.length > 0) {
                recommendations.forEach(rec => {
                    const cardEl = document.createElement('div');
                    cardEl.className = 'kr-copilot-product-card';
                    cardEl.innerHTML = `
                        <div class="kr-copilot-product-title">${escapeHTML(rec.title)}</div>
                        ${rec.reason ? `<div class="kr-copilot-product-reason">${escapeHTML(rec.reason)}</div>` : ''}
                        <div class="kr-copilot-product-actions">
                            ${rec.add_to_cart_url ? `<a href="${escapeHTML(rec.add_to_cart_url)}" class="kr-copilot-btn-cart">افزودن به سبد</a>` : ''}
                            ${rec.permalink ? `<a href="${escapeHTML(rec.permalink)}" target="_blank" class="kr-copilot-btn-view">مشاهده کالا</a>` : ''}
                        </div>
                    `;
                    msgEl.appendChild(cardEl);
                });
            }

            messagesContainer.appendChild(msgEl);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Render suggestion pills
        function renderPills(pills) {
            pillsContainer.innerHTML = '';
            if (!pills || pills.length === 0) return;

            pills.forEach(pillText => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kr-copilot-pill';
                btn.textContent = pillText;
                btn.addEventListener('click', function() {
                    sendMessage(pillText);
                });
                pillsContainer.appendChild(btn);
            });
        }

        // Show Typing Indicator
        let typingIndicator = null;
        function showTyping() {
            if (typingIndicator) return;
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'kr-msg kr-msg-assistant kr-copilot-typing';
            typingIndicator.innerHTML = '<span></span><span></span><span></span>';
            messagesContainer.appendChild(typingIndicator);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTyping() {
            if (typingIndicator && typingIndicator.parentNode) {
                typingIndicator.parentNode.removeChild(typingIndicator);
            }
            typingIndicator = null;
        }

        // Send Message
        function sendMessage(text) {
            const query = text || input.value.trim();
            if (!query) return;

            appendMessage('user', query);
            input.value = '';
            showTyping();

            // Earlier turns as plain text only, without product cards.
            const history = chatHistory.slice(-7, -1).map(item => ({
                role: item.role,
                content: item.content
            }));

            // Public REST route on this site: no nonce, so it works on cached pages.
            fetch(config.rest_url, {
                method: 'POST',
                credentials: 'omit',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: query,
                    product_id: config.product_id || 0,
                    history: history
                })
            })
            .then(res => res.json().catch(() => ({})))
            .then(data => {
                hideTyping();
                if (data && data.success && data.data && data.data.answer) {
                    appendMessage('assistant', data.data.answer, data.data.recommendations);
                } else {
                    appendMessage('assistant', (data && data.message) || unavailableMessage);
                }
            })
            .catch(() => {
                hideTyping();
                appendMessage('assistant', unavailableMessage);
            });
        }

        sendBtn.addEventListener('click', () => sendMessage());
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        function escapeHTML(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
})();
