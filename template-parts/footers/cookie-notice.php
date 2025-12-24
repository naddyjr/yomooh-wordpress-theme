<?php
/** cookie notice template
 * @package Yomooh  
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
if (empty($options['enable_cookie_notice'])) return;
?>

<div class="cookie-notice" id="cookieNotice">
    <div class="cookie-notice-container">
        <div class="cookie-notice-text">
            <?php echo esc_html($options['cookie_notice_text']); ?>
            
            <?php if (!empty($options['cookie_policy_link'])) : ?>
                <a href="<?php echo esc_url($options['cookie_policy_link']); ?>" class="cookie-policy-link">
                    <?php esc_html_e('Learn more', 'yomooh'); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="cookie-buttons">
        <button class="cookie-accept-button">
            <?php echo esc_html($options['cookie_accept_button_text'] ?? __('Accept', 'yomooh')); ?>
        </button>
        <button class="cookie-decline-button">
            <?php echo esc_html($options['cookie_decline_button_text'] ?? __('Decline', 'yomooh')); ?>
    </div></div>
</div>

