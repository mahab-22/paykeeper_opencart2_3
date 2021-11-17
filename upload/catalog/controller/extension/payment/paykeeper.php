<?php
/*version 11121*/
require_once('paykeeper_common_class/paykeeper.class.php');

class ControllerExtensionPaymentPaykeeper extends Controller {

    private $fiscal_cart = array(); //fz54 cart
    private $order_total = 0; //order total sum
    private $shipping_price = 0; //shipping price
    private $use_delivery = false; //is delivery using or not
    private $order_params = NULL; //order parameters

	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['current_host'] = $_SERVER['HTTP_HOST'];

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$payment_parameters = http_build_query(array(
			"orderid"=>$this->session->data['order_id'],
			"clientid"=>$order_info['email'],
			"sum"=>$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
			"phone"=>$order_info['telephone']
		));

		$data['server'] = $this->config->get('paykeeperserver');
		$data['payment_parameters'] = $payment_parameters;
		
        return $this->load->view('/extension/payment/paykeeper', $data);
	}
	public function callback() {
        
        //file_put_contents("request.log", print_r($POST,true),FILE_APPEND);
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('checkout/order');

			
			if(isset($this->request->post['id']) && isset($this->request->post['orderid'])){
				$secret_seed = $this->config->get('paykeepersecret');
				$id = $this->request->post['id'];
				$sum = $this->request->post['sum'];
				$clientid = $this->request->post['clientid'];
				$orderid = $this->request->post['orderid'];
				$key = $this->request->post['key'];

				if ($key != md5 ($id . sprintf ("%.2lf", $sum).$clientid.$orderid.$secret_seed))
				{
					echo "Error! Hash mismatch";
					exit;
				}
				
				$order_info = $this->model_checkout_order->getOrder($orderid);
				
				if ($orderid == "")
				{
					
				}
				else
				{
					$this->model_checkout_order->addOrderHistory($orderid, $this->config->get('paykeeper_order_status_id'));
				}
				echo "OK ".md5($id.$secret_seed);
			}
			else echo "OK";
		}
	}
	
	public function gopay() {

		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('paykeeper_title'),
			'href' => $this->url->link('extension/payment/paykeeper/gopay', '', 'SSL')
		);

        $data['heading_title'] = $this->language->get('paykeeper_title');
		$this->document->setTitle($data['heading_title']);
		
		$this->load->model('checkout/order');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');


        //generate payment form
        $data["form"] = $this->generatePaymentForm($this->session->data['order_id']);

        $this->response->setOutput($this->load->view('extension/payment/paykeeper_iframe', $data));

	}
	public function success() {
		
		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');

        //clear cart
        $this->cart->clear();
		
		$data['heading_title'] = $this->language->get('paykeeper_title');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['message'] = $this->language->get('paykeeper_success');

		$this->document->setTitle($data['message']);
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
        $this->response->setOutput($this->load->view('extension/payment/paykeeper_feedback', $data));
	}
	public function failed() {
		
		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');
		
		$data['heading_title'] = $this->language->get('paykeeper_title');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['message'] = $this->language->get('paykeeper_failed');

		$this->document->setTitle($data['message']);
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
        $this->response->setOutput($this->load->view('extension/payment/paykeeper_feedback', $data));
	}

    public function confirm() {
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);
    }


    protected function recalculateTaxes($item_pos)
    {
        //recalculate taxes
        switch($this->fiscal_cart[$item_pos]['tax']) {
            case "vat10":
                $this->fiscal_cart[$item_pos]['tax_sum'] = round((float)
                    (($this->fiscal_cart[$item_pos]['sum']/110)*10), 2);
                break;
            case "vat18":
                $this->fiscal_cart[$item_pos]['tax_sum'] = round((float)
                    (($this->fiscal_cart[$item_pos]['sum']/118)*18), 2);
                break;
        }
    }

    protected function getRate($sum, $tax_class_id)
    {
        $tax_data = Null;
        foreach($this->tax->getRates($sum, $tax_class_id) as $td) {
            $tax_data = $td;
        }
        return ($tax_data) ? (int)$tax_data['rate'] : 0;

    }

    //add tax sum to item price
    protected function correctCartItemPrice($price, $tax_rate)
    {
        return ($tax_rate != 0) ? $price+($price/100*$tax_rate) : $price;
    }

    protected function showDebugInfo($obj_to_debug)
    {
        echo "<pre>";
        var_dump($obj_to_debug);
        echo "</pre>";
    }

    public function generatePaymentForm($order_id)
    {
        $this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($order_id);

        //GENERATING PAYKEEPER PAYMENT FORM
        $pk_obj = new PaykeeperPayment();

        //set order params
        $pk_obj->setOrderParams(
            //sum
            $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
            //clientid
            $order_info['firstname'] . " " . $order_info['lastname'],
            //orderid
            $order_info['order_id'],
            //client_email
            $order_info['email'],
            //client_phone
            $order_info['telephone'],
            //service_name
            '',
            //payment form url
            $this->config->get('paykeeperserver'),
            //secret key
            $this->config->get('paykeepersecret')
        );

        //GENERATE FZ54 CART

        $cart_data = $this->cart->getProducts();
        $item_index = 0;
        foreach ($cart_data as $item) {
            $tax_rate = 0;
            $taxes = array("tax" => "none", "tax_sum" => 0);
            $name = $item["name"];
            //vat included in price
            $tax_amount =0;
            if ( (int) $item["tax_class_id"] != 0) {
                $tax_rate = $this->getRate($item['price'], $item["tax_class_id"]);
                $tax_amount = $item['price']*($tax_rate/100);
            }

            $price = floatval($item['price']+$tax_amount);

            $quantity = floatval($item['quantity']);
            if ($quantity == 1 && $pk_obj->single_item_index < 0)
                $pk_obj->single_item_index = $item_index;
            if ($quantity > 1 && $pk_obj->more_then_one_item_index < 0)
                $pk_obj->more_then_one_item_index = $item_index;
            $sum = $price*$quantity;

            $taxes = $pk_obj->setTaxes($tax_rate);
            $pk_obj->updateFiscalCart($pk_obj->getPaymentFormType(),
                        $name, $price, $quantity, $sum, $taxes["tax"]);
                        $item_index++;                        
        }

        //add shipping parameters to cart
        if (array_key_exists('shipping_method', $this->session->data)) {
            $shipping_taxes = array("tax" => "none", "tax_sum" => 0);
            $shipping_tax_rate = 0;
            $shipping_tax_rate = $this->getRate($pk_obj->getShippingPrice(),
                                                $this->session->data["shipping_method"]["tax_class_id"]
            );

            $shipping_tax_amount = $this->session->data['shipping_method']['cost']*($shipping_tax_rate/100);
            $pk_obj->setShippingPrice(floatval($this->session->data['shipping_method']['cost']+$shipping_tax_amount));
            $shipping_name = $this->session->data['shipping_method']['title'];
            $shipping_taxes = $pk_obj->setTaxes($shipping_tax_rate,true);
            if (!$pk_obj->checkDeliveryIncluded($pk_obj->getShippingPrice(), $shipping_name)
                && $pk_obj->getShippingPrice() > 0) {
                $pk_obj->setUseDelivery(); //for precision correct check
                $pk_obj->updateFiscalCart($pk_obj->getPaymentFormType(), $shipping_name,
                            $pk_obj->getShippingPrice(), 1, $pk_obj->getShippingPrice(), $shipping_taxes["tax"]);
                $pk_obj->delivery_index = count($pk_obj->getFiscalCart())-1;
            }
        }
        //set discounts
        $pk_obj->setDiscounts(array_key_exists("coupon", $this->session->data));
// echo'<pre>';
// print_r($this->config);
// die();
        //handle possible precision problem
        $pk_obj->correctPrecision();

        $fiscal_cart_encoded = json_encode($pk_obj->getFiscalCart());
        //generate payment form
        $form = "";
        if ($pk_obj->getPaymentFormType() == "create") { //create form
            $to_hash = number_format($pk_obj->getOrderTotal(), 2, ".", "") .
                       $pk_obj->getOrderParams("clientid")     .
                       $pk_obj->getOrderParams("orderid")      .
                       $pk_obj->getOrderParams("service_name") .
                       $pk_obj->getOrderParams("client_email") .
                       $pk_obj->getOrderParams("client_phone") .
                       $pk_obj->getOrderParams("secret_key");
            $sign = hash ('sha256' , $to_hash);

            $form = '
                <h3>Сейчас Вы будете перенаправлены на страницу банка.</h3> 
                <form name="payment" id="pay_form" action="'.$pk_obj->getOrderParams("form_url").'" accept-charset="utf-8" method="post">
                <input type="hidden" name="sum" value = "'.$pk_obj->getOrderTotal().'"/>
                <input type="hidden" name="orderid" value = "'.$pk_obj->getOrderParams("orderid").'"/>
                <input type="hidden" name="clientid" value = "'.$pk_obj->getOrderParams("clientid").'"/>
                <input type="hidden" name="client_email" value = "'.$pk_obj->getOrderParams("client_email").'"/>
                <input type="hidden" name="client_phone" value = "'.$pk_obj->getOrderParams("client_phone").'"/>
                <input type="hidden" name="service_name" value = "'.$pk_obj->getOrderParams("service_name").'"/>
                <input type="hidden" name="cart" value = \''.htmlentities($fiscal_cart_encoded,ENT_QUOTES).'\' />
                <input type="hidden" name="sign" value = "'.$sign.'"/>
                <input type="submit" id="button-confirm" value="Оплатить"/>
                </form>
                <script text="javascript">
                    
                window.onload=function() {
                    setTimeout(sendForm, 2000);
                }   
                function sendForm() {
                    $.ajax({ 
                        type: "get",
                        url: "index.php?route=extension/payment/paykeeper/confirm",
                        success: function() {
                            $("#pay_form").submit();
                        }       
                    });
                }
                $("#button-confirm").bind("click", sendForm());
                </script>';

        }
        else { //order form
            $payment_parameters = array(
                "clientid"=>$pk_obj->getOrderParams("clientid"), 
                "orderid"=>$pk_obj->getOrderParams('orderid'), 
                "sum"=>$pk_obj->getOrderTotal(), 
                "phone"=>$pk_obj->getOrderParams("phone"), 
                "client_email"=>$pk_obj->getOrderParams("client_email"), 
                "cart"=>$fiscal_cart_encoded);
            $query = http_build_query($payment_parameters);
            $query_options = array("http"=>array(
                "method"=>"POST",
                "header"=>"Content-type: application/x-www-form-urlencoded",
                "content"=>$query
                ));
            $context = stream_context_create($query_options);

            $err_num = $err_text = NULL;
            if( function_exists( "curl_init" )) { //using curl
                $CR = curl_init();
                curl_setopt($CR, CURLOPT_URL, $pk_obj->getOrderParams("form_url"));
                curl_setopt($CR, CURLOPT_POST, 1);
                curl_setopt($CR, CURLOPT_FAILONERROR, true); 
                curl_setopt($CR, CURLOPT_POSTFIELDS, $query);
                curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
                $result = curl_exec( $CR );
                $error = curl_error( $CR );
                if( !empty( $error )) {
                    $form = "<br/><span class=message>"."INTERNAL ERROR:".$error."</span>";
                    return false;
                }
                else {
                    $form = $result;
                }
                curl_close($CR);
            }
            else { //using file_get_contents
                if (!ini_get('allow_url_fopen')) {
                    $form_html = "<br/><span class=message>"."INTERNAL ERROR: Option allow_url_fopen is not set in php.ini"."</span>";
                }
                else {

                    $form = file_get_contents($pk_obj->getOrderParams("form_url"), false, $context);
                }
            }
        }
        if ($form  == "") {
            $form = '<h3>Произошла ошибка при инциализации платежа</h3><p>$err_num: '.htmlspecialchars($err_text).'</p>';
        }

        return $form;
    }
}


