<?php
class ControllerExtensionModuleGiftFinder extends Controller {
	public function index() {
		$this->load->language('extension/module/gift_finder');
		
		$this->document->setTitle($this->language->get('meta_title'));
		$this->document->setDescription($this->language->get('meta_description'));
		$this->document->setKeywords($this->language->get('meta_keyword'));
		
		$this->load->model('extension/module/gift_finder');
		$this->load->model('tool/image');
		
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/gift_finder', '', true)
		);
		
		// Get all filter groups and filters
		$filter_data = $this->model_extension_module_gift_finder->getFilterGroups();
		$data['filter_groups'] = $filter_data;
		
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
		
		if (isset($this->request->get['filter_id'])) {
			$filter_id = (int)$this->request->get['filter_id'];
		} else {
			$filter_id = 0;
		}
		
		$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		
		// Filter data for the query
		$filter_data = array(
			'filter_filter_id' => $filter_id,
			'sort'  => 'g.sort_order',
			'order' => 'ASC',
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		);
		
		$data['gifts'] = array();
		
		$gift_total = $this->model_extension_module_gift_finder->getTotalGifts($filter_data);
		$results = $this->model_extension_module_gift_finder->getGifts($filter_data);
		
		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			}
			
			$data['gifts'][] = array(
				'gift_id'  => $result['gift_id'],
				'name'     => $result['name'],
				'image'    => $image,
				'url'      => $result['url'],
				'status'   => $result['status']
			);
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_reset'] = $this->language->get('text_reset');
		$data['text_no_results'] = $this->language->get('text_no_results');
		
		$url = '';
		
		if ($filter_id) {
			$url .= '&filter_id=' . $filter_id;
		}
		
        // Pagination
        $pagination = new Pagination();
        $pagination->total = $gift_total;
        $pagination->page = $page;
        $pagination->limit = $limit;

        // Make sure route is always included for all pages, including page 1
        $pagination->url = $this->url->link('extension/module/gift_finder', $url . '&page={page}', true);

        // For the first page, create a special URL to ensure it has the route
        $data['first_page_url'] = $this->url->link('extension/module/gift_finder', $filter_id ? '&filter_id=' . $filter_id : '', true);

        $data['pagination'] = $pagination->render();
		
		$data['results'] = sprintf($this->language->get('text_pagination'), ($gift_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($gift_total - $limit)) ? $gift_total : ((($page - 1) * $limit) + $limit), $gift_total, ceil($gift_total / $limit));
		
		// Add pagination to the document for SEO
		if ($page == 1) {
			$this->document->addLink($this->url->link('extension/module/gift_finder', '', true), 'canonical');
		} else {
			$this->document->addLink($this->url->link('extension/module/gift_finder', $url, true), 'canonical');
		}
		
		if ($page > 1) {
			$this->document->addLink($this->url->link('extension/module/gift_finder', $url . (($page - 2) ? '&page='. ($page - 1) : ''), true), 'prev');
		}
		
		if ($limit && ceil($gift_total / $limit) > $page) {
			$this->document->addLink($this->url->link('extension/module/gift_finder', $url . '&page='. ($page + 1), true), 'next');
		}
		
		// Remember current filter_id for AJAX calls
		$data['current_filter_id'] = $filter_id;

		//Get SEO ulr if exists
		$data['pageUrl'] = "index.php?route=extension/module/gift_finder";

		$defaulRoute = "extension/module/gift_finder";
		$seoRoute = $this->getSeoUrl($defaulRoute);
		if($seoRoute){
            $data['pageUrl'] = $seoRoute;
		}
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('extension/module/gift_finder', $data));
	}
	

	public function filter() {
		$this->load->language('extension/module/gift_finder');
		$this->load->model('extension/module/gift_finder');
		$this->load->model('tool/image');
		
		$data = array();
		
		if (isset($this->request->post['filter_id'])) {
			$filter_id = (int)$this->request->post['filter_id'];
			
			if (isset($this->request->post['page'])) {
				$page = (int)$this->request->post['page'];
			} else {
				$page = 1;
			}
			
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
			
			$filter_data = array(
				'filter_filter_id'  => $filter_id,
				'sort'  => 'g.sort_order',
				'order' => 'ASC',
				'start' => ($page - 1) * $limit,
				'limit' => $limit
			);
			
			$results = $this->model_extension_module_gift_finder->getGifts($filter_data);
			$gift_total = $this->model_extension_module_gift_finder->getTotalGifts($filter_data);
			
			$data['gifts'] = array();
			
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}
				
				$data['gifts'][] = array(
					'gift_id'  => $result['gift_id'],
					'name'     => $result['name'],
					'image'    => $image,
					'url'      => $result['url'],
					'status'   => $result['status']
				);
			}
			
			// Pagination for AJAX
			$pagination = new Pagination();
			$pagination->total = $gift_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			
			$url = '';
			
			$pagination->url = $this->url->link('extension/module/gift_finder', $url . '&filter_id=' . $filter_id . '&page={page}', true);
			
			$data['pagination'] = $pagination->render();
			$data['results'] = sprintf($this->language->get('text_pagination'), ($gift_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($gift_total - $limit)) ? $gift_total : ((($page - 1) * $limit) + $limit), $gift_total, ceil($gift_total / $limit));
			$data['text_no_results'] = $this->language->get('text_no_results');
			
			// Add pagination info to determine if pagination should be shown
			$data['total_pages'] = ceil($gift_total / $limit);
			$data['current_page'] = $page;
			$data['filter_id'] = $filter_id;
			
			// Create separate HTML for content and pagination
			$product_html = $this->load->view('extension/module/gift_finder_items', $data);
			$pagination_html = $this->load->view('extension/module/gift_finder_pagination', $data);
			
			// Response array
			$json = array(
				'success' => true,
				'total' => $gift_total,
				'page' => $page,
				'limit' => $limit,
				'filter_id' => $filter_id,
				'product_html' => $product_html,
				'pagination_html' => $pagination_html,
				'results_text' => $data['results']
			);
			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		} else {
			$json = array(
				'success' => false,
				'error' => 'No filter ID provided'
			);
			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}
	}

	private function getSeoUrl($route){
	    $query = $this->db->query("SELECT keyword 
									FROM " . DB_PREFIX . "seo_url 
									WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' 
									AND query = '" . $this->db->escape($route) . "'");

		if ($query->num_rows) {
		    return $query->row['keyword'];
		}

		return null;
	}
}