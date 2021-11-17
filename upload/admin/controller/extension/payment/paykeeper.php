<?php
class ControllerExtensionPaymentPaykeeper extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/paykeeper');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('paykeeper', $this->request->post);//сохранение в БД

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_pay'] = $this->language->get('text_pay');
		$data['text_card'] = $this->language->get('text_card');

		$data['entry_paykeeperserver'] = $this->language->get('entry_paykeeperserver');
		$data['entry_paykeepersecret'] = $this->language->get('entry_paykeepersecret');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_callback'] = $this->language->get('entry_callback');
		$data['entry_success_link'] = $this->language->get('entry_success_link');
		$data['entry_failed_link'] = $this->language->get('entry_failed_link');

		$data['help_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');


		// ********************** Принудительный учет скидок ***************************************************************//
		$data['entry_force_discounts_check'] = $this->language->get('entry_force_discounts_check');
		$data['entry_force_discounts_check_description'] = $this->language->get('entry_force_discounts_check_description');
		if (isset($this->request->post['paykeeper_force_discounts_check'])) {
			$data['paykeeper_force_discounts_check'] = $this->request->post['paykeeper_force_discounts_check'];
		} else {
			$data['paykeeper_force_discounts_check'] = $this->config->get('paykeeper_force_discounts_check');
		}
		//*******************************************************************************************************************//
		

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['paykeeperserver'])) {
			$data['error_paykeeperserver'] = $this->error['paykeeperserver'];
		} else {
			$data['error_paykeeperserver'] = '';
		}

		if (isset($this->error['paykeepersecret'])) {
			$data['error_paykeepersecret'] = $this->error['paykeepersecret'];
		} else {
			$data['error_paykeepersecret'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'type=payment', 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/paykeeper', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('extension/payment/paykeeper', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', 'SSL');

		if (isset($this->request->post['paykeeperserver'])) {
			$data['paykeeperserver'] = $this->request->post['paykeeperserver'];
		} else {
			$data['paykeeperserver'] = $this->config->get('paykeeperserver');
		}

		if (isset($this->request->post['paykeepersecret'])) {
			$data['paykeepersecret'] = $this->request->post['paykeepersecret'];
		} else {
			$data['paykeepersecret'] = $this->config->get('paykeepersecret');
		}

		if (isset($this->request->post['paykeeper_order_status_id'])) {
			$data['paykeeper_order_status_id'] = $this->request->post['paykeeper_order_status_id'];
		} else {
			$data['paykeeper_order_status_id'] = $this->config->get('paykeeper_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['paykeeper_geo_zone_id'])) {
			$data['paykeeper_geo_zone_id'] = $this->request->post['paykeeper_geo_zone_id'];
		} else {
			$data['paykeeper_geo_zone_id'] = $this->config->get('paykeeper_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['paykeeper_status'])) {
			$data['paykeeper_status'] = $this->request->post['paykeeper_status'];
		} else {
			$data['paykeeper_status'] = $this->config->get('paykeeper_status');
		}

		if (isset($this->request->post['paykeeper_sort_order'])) {
			$data['paykeeper_sort_order'] = $this->request->post['paykeeper_sort_order'];
		} else {
			$data['paykeeper_sort_order'] = $this->config->get('paykeeper_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/paykeeper.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paykeeper')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['paykeeperserver']) {
			$this->error['paykeeperserver'] = $this->language->get('error_paykeeperserver');
		}

		if (!$this->request->post['paykeepersecret']) {
			$this->error['paykeepersecret'] = $this->language->get('error_paykeepersecret');
		}

		return !$this->error;
	}
}


