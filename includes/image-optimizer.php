<?php

/**
 * RB image optimization.
 *
 * New JPEG/PNG uploads are converted to WebP. Existing attachments can be
 * converted in small AJAX batches from Tools > Image optimization.
 */

if (!defined('ABSPATH')) {
    exit;
}

function RB_image_optimizer_webp_quality()
{
    return max(1, min(100, (int) apply_filters('RB_image_optimizer_webp_quality', 82)));
}

function RB_image_optimizer_legacy_quality()
{
    return max(1, min(100, (int) apply_filters('RB_image_optimizer_legacy_quality', 82)));
}

function RB_image_optimizer_supports_webp()
{
    return wp_image_editor_supports(array('mime_type' => 'image/webp'));
}

function RB_image_optimizer_is_supported_mime($mime_type)
{
    return in_array($mime_type, array('image/jpeg', 'image/png'), true);
}

function RB_image_optimizer_convert_file($source_path)
{
    if (!is_file($source_path) || !is_readable($source_path)) {
        return new WP_Error('source_not_readable', 'The source image is not readable.');
    }

    if (!RB_image_optimizer_supports_webp()) {
        return new WP_Error('webp_not_supported', 'WebP is not supported by the server image library.');
    }

    $editor = wp_get_image_editor($source_path);
    if (is_wp_error($editor)) {
        return $editor;
    }

    $quality_result = $editor->set_quality(RB_image_optimizer_webp_quality());
    if (is_wp_error($quality_result)) {
        return $quality_result;
    }

    $directory = dirname($source_path);
    $base_name = pathinfo($source_path, PATHINFO_FILENAME) . '.webp';
    $destination = trailingslashit($directory) . wp_unique_filename($directory, $base_name);
    $saved = $editor->save($destination, 'image/webp');

    if (is_wp_error($saved)) {
        return $saved;
    }

    $webp_path = isset($saved['path']) ? $saved['path'] : $destination;
    if (!is_file($webp_path) || filesize($webp_path) < 1) {
        return new WP_Error('webp_not_created', 'The WebP image was not created.');
    }

    $source_permissions = @fileperms($source_path);
    if ($source_permissions) {
        @chmod($webp_path, $source_permissions & 0777);
    }

    return array(
        'path' => $webp_path,
        'mime' => 'image/webp',
        'size' => (int) filesize($webp_path),
    );
}

function RB_image_optimizer_handle_upload($upload, $context = 'upload')
{
    if (!empty($upload['error']) || empty($upload['file']) || empty($upload['type'])) {
        return $upload;
    }

    if (!RB_image_optimizer_is_supported_mime($upload['type'])) {
        return $upload;
    }

    $converted = RB_image_optimizer_convert_file($upload['file']);
    if (is_wp_error($converted)) {
        return $upload;
    }

    $old_file = $upload['file'];
    $old_url = $upload['url'];
    $upload['file'] = $converted['path'];
    $upload['url'] = trailingslashit(dirname($old_url)) . rawurlencode(basename($converted['path']));
    $upload['type'] = $converted['mime'];

    if (wp_normalize_path($old_file) !== wp_normalize_path($converted['path'])) {
        wp_delete_file($old_file);
    }

    return $upload;
}
add_filter('wp_handle_upload', 'RB_image_optimizer_handle_upload', 20, 2);

function RB_image_optimizer_compress_file_in_place($file_path, $mime_type)
{
    if (!is_file($file_path) || !is_writable($file_path) || !RB_image_optimizer_is_supported_mime($mime_type)) {
        return false;
    }

    $editor = wp_get_image_editor($file_path);
    if (is_wp_error($editor)) {
        return false;
    }

    $quality_result = $editor->set_quality(RB_image_optimizer_legacy_quality());
    if (is_wp_error($quality_result)) {
        return false;
    }

    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $temporary_path = trailingslashit(dirname($file_path))
        . wp_unique_filename(dirname($file_path), pathinfo($file_path, PATHINFO_FILENAME) . '-mango-temp.' . $extension);
    $saved = $editor->save($temporary_path, $mime_type);

    if (is_wp_error($saved) || !is_file($temporary_path)) {
        if (is_file($temporary_path)) {
            wp_delete_file($temporary_path);
        }
        return false;
    }

    $old_size = (int) filesize($file_path);
    $new_size = (int) filesize($temporary_path);
    if ($new_size < 1 || $new_size >= $old_size) {
        wp_delete_file($temporary_path);
        return false;
    }

    $directory = dirname($file_path);
    $backup_path = trailingslashit($directory)
        . wp_unique_filename($directory, pathinfo($file_path, PATHINFO_FILENAME) . '-mango-backup.' . $extension);
    $permissions = @fileperms($file_path);

    if (!@rename($file_path, $backup_path)) {
        wp_delete_file($temporary_path);
        return false;
    }

    if (!@rename($temporary_path, $file_path)) {
        @rename($backup_path, $file_path);
        wp_delete_file($temporary_path);
        return false;
    }

    if ($permissions) {
        @chmod($file_path, $permissions & 0777);
    }
    wp_delete_file($backup_path);

    return true;
}

function RB_image_optimizer_compress_legacy_files($source_path, $metadata, $mime_type)
{
    $files = array($source_path);
    $directory = dirname($source_path);

    if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $size_data) {
            if (!empty($size_data['file'])) {
                $files[] = trailingslashit($directory) . $size_data['file'];
            }
        }
    }

    if (!empty($metadata['original_image'])) {
        $files[] = trailingslashit($directory) . $metadata['original_image'];
    }

    foreach (array_unique($files) as $file_path) {
        RB_image_optimizer_compress_file_in_place($file_path, $mime_type);
    }
}

function RB_image_optimizer_convert_attachment($attachment_id)
{
    $attachment_id = (int) $attachment_id;
    $mime_type = get_post_mime_type($attachment_id);
    $source_path = get_attached_file($attachment_id, true);

    if (!$attachment_id || !$source_path || !RB_image_optimizer_is_supported_mime($mime_type)) {
        return new WP_Error('unsupported_attachment', 'The attachment is not a JPEG or PNG image.');
    }

    $uploads = wp_get_upload_dir();
    $normalized_source = wp_normalize_path($source_path);
    $normalized_uploads = trailingslashit(wp_normalize_path($uploads['basedir']));
    if (strpos($normalized_source, $normalized_uploads) !== 0) {
        return new WP_Error('outside_uploads', 'The image is outside the WordPress uploads directory.');
    }

    $old_metadata = wp_get_attachment_metadata($attachment_id);
    $old_metadata = is_array($old_metadata) ? $old_metadata : array();
    $old_relative_file = get_post_meta($attachment_id, '_wp_attached_file', true);
    $converted = RB_image_optimizer_convert_file($source_path);
    if (is_wp_error($converted)) {
        return $converted;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    update_attached_file($attachment_id, $converted['path']);
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_mime_type' => 'image/webp',
    ));

    $new_metadata = wp_generate_attachment_metadata($attachment_id, $converted['path']);
    if (!is_array($new_metadata) || empty($new_metadata['file'])) {
        update_attached_file($attachment_id, $source_path);
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_mime_type' => $mime_type,
        ));
        if ($old_relative_file) {
            update_post_meta($attachment_id, '_wp_attached_file', $old_relative_file);
        }
        wp_delete_file($converted['path']);
        return new WP_Error('metadata_failed', 'WordPress could not generate WebP attachment metadata.');
    }

    wp_update_attachment_metadata($attachment_id, $new_metadata);
    update_post_meta($attachment_id, '_RB_image_optimizer_processed', 'success');
    update_post_meta($attachment_id, '_RB_image_optimizer_source_file', $old_relative_file);

    // Keep legacy files for URLs already embedded in post content, but reduce their size.
    RB_image_optimizer_compress_legacy_files($source_path, $old_metadata, $mime_type);

    clean_post_cache($attachment_id);

    return array(
        'attachment_id' => $attachment_id,
        'source_size' => is_file($source_path) ? (int) filesize($source_path) : 0,
        'webp_size' => (int) $converted['size'],
    );
}

function RB_image_optimizer_query($limit = 1)
{
    return new WP_Query(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'posts_per_page' => (int) $limit,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
        'no_found_rows' => false,
        'meta_query' => array(
            array(
                'key' => '_RB_image_optimizer_processed',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ));
}

function RB_image_optimizer_ajax_start()
{
    check_ajax_referer('RB_image_optimizer', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You are not allowed to optimize images.'), 403);
    }

    delete_post_meta_by_key('_RB_image_optimizer_processed');
    $query = RB_image_optimizer_query(1);

    wp_send_json_success(array(
        'total' => (int) $query->found_posts,
        'webp_supported' => RB_image_optimizer_supports_webp(),
    ));
}
add_action('wp_ajax_RB_image_optimizer_start', 'RB_image_optimizer_ajax_start');

function RB_image_optimizer_ajax_batch()
{
    check_ajax_referer('RB_image_optimizer', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You are not allowed to optimize images.'), 403);
    }

    if (!RB_image_optimizer_supports_webp()) {
        wp_send_json_error(array('message' => 'WebP is not supported by the server image library.'));
    }

    wp_raise_memory_limit('image');
    $query = RB_image_optimizer_query(3);
    $converted_count = 0;
    $failed_count = 0;
    $messages = array();

    foreach ($query->posts as $attachment_id) {
        $result = RB_image_optimizer_convert_attachment($attachment_id);
        if (is_wp_error($result)) {
            $failed_count++;
            update_post_meta($attachment_id, '_RB_image_optimizer_processed', 'failed');
            update_post_meta($attachment_id, '_RB_image_optimizer_error', $result->get_error_message());
            $messages[] = sprintf('#%d: %s', $attachment_id, $result->get_error_message());
            continue;
        }

        delete_post_meta($attachment_id, '_RB_image_optimizer_error');
        $converted_count++;
    }

    $remaining_query = RB_image_optimizer_query(1);
    wp_send_json_success(array(
        'processed' => count($query->posts),
        'converted' => $converted_count,
        'failed' => $failed_count,
        'remaining' => (int) $remaining_query->found_posts,
        'messages' => $messages,
    ));
}
add_action('wp_ajax_RB_image_optimizer_batch', 'RB_image_optimizer_ajax_batch');

function RB_image_optimizer_admin_menu()
{
    add_management_page(
        'Image optimization',
        'Image optimization',
        'manage_options',
        'RB-image-optimizer',
        'RB_image_optimizer_admin_page'
    );
}
add_action('admin_menu', 'RB_image_optimizer_admin_menu');

function RB_image_optimizer_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $nonce = wp_create_nonce('RB_image_optimizer');
    ?>
    <div class="wrap">
        <h1>Image optimization</h1>
        <p>New JPEG and PNG uploads are converted to WebP automatically. Use this tool to process existing Media Library images.</p>

        <div class="card" style="max-width: 760px; padding: 20px;">
            <h2 style="margin-top: 0;">Existing images</h2>
            <?php if (!RB_image_optimizer_supports_webp()) : ?>
                <div class="notice notice-error inline"><p>WebP is not supported by the active PHP image library. Enable WebP support in GD or Imagick first.</p></div>
            <?php else : ?>
                <p>Original JPEG/PNG files are retained for old URLs and compressed when possible. WordPress attachment URLs and generated sizes are switched to WebP.</p>
                <button type="button" class="button button-primary" id="RB-image-optimizer-start">Optimize existing images</button>
                <div id="RB-image-optimizer-progress" style="display:none; margin-top:20px;">
                    <div style="height:12px; max-width:600px; overflow:hidden; background:#dcdcde; border-radius:2px;">
                        <div id="RB-image-optimizer-bar" style="height:100%; width:0; background:#2271b1;"></div>
                    </div>
                    <p id="RB-image-optimizer-status" aria-live="polite"></p>
                    <div id="RB-image-optimizer-errors" style="color:#b32d2e;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (RB_image_optimizer_supports_webp()) : ?>
        <script>
        jQuery(function ($) {
            const $button = $('#RB-image-optimizer-start');
            const $progress = $('#RB-image-optimizer-progress');
            const $bar = $('#RB-image-optimizer-bar');
            const $status = $('#RB-image-optimizer-status');
            const $errors = $('#RB-image-optimizer-errors');
            let total = 0;
            let completed = 0;
            let converted = 0;
            let failed = 0;

            function request(action) {
                return $.post(ajaxurl, {
                    action: action,
                    nonce: <?php echo wp_json_encode($nonce); ?>
                });
            }

            function updateProgress(remaining) {
                const percentage = total > 0 ? Math.min(100, Math.round((completed / total) * 100)) : 100;
                $bar.css('width', percentage + '%');
                $status.text('Processed ' + completed + ' of ' + total + '. Converted: ' + converted + '. Failed: ' + failed + '. Remaining: ' + remaining + '.');
            }

            function runBatch() {
                request('RB_image_optimizer_batch').done(function (response) {
                    if (!response.success) {
                        finish(response.data && response.data.message ? response.data.message : 'Image optimization failed.');
                        return;
                    }

                    completed += Number(response.data.processed || 0);
                    converted += Number(response.data.converted || 0);
                    failed += Number(response.data.failed || 0);
                    if (response.data.messages && response.data.messages.length) {
                        response.data.messages.forEach(function (message) {
                            $errors.append($('<p>').text(message));
                        });
                    }
                    updateProgress(response.data.remaining || 0);

                    if (Number(response.data.remaining || 0) > 0) {
                        runBatch();
                    } else {
                        finish('Optimization completed. Converted: ' + converted + '. Failed: ' + failed + '.');
                    }
                }).fail(function () {
                    finish('The server interrupted image optimization. You can run it again to retry.');
                });
            }

            function finish(message) {
                $button.prop('disabled', false);
                $status.text(message);
            }

            $button.on('click', function () {
                $button.prop('disabled', true);
                $progress.show();
                $errors.empty();
                $bar.css('width', '0');
                total = completed = converted = failed = 0;
                $status.text('Scanning the Media Library...');

                request('RB_image_optimizer_start').done(function (response) {
                    if (!response.success || !response.data.webp_supported) {
                        finish(response.data && response.data.message ? response.data.message : 'WebP is not supported by the server.');
                        return;
                    }

                    total = Number(response.data.total || 0);
                    if (total === 0) {
                        updateProgress(0);
                        finish('No JPEG or PNG attachments need optimization.');
                        return;
                    }

                    runBatch();
                }).fail(function () {
                    finish('Could not scan the Media Library.');
                });
            });
        });
        </script>
    <?php endif;
}
