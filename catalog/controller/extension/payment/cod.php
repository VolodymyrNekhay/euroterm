<?php
class ControllerExtensionPaymentCod extends Controller {
	public function index() {
		//8-custom-code		
		$this->load->language('extension/payment/cod');
       
		return $this->load->view('extension/payment/cod');
	}

	public function confirm() {
		$json = array();
		
		if ($this->session->data['payment_method']['code'] == 'cod') {
			$this->load->model('checkout/order');

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cod_order_status_id'));
		
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link('checkout/success'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
}
