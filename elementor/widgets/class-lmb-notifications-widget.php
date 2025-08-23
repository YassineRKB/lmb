<?php
use Elementor\Widget_Base;
if (!defined('ABSPATH')) exit;

class LMB_Notifications_Widget extends Widget_Base {
    public function get_name() { return 'lmb_notifications'; }
    public function get_title(){ return __('LMB Notifications','lmb-core'); }
    public function get_icon() { return 'eicon-bell'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view notifications.', 'lmb-core') . '</p></div>';
            return;
        }

        // Ensure manager is available
        if (!class_exists('LMB_Notification_Manager')) {
            require_once LMB_CORE_PATH . 'includes/class-lmb-notification-manager.php';
        }

        $uid   = get_current_user_id();
        $nonce = wp_create_nonce(LMB_Notification_Manager::NONCE);
        $unread = method_exists('LMB_Notification_Manager','get_unread_count') ? LMB_Notification_Manager::get_unread_count($uid) : 0;
        $wid = esc_attr($this->get_id());
        ?>
        <div class="lmb-notifications" id="lmb-notifications-<?php echo $wid; ?>">
            <button type="button" class="lmb-bell" aria-haspopup="true" aria-expanded="false" aria-controls="lmb-dropdown-<?php echo $wid; ?>">
                <i class="fas fa-bell" aria-hidden="true"></i>
                <span class="lmb-badge" data-count="<?php echo (int)$unread; ?>"><?php echo (int)$unread; ?></span>
                <span class="screen-reader-text"><?php esc_html_e('Toggle notifications', 'lmb-core'); ?></span>
            </button>
            <div class="lmb-dropdown" id="lmb-dropdown-<?php echo $wid; ?>" role="menu" aria-label="<?php esc_attr_e('Notifications', 'lmb-core'); ?>" style="display:none">
                <div class="lmb-dropdown-header">
                    <strong><?php esc_html_e('Notifications', 'lmb-core'); ?></strong>
                    <button type="button" class="lmb-mark-all" <?php disabled($unread === 0); ?>><?php esc_html_e('Mark all as read', 'lmb-core'); ?></button>
                </div>
                <div class="lmb-list" aria-live="polite"></div>
                <div class="lmb-empty" style="display:none;"><em><?php esc_html_e('No notifications yet.', 'lmb-core'); ?></em></div>
            </div>
        </div>
        <style>
        .lmb-notifications{position:relative;display:inline-block}
        .lmb-bell{display:flex;align-items:center;gap:.5rem;background:transparent;border:0;cursor:pointer}
        .lmb-badge{min-width:18px;height:18px;line-height:18px;text-align:center;border-radius:9px;padding:0 6px;font-size:12px;display:inline-block;background:#d33;color:#fff}
        .lmb-dropdown{position:absolute;right:0;top:125%;width:360px;max-width:95vw;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.08);z-index:9999}
        .lmb-dropdown-header{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #f0f2f4}
        .lmb-list{max-height:360px;overflow:auto}
        .lmb-item{display:flex;gap:10px;padding:12px;border-bottom:1px solid #f7f7f8;cursor:pointer}
        .lmb-item[aria-current="false"]{background:#fff}
        .lmb-item[aria-current="true"]{background:#f9fafb}
        .lmb-item:hover{background:#f5f7fb}
        .lmb-item .meta{font-size:12px;color:#6b7280}
        .lmb-empty{padding:20px;text-align:center;color:#6b7280}
        .lmb-mark-all{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;cursor:pointer}
        </style>
        <script>
        (function(){
            const wrap   = document.getElementById('lmb-notifications-<?php echo $wid; ?>');
            const bell   = wrap.querySelector('.lmb-bell');
            const badge  = wrap.querySelector('.lmb-badge');
            const menu   = document.getElementById('lmb-dropdown-<?php echo $wid; ?>');
            const listEl = menu.querySelector('.lmb-list');
            const emptyEl= menu.querySelector('.lmb-empty');
            const markAllBtn = menu.querySelector('.lmb-mark-all');
            const ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            const nonce   = '<?php echo esc_js($nonce); ?>';

            let open = false;

            function setBadge(n){
                n = parseInt(n||0,10);
                badge.textContent = n; badge.setAttribute('data-count', n);
                markAllBtn.disabled = (n === 0);
            }
            function closeMenu(){
                if(!open) return; open = false; menu.style.display='none'; bell.setAttribute('aria-expanded','false');
                document.removeEventListener('click', outside);
                window.removeEventListener('keydown', esc);
            }
            function outside(e){ if(!wrap.contains(e.target)) closeMenu(); }
            function esc(e){ if(e.key==='Escape') closeMenu(); }

            function render(items){
                listEl.innerHTML='';
                if(!items || !items.length){ emptyEl.style.display='block'; return; }
                emptyEl.style.display='none';
                items.forEach(function(it){
                    const row = document.createElement('div');
                    row.className='lmb-item';
                    row.setAttribute('role','menuitem');
                    row.setAttribute('data-id', it.id);
                    row.setAttribute('aria-current', it.is_read ? 'true' : 'false');
                    row.innerHTML = '<div class="icon"><i class="fas fa-bell"></i></div>'+
                                      '<div class="body"><div class="title"><strong>'+it.title+'</strong></div>'+
                                      '<div class="msg">'+it.message+'</div>'+
                                      '<div class="meta">'+it.time_ago+'</div></div>';
                    row.addEventListener('click', function(){ markRead(it.id); });
                    listEl.appendChild(row);
                });
            }

            function fetchList(){
                const fd = new FormData();
                fd.append('action','lmb_get_notifications');
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', credentials:'same-origin', body: fd })
                    .then(r=>r.json()).then(function(res){
                        if(res && res.success){ render(res.data.items); setBadge(res.data.unread); }
                    }).catch(()=>{});
            }

            function markRead(id){
                const fd = new FormData();
                fd.append('action','lmb_mark_notification_read');
                fd.append('id', id);
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', credentials:'same-origin', body: fd })
                    .then(r=>r.json()).then(function(){ fetchList(); });
            }

            markAllBtn.addEventListener('click', function(){
                const fd = new FormData();
                fd.append('action','lmb_mark_all_notifications_read');
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', credentials:'same-origin', body: fd })
                    .then(r=>r.json()).then(function(){ fetchList(); });
            });

            bell.addEventListener('click', function(e){
                e.stopPropagation();
                open = !open; menu.style.display = open ? 'block' : 'none';
                bell.setAttribute('aria-expanded', open ? 'true' : 'false');
                if(open){ fetchList(); document.addEventListener('click', outside); window.addEventListener('keydown', esc); }
            });

            // Lightweight polling to keep counts fresh (30s)
            setInterval(function(){ if(!open){ fetchList(); } }, 30000);
        })();
        </script>
        <?php
    }
}