<?php

declare(strict_types=1);

/**
 * Demo / ESPRIT test-account helpers (@happybite.tn).
 */

function fo_is_demo_email(?string $email): bool
{
    $email = strtolower(trim((string) $email));

    return $email !== '' && str_ends_with($email, '@happybite.tn');
}

/** @return list<array{role: string, email: string, page: string}> */
function fo_demo_guide_accounts(): array
{
    return [
        [
            'role' => 'auth.demo_role_admin',
            'email' => 'admin@happybite.tn',
            'page' => 'auth.demo_page_admin',
        ],
        [
            'role' => 'auth.demo_role_fournisseur',
            'email' => 'fournisseur@happybite.tn',
            'page' => 'auth.demo_page_fournisseur',
        ],
        [
            'role' => 'auth.demo_role_nutritionniste',
            'email' => 'nutritionniste@happybite.tn',
            'page' => 'auth.demo_page_nutritionniste',
        ],
    ];
}

function fo_render_auth_demo_guide(): void
{
    if (!function_exists('fo_t')) {
        return;
    }

    $accounts = fo_demo_guide_accounts();
    ?>
    <details class="auth-demo-guide">
        <summary class="auth-demo-guide__title"><?php echo fo_e('auth.demo_guide_title'); ?></summary>
        <p class="auth-demo-guide__intro"><?php echo fo_e('auth.demo_guide_intro'); ?></p>
        <ul class="auth-demo-guide__list">
            <?php foreach ($accounts as $row): ?>
                <li>
                    <strong><?php echo fo_e($row['role']); ?></strong>
                    — <code><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></code>
                    <span class="auth-demo-guide__page"><?php echo fo_e($row['page']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="auth-demo-guide__pwd"><?php echo fo_e('auth.demo_password_note'); ?></p>
        <p class="auth-demo-guide__warn"><?php echo fo_e('auth.demo_password_change_warning'); ?></p>
    </details>
    <?php
}

function fo_render_demo_session_notice(): void
{
    if (!function_exists('fo_t')) {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return;
    }

    $email = (string) ($_SESSION['user_email'] ?? '');
    if (!fo_is_demo_email($email)) {
        return;
    }

    $prefix = hb_brand_asset_prefix_from_request();
    $profileHref = $prefix . 'Profile_Utilisateur.php';
    ?>
    <div class="hb-demo-session-notice" role="status">
        <?php echo fo_e('auth.demo_session_notice'); ?>
        <a href="<?php echo htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo fo_e('auth.demo_session_profile_link'); ?></a>
    </div>
    <?php
}
