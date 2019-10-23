{**
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
 *}

<style>
    .table-responsive-row{
        overflow-x: auto!important;
    }
</style>
{$header}
{$content}
{$footer}
<script>
    $(function() {
        $('.table-responsive-row').on('shown.bs.dropdown', function(e) {
            var t = $(this),
                m = $(e.target).find('.dropdown-menu'),
                tb = t.offset().top + t.height(),
                mb = m.offset().top + m.outerHeight(true),
                d = 20; // Space for shadow + scrollbar.
            // if (t[0].scrollWidth > t.innerWidth()) {
            if (mb + d > tb) {
                t.css('padding-bottom', ((mb + d) - tb));
            }
            // } else {
            //     t.css('overflow', 'visible');
            // }
        }).on('hidden.bs.dropdown', function() {
            $(this).css({
                'padding-bottom': '',
                'overflow': ''
            });
        });
    });
</script>