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
 * @since 1.5
 */
class PDFCore
{
    public $filename;
    public $pdf_renderer;
    public $objects;
    public $template;
    public $send_bulk_flag = false;

    const TEMPLATE_INVOICE = 'Invoice';
    const TEMPLATE_ORDER_RETURN = 'OrderReturn';
    const TEMPLATE_ORDER_SLIP = 'OrderSlip';
    const TEMPLATE_DELIVERY_SLIP = 'DeliverySlip';
    const TEMPLATE_SUPPLY_ORDER_FORM = 'SupplyOrderForm';

    /**
     * @param $objects
     * @param $template
     * @param $smarty
     * @param string $orientation
     */
    public function __construct($objects, $template, $smarty, $orientation = 'P')
    {
        $this->pdf_renderer = new PDFGenerator((bool) Configuration::get('PS_PDF_USE_CACHE'), $orientation);
        $this->template = $template;

        /*
         * We need a Smarty instance that does NOT escape HTML.
         * Since in BO Smarty does not autoescape
         * and in FO Smarty does autoescape, we use
         * a new Smarty of which we're sure it does not escape
         * the HTML.
         */
        $this->smarty = clone $smarty;
        $this->smarty->escape_html = false;

        /* We need to get the old instance of the LazyRegister
         * because some of the functions are already defined
         * and we need to check in the old one first
         */
        $original_lazy_register = SmartyLazyRegister::getInstance($smarty);

        /* For PDF we restore some functions from Smarty
         * they've been removed in PrestaShop 1.7 so
         * new themes don't use them. Although PDF haven't been
         * reworked so every PDF controller must extend this class.
         */
        smartyRegisterFunction($this->smarty, 'function', 'convertPrice', array('Product', 'convertPrice'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'convertPriceWithCurrency', array('Product', 'convertPriceWithCurrency'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'displayWtPrice', array('Product', 'displayWtPrice'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'displayWtPriceWithCurrency', array('Product', 'displayWtPriceWithCurrency'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'displayPrice', array('Tools', 'displayPriceSmarty'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'modifier', 'convertAndFormatPrice', array('Product', 'convertAndFormatPrice'), true, $original_lazy_register); // used twice
        smartyRegisterFunction($this->smarty, 'function', 'displayAddressDetail', array('AddressFormat', 'generateAddressSmarty'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'getWidthSize', array('Image', 'getWidth'), true, $original_lazy_register);
        smartyRegisterFunction($this->smarty, 'function', 'getHeightSize', array('Image', 'getHeight'), true, $original_lazy_register);

        $this->objects = $objects;
        if (!($objects instanceof Iterator) && !is_array($objects)) {
            $this->objects = array($objects);
        }

        if (count($this->objects) > 1) { // when bulk mode only
            $this->send_bulk_flag = true;
        }
    }

    /**
     * Render PDF.
     *
     * @param bool $display
     *
     * @return mixed
     *
     * @throws PrestaShopException
     */
    public function render($display = true)
    {
        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        foreach ($this->objects as $object) {
            $this->pdf_renderer->startPageGroup();
            $template = $this->getTemplateObject($object);
            if (!$template) {
                continue;
            }

            if (empty($this->filename)) {
                $this->filename = $template->getFilename();
                if (count($this->objects) > 1) {
                    $this->filename = $template->getBulkFilename();
                }
            }

            $template->assignHookData($object);

            $this->pdf_renderer->createHeader($template->getHeader());
            $this->pdf_renderer->createFooter($template->getFooter());
            $this->pdf_renderer->createPagination($template->getPagination());
            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage();
            $render = true;

            unset($template);
        }

        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }

            return $this->pdf_renderer->render($this->filename, $display);
        }
    }

    /**
     * Get correct PDF template classes.
     *
     * @param mixed $object
     *
     * @return HTMLTemplate|false
     *
     * @throws PrestaShopException
     */
    public function getTemplateObject($object)
    {
        $class = false;
        $class_name = 'HTMLTemplate'.$this->template;

        if (class_exists($class_name)) {
            // Some HTMLTemplateXYZ implementations won't use the third param but this is not a problem (no warning in PHP),
            // the third param is then ignored if not added to the method signature.
            $class = new $class_name($object, $this->smarty, $this->send_bulk_flag);

            if (!($class instanceof HTMLTemplate)) {
                throw new PrestaShopException('Invalid class. It should be an instance of HTMLTemplate');
            }
        }

        return $class;
    }

    public function visualizarGuarda($object,$nombre)
    {
        $tienda_actual = new Shop((int)Context::getContext()->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;


        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        foreach ($this->objects as $object)
        {

            $template = $this->getTemplateObject($object);

            if (!$template)
                continue;

            if (empty($this->filename))
            {
                $this->filename = $template->getFilename();
                if (count($this->objects) > 1)
                    $this->filename = $template->getBulkFilename();
            }

            $template->assignHookData($object);
            $this->pdf_renderer->createFooter($template->getFooter());
            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage();


            if (!file_exists(_PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri)) {
                mkdir(_PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri, 0777, true);
            }
            $ruta = _PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri;


            $this->filename = $nombre;
            $display ='F';
            $this->pdf_renderer->render($ruta.$this->filename,$display);
//            d($ruta.$this->filename);
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="'.$ruta.$this->filename.'"');
            readfile($ruta.$this->filename);


        }
    }

    public function Guardar($nombre, $valor_qr='', $tipo_hoja = '', $valor_digest_Value = null){
        $render = true;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        $tienda_actual = new Shop((int)Context::getContext()->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;

        foreach ($this->objects as $object)
        {

            $template = $this->getTemplateObject($object);
            $this->pdf_renderer->formatFooter='yes';
//            d($object);
            $domain = _PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri;
            if (!file_exists($domain)) {
                mkdir($domain, 0777, true);
            }
            if ($tipo_hoja == 'ticket'){
//                D($tipo_hoja);
//                d($tipo_hoja);.

                $this->pdf_renderer->createContent($template->getContent());
                $this->pdf_renderer->writePage('comp'); // enviamos esta variable para poder cambiar el tamaño de la hoja

                if ($valor_qr != '') {
                    $tienda = Context::getContext()->shop;
                    $empleado = Context::getContext()->employee;
//                d($tienda);
                    $url_website = $tienda->domain.$tienda->physical_uri.$tienda->virtual_uri;
//d($url_website);
                    $style = array(
                        'border' => 2,
                        'vpadding' => 'auto',
                        'hpadding' => 'auto',
                        'fgcolor' => array(0,0,0),
                        'bgcolor' => false, //array(255,255,255)
                        'module_width' => 1, // width of a single module in points
                        'module_height' => 1 // height of a single module in points
                    );
                    // QRCODE,Q : QR-CODE Better error correction
                    $this->pdf_renderer->write2DBarcode((string)$valor_qr, 'QRCODE,Q', 25, ($this->pdf_renderer->GetY())-5, 30, 30, $style, 'RTL');
                    $this->pdf_renderer->writeHTMLCell(75, 10, 2, ($this->pdf_renderer->GetY()+28), '<table><tr><td colspan="6" style="font-size: 8px; border-bottom: 1px dashed black">Representación impresa de la '.strtoupper($object->tipo_documento_electronico).' ELECTRONICA puede ser consultado en www.sunat.gob.pe</td></tr></table>', 0, 2, false, true, 'J', true );
//                    $this->pdf_renderer->writeHTMLCell(75, 10, 2, ($this->pdf_renderer->GetY()+28), '<table><tr><td colspan="6" style="font-size: 8px; border-bottom: 1px dashed black">Representación impresa de la '.strtoupper($object->tipo_documento_electronico).' ELECTRONICA puede ser consultado en '.$url_website.'</td></tr><tr><td colspan="4" style="text-align: center; font-size: 7px;">USUARIO: '.$empleado->firstname.' '. $empleado->lastname.'</td><td colspan="2" style="text-align: center"><small>&nbsp;</small></td></tr></table>', 0, 2, false, true, 'J', true );

                }

                $ruta = _PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri;
                if (!file_exists($ruta)) {
                    mkdir($ruta, 0777, true);
                }


                $this->filename = $nombre;

                unset($template);
                if ($render)
                {
                    $display ='F';
                    // clean the output buffer
                    if (ob_get_level() && ob_get_length() > 0)
                        ob_clean();
//                    echo "<script>window.open('$domain$this->filename');</script>";
                    $this->pdf_renderer->render($ruta.$this->filename,$display);

                }
            }
            else if ($tipo_hoja == 'a4'){

                $this->pdf_renderer->createFooter($template->getFooter());
                $this->pdf_renderer->createContent($template->getContent());

                $this->pdf_renderer->writePage(); // enviamos esta variable para poder cambiar el tamaño de la hoja
                if ($valor_qr != '') {
                    $tienda = Context::getContext()->shop;
                    $empleado = Context::getContext()->employee;

                    $url_website = $tienda->domain.$tienda->physical_uri.$tienda->virtual_uri;
//d($url_website);
                    $style = array(
                        'border' => 2,
                        'vpadding' => 'auto',
                        'hpadding' => 'auto',
                        'fgcolor' => array(0,0,0),
                        'bgcolor' => false, //array(255,255,255)
                        'module_width' => 1, // width of a single module in points
                        'module_height' => 1 // height of a single module in points
                    );


                    if ($object->tipo_documento_electronico == 'NotaCredito') {
                        // QRCODE,Q : QR-CODE Better error correction
                        $this->pdf_renderer->write2DBarcode((string)$valor_qr, 'QRCODE,Q', 160, ($this->pdf_renderer->GetY() - 10), 30, 30, $style, 'RTL');
//                            $this->pdf_renderer->writeHTMLCell(100, 10, 55, ($this->pdf_renderer->GetY()), '<table><tr><td colspan="6" style="font-size: 8px;">Representación impresa de la NOTA DE CREDITO ELECTRONICA puede ser consultado en ' . $url_website . '</td></tr></table>', 0, 2, false, true, 'J', true);
                        $this->pdf_renderer->writeHTMLCell(100, 10, 80, ($this->pdf_renderer->GetY() + 18), '<table><tr><td colspan="6" style="font-size: 8px;">¡GRACIAS POR SU PREFERENCIA!</td></tr></table>', 0, 2, false, true, 'J', true);
                    }
                    else{
                        // QRCODE,Q : QR-CODE Better error correction
                        $this->pdf_renderer->write2DBarcode((string)$valor_qr, 'QRCODE,Q', 25, ($this->pdf_renderer->GetY()), 30, 30, $style, 'RTL');
                        $this->pdf_renderer->writeHTMLCell(100, 10, 55, ($this->pdf_renderer->GetY()), '<table><tr><td colspan="6" style="font-size: 8px;">Representación impresa de la ' . strtoupper($object->tipo_documento_electronico) . ' ELECTRONICA puede ser consultado en www.sunat.gob.pe</td></tr></table>', 0, 2, false, true, 'J', true);
                        $this->pdf_renderer->writeHTMLCell(100, 10, 80, ($this->pdf_renderer->GetY() + 38), '<table><tr><td colspan="6" style="font-size: 8px;">¡GRACIAS POR SU PREFERENCIA!</td></tr></table>', 0, 2, false, true, 'J', true);
                    }

                }

                $ruta =  _PS_ADMIN_DIR_.'/documentos_pdf_a4/'.$nombre_virtual_uri;
                $ruta_nota =  _PS_ADMIN_DIR_.'/documentos_pdf_a4/notas/'.$nombre_virtual_uri;

                if (!file_exists($ruta)) {
                    mkdir($ruta, 0777, true);
                }

                if (!file_exists($ruta_nota)) {
                    mkdir($ruta_nota, 0777, true);
                }


                $this->filename = $nombre;

                if ($object->tipo_documento_electronico == 'NotaCredito') {

                    unset($template);
                    if ($render) {
                        $display = 'F';
                        // clean the output buffer
                        if (ob_get_level() && ob_get_length() > 0)
                            ob_clean();
                        $this->pdf_renderer->render($ruta_nota . $this->filename, $display);

                    }
                }

                else{

                    unset($template);
                    if ($render) {
                        $display = 'F';
                        // clean the output buffer
                        if (ob_get_level() && ob_get_length() > 0)
                            ob_clean();

//                            echo "<script>window.open('$domain$this->filename');</script>";

                        $this->pdf_renderer->render($ruta . $this->filename, $display);

                    }
                }
            }
            else{

                $template->assignHookData($object);
                $this->pdf_renderer->createFooter($template->getFooter());
                $this->pdf_renderer->createContent($template->getContent());
                $this->pdf_renderer->writePage();

                $this->filename = $nombre;

                unset($template);
                if ($render) {
                    $display = 'F';
                    // clean the output buffer
                    if (ob_get_level() && ob_get_length() > 0)
                        ob_clean();

                    $this->pdf_renderer->render(_PS_ADMIN_DIR_.'/'.$this->filename, $display);
                }
            }

        }
    }

    public function GuardarTicketWeb($nombre, $valor_qr='', $tipo_hoja = '', $valor_digest_Value = null){
        $render = true;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        $tienda_actual = new Shop((int)Context::getContext()->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;

        foreach ($this->objects as $object)
        {

            $template = $this->getTemplateObject($object);
            $this->pdf_renderer->formatFooter='yes';
//            d($object);
            $domain = _PS_ADMIN_DIR_.'/documentos_pdf/'.$nombre_virtual_uri;
            if (!file_exists($domain)) {
                mkdir($domain, 0777, true);
            }

            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage('comp'); // enviamos esta variable para poder cambiar el tamaño de la hoja

            $this->filename = $nombre;

            unset($template);
            if ($render)
            {
                $display ='F';
                // clean the output buffer
                if (ob_get_level() && ob_get_length() > 0)
                    ob_clean();
//                    echo "<script>window.open('$domain$this->filename');</script>";
                $this->pdf_renderer->render($domain.$this->filename,$display);

            }


        }
    }

    public function renderTicketWeb($display = true)
    {
        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        foreach ($this->objects as $object) {
            $this->pdf_renderer->startPageGroup();
            $template = $this->getTemplateObject($object);
            if (!$template) {
                continue;
            }

            if (empty($this->filename)) {
                $this->filename = $template->getFilename();
                if (count($this->objects) > 1) {
                    $this->filename = $template->getBulkFilename();
                }
            }

            $template->assignHookData($object);

            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage('comp'); // enviamos esta variable para poder cambiar el tamaño de la hoja
            $render = true;

            unset($template);
        }

        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }

            return $this->pdf_renderer->render($this->filename, $display);
        }
    }
}
