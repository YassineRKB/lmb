<?php
// FILE: includes/class-lmb-maintenance-utilities.php

if (!defined('ABSPATH')) exit;

/**
 * Handles scheduled cleanup of orphaned PDF files (Journals and Accusé).
 */
class LMB_Maintenance_Utilities {

    const CLEANUP_CRON_HOOK = 'lmb_monthly_file_cleanup';

    public static function init() {
        // Schedule the event
        add_action('init', [__CLASS__, 'schedule_cleanup']);
        add_action(self::CLEANUP_CRON_HOOK, [__CLASS__, 'run_cleanup']);
    }

    public static function schedule_cleanup() {
        if (!wp_next_scheduled(self::CLEANUP_CRON_HOOK)) {
            // Schedule to run once a month starting tomorrow
            if (!defined('DAY_IN_SECONDS')) {
                define('DAY_IN_SECONDS', 86400); // Fallback definition
            }
            wp_schedule_event(time() + DAY_IN_SECONDS, 'monthly', self::CLEANUP_CRON_HOOK);
        }
    }

    /**
     * Executes the main cleanup logic.
     */
    public static function run_cleanup() {
        // Ensure WordPress environment is loaded for file operations
        if (!function_exists('wp_delete_attachment') || !function_exists('get_posts')) {
            require_once(ABSPATH . 'wp-admin/includes/post.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Ensure LMB_Ad_Manager exists for logging
        if (!class_exists('LMB_Ad_Manager')) {
            // Assuming the path is correct
            require_once(LMB_CORE_PATH . 'includes/class-lmb-ad-manager.php');
        }
        
        $deleted_attachments_count = self::cleanup_orphaned_attachments();
        $deleted_accuse_files_count = self::cleanup_orphaned_accuse_pdfs();
        
        // Log the result of the cleanup cycle
        LMB_Ad_Manager::log_activity(sprintf(
            'Cycle de nettoyage mensuel terminé. Attachments supprimés : %d. Fichiers Accusé orphelins supprimés : %d.',
            $deleted_attachments_count,
            $deleted_accuse_files_count
        ));
    }

    /**
     * Cleans up orphaned Temporary/Final Journal PDFs (which are WP attachments).
     */
    private static function cleanup_orphaned_attachments() {
        $allowed_ids = [];
        $deleted_count = 0;

        // --- 1. Collect ALL associated attachment IDs (Allowed IDs) ---
        
        // a) Collect Temporary Journal IDs from Legal Ads (lmb_legal_ad)
        $temp_ids = get_posts([
            'post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => 'lmb_temporary_journal_id', 'compare' => 'EXISTS']
            ],
        ]);
        foreach ($temp_ids as $ad_id) {
            $allowed_ids[] = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
        }

        // b) Collect Final Journal PDF IDs from Newspaper Posts (lmb_newspaper)
        $final_journal_pdfs = get_posts([
            'post_type' => 'lmb_newspaper', 'post_status' => 'publish', 'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => 'newspaper_pdf', 'compare' => 'EXISTS']
            ],
        ]);
        foreach ($final_journal_pdfs as $newspaper_id) {
            $allowed_ids[] = get_post_meta($newspaper_id, 'newspaper_pdf', true);
        }
        
        // Filter out duplicates and invalid IDs
        $allowed_ids = array_filter(array_unique(array_map('intval', $allowed_ids)));


        // --- 2. Collect ALL PDF attachments in the Media Library ---
        $all_pdf_attachments = get_posts([
            'post_type' => 'attachment', 'post_status' => 'any', 'posts_per_page' => -1,
            'post_mime_type' => 'application/pdf',
            'fields' => 'ids',
        ]);

        // --- 3. Identify and Delete Orphaned Attachments ---
        foreach ($all_pdf_attachments as $attachment_id) {
            if (!in_array($attachment_id, $allowed_ids)) {
                // Orphaned file found. Delete the attachment and the file.
                if (wp_delete_attachment($attachment_id, true)) {
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }
    
    /**
     * Cleans up orphaned Accusé PDFs (files linked by URL, not WP attachments).
     */
    private static function cleanup_orphaned_accuse_pdfs() {
        $upload_dir = wp_upload_dir();
        // Assuming the Accusé path is hardcoded here (this should be verified in LMB_PDF_Generator)
        $accuse_dir_path = trailingslashit($upload_dir['basedir']) . 'lmb-accuse/'; 
        $deleted_count = 0;
        
        if (!is_dir($accuse_dir_path)) {
            return 0; // Directory does not exist
        }

        // 1. Collect ALL currently associated Accusé URLs
        $associated_urls = get_posts([
            'post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => 'lmb_accuse_pdf_url',
            'meta_compare' => 'EXISTS',
        ]);
        
        $allowed_urls = [];
        foreach ($associated_urls as $ad_id) {
            $allowed_urls[] = get_post_meta($ad_id, 'lmb_accuse_pdf_url', true);
        }
        $allowed_urls = array_filter(array_unique($allowed_urls));

        // 2. Scan the Accusé generation directory for all PDF files
        $all_accuse_files = glob(trailingslashit($accuse_dir_path) . '*.pdf');

        // 3. Identify and Delete Orphaned Files
        foreach ($all_accuse_files as $file_path) {
            // Construct the URL from the file path for comparison
            $file_url = str_replace(
                trailingslashit($upload_dir['basedir']), 
                trailingslashit($upload_dir['baseurl']), 
                $file_path
            );
            
            if (!in_array($file_url, $allowed_urls)) {
                // Orphaned file found. Delete the file using PHP's unlink.
                if (unlink($file_path)) {
                    $deleted_count++;
                }
            }
        }
        
        return $deleted_count;
    }
}