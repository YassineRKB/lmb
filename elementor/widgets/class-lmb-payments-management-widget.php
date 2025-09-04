<?php
// FILE: elementor/widgets/class-lmb-payments-management-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Payments_Management_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_payments_management';
    }

    public function get_title() {
        return __('Payments Management', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-price-table';
    }

    public function get_categories() {
        return ['lmb-admin-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-payments-management'];
    }

    public function get_style_depends() {
        return ['lmb-payments-management'];
    }

    protected function render() {
        ?>
        <div class="lmb-payments-management">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Payments Management</h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <form id="lmb-payments-filters-form">
                        <div class="lmb-filter-grid">
                            <input type="text" name="filter_ref" placeholder="Search by Invoice Ref..." class="lmb-filter-input">
                            <input type="text" name="filter_client" placeholder="Search by Client Name..." class="lmb-filter-input">
                            <select name="filter_status" class="lmb-filter-select">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="denied">Denied</option>
                                <option value="">All Statuses</option>
                            </select>
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th>Invoice Ref</th>
                                <th>Client</th>
                                <th>Package</th>
                                <th>Price</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                 <div class="lmb-pagination-container">
                    </div>
            </div>
        </div>
        <?php
    }
}