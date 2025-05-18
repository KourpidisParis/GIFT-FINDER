<?php
class ModelCatalogGift extends Model {
	public function addGift($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "gift SET code = '" . $this->db->escape($data['code']) . "', image = '" . $this->db->escape($data['image']) . "', status = '" . (int)$data['status'] . "', url = '" . $this->db->escape($data['url']) . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

		$gift_id = $this->db->getLastId();

		foreach ($data['gift_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "gift_description SET gift_id = '" . (int)$gift_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['gift_filter'])) {
			foreach ($data['gift_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "gift_filter SET gift_id = '" . (int)$gift_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		return $gift_id;
	}

	public function editGift($gift_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "gift SET code = '" . $this->db->escape($data['code']) . "', image = '" . $this->db->escape($data['image']) . "', status = '" . (int)$data['status'] . "', url = '" . $this->db->escape($data['url']) . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE gift_id = '" . (int)$gift_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "gift_description WHERE gift_id = '" . (int)$gift_id . "'");

		foreach ($data['gift_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "gift_description SET gift_id = '" . (int)$gift_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "gift_filter WHERE gift_id = '" . (int)$gift_id . "'");

		if (isset($data['gift_filter'])) {
			foreach ($data['gift_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "gift_filter SET gift_id = '" . (int)$gift_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}
	}

	public function deleteGift($gift_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "gift WHERE gift_id = '" . (int)$gift_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "gift_description WHERE gift_id = '" . (int)$gift_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "gift_filter WHERE gift_id = '" . (int)$gift_id . "'");
	}

	public function getGift($gift_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "gift WHERE gift_id = '" . (int)$gift_id . "'");

		return $query->row;
	}

	public function getGiftDescriptions($gift_id) {
		$gift_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "gift_description WHERE gift_id = '" . (int)$gift_id . "'");

		foreach ($query->rows as $result) {
			$gift_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword']
			);
		}

		return $gift_description_data;
	}

	public function getGifts($data = array()) {
		$sql = "SELECT g.gift_id, g.code, g.image, g.status, g.url, g.sort_order, g.date_added, g.date_modified, gd.name 
                FROM " . DB_PREFIX . "gift g 
                LEFT JOIN " . DB_PREFIX . "gift_description gd ON (g.gift_id = gd.gift_id) 
                WHERE gd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND gd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND g.code LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND g.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = array(
			'gd.name',
			'g.code',
			'g.status',
			'g.sort_order',
			'g.date_added',
			'g.date_modified'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY gd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalGifts($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "gift g LEFT JOIN " . DB_PREFIX . "gift_description gd ON (g.gift_id = gd.gift_id) WHERE gd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND gd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND g.code LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND g.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getGiftFilters($gift_id) {
		$gift_filter_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "gift_filter WHERE gift_id = '" . (int)$gift_id . "'");

		foreach ($query->rows as $result) {
			$gift_filter_data[] = $result['filter_id'];
		}

		return $gift_filter_data;
	}
}