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
        this.fetchList(); // Initial fetch
        setInterval(this.fetchList.bind(this), 30000); // Poll every 30 seconds
    };

    LMBNotifications.prototype.toggle = function(e) {
        e.stopPropagation();
        this.isOpen = !this.isOpen;
        this.dropdown.toggle(this.isOpen);
        this.bell.attr('aria-expanded', this.isOpen);
        if (this.isOpen) {
            this.fetchList(true); // Force fetch on open
        }
    };

    LMBNotifications.prototype.handleClickOutside = function(e) {
        if (this.isOpen && !this.widget[0].contains(e.target)) {
            this.toggle(e);
        }
    };

    LMBNotifications.prototype.render = function(items) {
        this.listEl.empty();
        if (!items || !items.length) {
            this.emptyEl.show();
            return;
        }
        this.emptyEl.hide();
        
        items.forEach(function(item) {
            const itemClass = item.is_read == '0' ? 'lmb-item unread' : 'lmb-item';
            const row = $(`<div class="${itemClass}" role="menuitem" data-id="${item.id}"></div>`);
            row.html(`
                <div class="icon"><i class="fas fa-bell"></i></div>
                <div class="body">
                    <div class="title"><strong>${item.title}</strong></div>
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
        if (n > 0) {
            this.badge.show();
        } else {
            this.badge.hide();
        }
    };

    LMBNotifications.prototype.fetchList = function(force = false) {
        if (!this.isOpen && !force) { // Only poll if dropdown is closed, unless forced
            $.post(lmb_ajax_params.ajaxurl, { action: 'lmb_get_notifications', nonce: lmb_ajax_params.nonce })
                .done(res => { if (res.success) this.setBadge(res.data.unread); });
            return;
        }

        this.listEl.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i></div>');
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

    // Initialize all notification widgets on the page
    $(function() {
        $('.lmb-notifications').each(function() {
            new LMBNotifications(this);
        });
    });

})(jQuery);