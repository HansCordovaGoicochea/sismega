<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include(_PS_ADMIN_DIR_.'/../config/config.inc.php');
/* Getting cookie or logout */
require_once(_PS_ADMIN_DIR_.'/init.php');

$query = Tools::getValue('q', false);
if (!$query || $query == '' || strlen($query) < 1) {
    die();
}

/*
 * In the SQL request the "q" param is used entirely to match result in database.
 * In this way if string:"(ref : #ref_pattern#)" is displayed on the return list,
 * they are no return values just because string:"(ref : #ref_pattern#)"
 * is not write in the name field of the product.
 * So the ref pattern will be cut for the search request.
 */
if ($pos = strpos($query, ' (ref:')) {
    $query = substr($query, 0, $pos);
}

$excludeIds = Tools::getValue('excludeIds', false);
if ($excludeIds && $excludeIds != 'NaN') {
    $excludeIds = implode(',', array_map('intval', explode(',', $excludeIds)));
} else {
    $excludeIds = '';
}

// Excluding downloadable products from packs because download from pack is not supported
$exclude_packs = (bool)Tools::getValue('exclude_packs', true);
$context = Context::getContext();

$sql = 'SELECT p.id_product as id,
       pl.name      as text,
       p.`id_product`,
       pl.`name`,
       p.`ean13`,
       p.`isbn`,
       p.`upc`,
       p.`active`,
       p.`reference`,
       stock.`quantity`,
       p.is_virtual
		FROM `'._DB_PREFIX_.'product` p
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.id_product = p.id_product AND pl.id_lang = '.(int)$context->language->id.Shop::addSqlRestrictionOnLang('pl').')
		'.Product::sqlStock('p', 0).'
		WHERE product_shop.active = 1 AND (pl.name LIKE \'%'.pSQL($query).'%\' OR p.reference LIKE \'%'.pSQL($query).'%\')'.
        (!empty($excludeIds) ? ' AND p.id_product NOT IN ('.$excludeIds.') ' : ' ').
        ($exclude_packs ? 'AND (p.cache_is_pack IS NULL OR p.cache_is_pack = 0)' : '').
        ' GROUP BY p.id_product';

$items = Db::getInstance()->executeS($sql);

if ($items) {

    $results_array = array();
    foreach ($items as $row) {

        $row['price_tax_incl'] = Product::getPriceStatic($row['id_product'], true, null, 2);
        $row['price_tax_excl'] = Product::getPriceStatic($row['id_product'], false, null, 2);
        $row['formatted_price'] = Tools::displayPrice(Tools::convertPrice($row['price_tax_incl'], Context::getContext()->currency), Context::getContext()->currency);
        $results_array[] = $row;
    }

    $to_return = array(
        'products' => $results_array,
        'found' => true
    );
    echo json_encode($to_return);

}
else {
    echo json_encode([]);
}
