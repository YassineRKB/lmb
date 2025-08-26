// FILE: assets/js/lmb-notifications.js
(function($) {
    'use strict';

    function LMBNotifications(widget) {
        this.widget = $(widget);
        this.bell = this.widget.find('.lmb-bell');
        this.badge = this.widget.find('.lmb-badge');
        this.dropdown = this.widget.find('.lmb-dropdown');
        this.listEl = this.widget.find('.lmb-list');
        this.emptyEl = this.widget.find('.lmb-empty');
        this.markAllBtn = this.widget.find('.lmb-mark-all');
        this.isOpen = false;
        
        this.init();
    }

    LMBNotifications.prototype.init = function() {
        this.bell.on('click', this.toggle.bind(this));
        this.markAllBtn.on('click', this.markAllRead.bind(this));
        $(document).on('click', this.handleClickOutside.bind(this));
        this.fetchList(true); // Initial fetch to set the badge count
        setInterval(() => this.fetchList(true), 30000); // Poll every 30 seconds
    };

    LMBNotifications.prototype.toggle = function(e) {
        e.stopPropagation();
        this.isOpen = !this.isOpen;
        // --- FIX: Use a class to toggle visibility for CSS transitions ---
        this.dropdown.toggleClass('lmb-show', this.isOpen);
        this.bell.attr('aria-expanded', this.isOpen);
        if (this.isOpen) {
            this.fetchList(true); // Force fetch on open
        }
    };

    LMBNotifications.prototype.handleClickOutside = function(e) {
        if (this.isOpen && !this.widget[0].contains(e.target)) {
            this.isOpen = false;
            this.dropdown.removeClass('lmb-show');
            this.bell.attr('aria-expanded', false);
        }
    };

    LMBNotifications.prototype.render = function(items) {
        this.listEl.empty();
        if (!items || !items.length) {
            this.emptyEl.show();
            this.listEl.hide();
            return;
        }
        this.emptyEl.hide();
        this.listEl.show();
        
        items.forEach(function(item) {
            const itemClass = item.is_read == '0' ? 'lmb-item unread' : 'lmb-item';
            const iconClass = this.getIconForType(item.type);
            const row = $(`<div class="${itemClass}" role="menuitem" data-id="${item.id}"></div>`);
            
            row.html(`
                <div class="icon"><i class="fas ${iconClass}"></i></div>
                <div class="body">
                    <div class="title">${item.title}</div>
                    <div class="msg">${item.message}</div>
                    <div class="meta">${item.time_ago}</div>
                </div>
            `);
            row.on('click', () => this.markRead(item.id));
            this.listEl.append(row);
        }.bind(this));
    };
    
    LMBNotifications.prototype.setBadge = function(n) {
        n = parseInt(n || 0, 10);
        this.badge.text(n).attr('data-count', n);
        this.markAllBtn.prop('disabled', n === 0);
    };

    LMBNotifications.prototype.fetchList = function(force = false) {
        // Only poll if dropdown is closed OR it's the initial load (force=true)
        if (!this.isOpen && !force) {
            $.post(lmb_ajax_params.ajaxurl, { action: 'lmb_get_notifications', nonce: lmb_ajax_params.nonce })
                .done(res => { if (res.success) this.setBadge(res.data.unread); });
            return;
        }

        if(this.isOpen) this.listEl.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i></div>');
        
        $.post(lmb_ajax_params.ajaxurl, { action: 'lmb_get_notifications', nonce: lmb_ajax_params.nonce })
            .done(res => {
                if (res.success) {
                    this.render(res.data.items);
                    this.setBadge(res.data.unread);
                }
            });
    };

    LMBNotifications.prototype.markRead = function(id) {
        const itemEl = this.listEl.find(`[data-id="${id}"]`);
        itemEl.removeClass('unread');
        
        $.post(lmb_ajax_params.ajaxurl, { action: 'lmb_mark_notification_read', nonce: lmb_ajax_params.nonce, id: id })
            .done(() => this.fetchList(true));
    };

    LMBNotifications.prototype.markAllRead = function() {
        this.listEl.find('.unread').removeClass('unread');
        this.markAllBtn.prop('disabled', true);
        
        $.post(lmb_ajax_params.ajaxurl, { action: 'lmb_mark_all_notifications_read', nonce: lmb_ajax_params.nonce })
            .done(() => this.fetchList(true));
    };

    LMBNotifications.prototype.getIconForType = function(type) {
        switch (type) {
            case 'ad_approved':
            case 'payment_approved':
            case 'receipt_ready':
                return 'fa-check-circle';
            case 'ad_denied':
            case 'payment_rejected':
                return 'fa-times-circle';
            case 'ad_pending':
            case 'proof_submitted':
                return 'fa-clock';
            default:
                return 'fa-bell';
        }
    };

    $(function() {
        $('.lmb-notifications').each(function() {
            new LMBNotifications(this);
        });
    });

})(jQuery);