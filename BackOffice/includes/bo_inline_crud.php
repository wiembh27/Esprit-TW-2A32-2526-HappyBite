<?php

declare(strict_types=1);

function bo_inline_crud_styles(): void
{
    ?>
    <style>
        .bo-inline-crud {
            margin-bottom: 22px;
            border: 2px solid #c8e6c9;
            border-radius: 14px;
            background: #fafffb;
            overflow: hidden;
            scroll-margin-top: 16px;
        }
        .bo-inline-crud__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 18px;
            background: #e8f5e9;
            border-bottom: 1px solid #c8e6c9;
        }
        .bo-inline-crud__head h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f3a28;
        }
        .bo-inline-crud__body {
            padding: 18px;
        }
        .bo-inline-crud__body .list-produit-head {
            display: none;
        }
        .bo-inline-crud__body .bo-catalog-inline-form > .liste-com-liv-stack > .liste-com-liv-topbar {
            display: none;
        }
    </style>
    <?php
}

/**
 * @param 'add'|'edit' $mode
 */
function bo_inline_crud_list_url(string $listPage, string $mode = '', int $id = 0): string
{
    $url = $listPage;
    $params = [];
    if (isset($_GET['embed']) && (string) $_GET['embed'] !== '' && (string) $_GET['embed'] !== '0') {
        $params['embed'] = '1';
    }
    if ($mode !== '') {
        $params['action'] = $mode;
    }
    if ($id > 0) {
        $params['id'] = (string) $id;
    }
    if ($params !== []) {
        $url .= '?' . http_build_query($params);
    }

    return $url . '#bo-inline-crud';
}

function bo_catalog_save_redirect(string $listPage): void
{
    $url = $listPage;
    $params = ['saved' => '1'];
    if (isset($_GET['embed']) && (string) $_GET['embed'] !== '' && (string) $_GET['embed'] !== '0') {
        $params['embed'] = '1';
    }
    header('Location: ' . $url . '?' . http_build_query($params) . '#bo-inline-crud');
    exit;
}
