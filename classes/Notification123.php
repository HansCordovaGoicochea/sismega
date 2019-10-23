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

/**
 * Class NotificationCore
 */
class NotificationCore
{
    public $types;

    /**
     * NotificationCore constructor.
     */
    public function __construct()
    {
        $this->types = array('citas');
    }

    /**
     * getLastElements return all the notifications (new order, new customer registration, and new customer message)
     * Get all the notifications
     *
     * @return array containing the notifications
     */
    public function getLastElements()
    {
        $notifications = array();
        $employeeInfos = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
		SELECT id_last_order, id_last_customer_message, id_last_customer
		FROM `'._DB_PREFIX_.'employee`
		WHERE `id_employee` = '.(int)Context::getContext()->employee->id);

        foreach ($this->types as $type) {
            $notifications[$type] = Notification::getLastElementsIdsByType($type);
        }

        return $notifications;
    }

    /**
     * getLastElementsIdsByType return all the element ids to show (order, customer registration, and customer message)
     * Get all the element ids
     *
     * @param string $type          contains the field name of the Employee table
     * @param int    $idLastElement contains the id of the last seen element
     *
     * @return array containing the notifications
     */
    public static function getLastElementsIdsByType($type)
    {
        global $cookie;

        switch ($type) {
            case 'citas':
                $sql = '
					SELECT SQL_CALC_FOUND_ROWS rc.`id_reservar_cita`, rc.`id_colaborador`, rc.`id_customer`, rc.`product_name`, CONCAT_WS(" ", e.firstname, e.lastname) as colaborador, c.firstname as cliente, fecha_inicio as fecha, hora
					FROM `'._DB_PREFIX_.'reservar_cita` as rc
					LEFT JOIN `'._DB_PREFIX_.'customer` as c ON (c.`id_customer` = rc.`id_customer`)
					LEFT JOIN `'._DB_PREFIX_.'employee` as e ON (e.`id_employee` = rc.`id_colaborador`)
					WHERE `estado_actual` = 0 AND  DATE_SUB(CURDATE(), INTERVAL -1 DAY) >= DATE(fecha_inicio) AND CURDATE() <= DATE(fecha_inicio)'.
                    Shop::addSqlRestriction(false, 'rc').'
					ORDER BY `fecha_inicio` ASC
					LIMIT 10';
                break;
        }

//        echo $sql;
//        d($sql);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $total = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()', false);
        $json = array('total' => $total, 'results' => array());
        foreach ($result as $value) {
            $customerName = $value['cliente'];

            $json['results'][] = array(
                'id_reservar_cita' => ((!empty($value['id_reservar_cita'])) ? (int) $value['id_reservar_cita'] : 0),
                'id_customer' => ((!empty($value['id_customer'])) ? (int) $value['id_customer'] : 0),
                'colaborador' => ((!empty($value['colaborador'])) ? Tools::displayDate($value['colaborador']) : 0),
                'fecha' => isset($value['fecha']) ? Tools::displayDate($value['fecha']) : 0,
                'hora' => ((!empty($value['hora'])) ? Tools::safeOutput($value['hora']) : ''),
                'product_name' => ((!empty($value['product_name'])) ? Tools::safeOutput($value['product_name']) : ''),
                'customer_name' => $customerName,
            );
        }

        return $json;
    }

    /**
     * updateEmployeeLastElement return 0 if the field doesn't exists in Employee table.
     * Updates the last seen element by the employee
     *
     * @param string $type contains the field name of the Employee table
     * @return bool if type exists or not
     */
    public function updateEmployeeLastElement($type)
    {
        if (in_array($type, $this->types)) {
            // We update the last item viewed
            return Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'employee`
			SET `id_last_'.bqSQL($type).'` = (
				SELECT IFNULL(MAX(`id_'.bqSQL($type).'`), 0)
				FROM `'._DB_PREFIX_.(($type == 'order') ? bqSQL($type).'s' : bqSQL($type)).'`
			)
			WHERE `id_employee` = '.(int)Context::getContext()->employee->id);
        }

        return false;
    }
}
