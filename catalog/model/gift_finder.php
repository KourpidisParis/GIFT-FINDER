<?php
class ModelExtensionModuleGiftFinder extends Model {
    public function getFilterGroups() {
        $filter_group_data = array();

        // Only get filter groups that have filters which are actually used by gifts
        $filter_group_query = $this->db->query("
            SELECT DISTINCT fg.filter_group_id, fg.sort_order, fgd.name 
            FROM " . DB_PREFIX . "filter_group fg 
            LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) 
            LEFT JOIN " . DB_PREFIX . "filter f ON (fg.filter_group_id = f.filter_group_id)
            LEFT JOIN " . DB_PREFIX . "gift_filter gf ON (f.filter_id = gf.filter_id)
            LEFT JOIN " . DB_PREFIX . "gift g ON (gf.gift_id = g.gift_id)
            WHERE fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND g.status = '1'
            AND g.gift_id IS NOT NULL
            ORDER BY fg.sort_order, fgd.name
        ");

        foreach ($filter_group_query->rows as $filter_group) {
            $filter_data = array();

            // Only get filters that are actually used by active gifts
            $filter_query = $this->db->query("
                SELECT DISTINCT f.filter_id, f.sort_order, fd.name 
                FROM " . DB_PREFIX . "filter f 
                LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) 
                LEFT JOIN " . DB_PREFIX . "gift_filter gf ON (f.filter_id = gf.filter_id)
                LEFT JOIN " . DB_PREFIX . "gift g ON (gf.gift_id = g.gift_id)
                WHERE f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' 
                AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND g.status = '1'
                AND g.gift_id IS NOT NULL
                ORDER BY f.sort_order, fd.name
            ");

            foreach ($filter_query->rows as $filter) {
                $filter_data[] = array(
                    'filter_id' => $filter['filter_id'],
                    'name'      => $filter['name']
                );
            }

            // Only add filter group if it has filters with associated gifts
            if ($filter_data) {
                $filter_group_data[] = array(
                    'filter_group_id' => $filter_group['filter_group_id'],
                    'name'            => $filter_group['name'],
                    'filter'          => $filter_data
                );
            }
        }

        return $filter_group_data;
    }

    public function getGifts($data = array()) {
        $sql = "SELECT g.gift_id, g.image, g.status, g.url, g.sort_order, gd.name 
                FROM " . DB_PREFIX . "gift g 
                LEFT JOIN " . DB_PREFIX . "gift_description gd ON (g.gift_id = gd.gift_id) 
                WHERE gd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        // Handle multiple filter IDs with OR within groups, AND across groups
        if (!empty($data['filter_filter_ids']) && is_array($data['filter_filter_ids'])) {
            $filter_ids = array_map('intval', $data['filter_filter_ids']);
            $filter_ids = array_filter($filter_ids); // Remove zeros
            
            if (!empty($filter_ids)) {
                // Group filters by filter_group_id
                $filter_groups = array();
                
                // Get filter group for each filter_id
                foreach ($filter_ids as $filter_id) {
                    $group_query = $this->db->query("SELECT filter_group_id FROM " . DB_PREFIX . "filter WHERE filter_id = '" . (int)$filter_id . "'");
                    if ($group_query->num_rows) {
                        $group_id = $group_query->row['filter_group_id'];
                        if (!isset($filter_groups[$group_id])) {
                            $filter_groups[$group_id] = array();
                        }
                        $filter_groups[$group_id][] = $filter_id;
                    }
                }
                
                // Apply AND logic across different groups, OR within same group
                foreach ($filter_groups as $group_id => $group_filters) {
                    if (count($group_filters) == 1) {
                        // Single filter in group
                        $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id = '" . (int)$group_filters[0] . "')";
                    } else {
                        // Multiple filters in same group - use OR
                        $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id IN (" . implode(',', array_map('intval', $group_filters)) . "))";
                    }
                }
            }
        } elseif (!empty($data['filter_filter_id'])) {
            // Backward compatibility for single filter
            $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id = '" . (int)$data['filter_filter_id'] . "')";
        }

        $sql .= " AND g.status = '1'";

        $sql .= " GROUP BY g.gift_id";

        $sort_data = array(
            'gd.name',
            'g.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY g.sort_order";
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
        $sql = "SELECT COUNT(DISTINCT g.gift_id) AS total 
                FROM " . DB_PREFIX . "gift g 
                LEFT JOIN " . DB_PREFIX . "gift_description gd ON (g.gift_id = gd.gift_id) 
                WHERE gd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        // Handle multiple filter IDs with OR within groups, AND across groups
        if (!empty($data['filter_filter_ids']) && is_array($data['filter_filter_ids'])) {
            $filter_ids = array_map('intval', $data['filter_filter_ids']);
            $filter_ids = array_filter($filter_ids); // Remove zeros
            
            if (!empty($filter_ids)) {
                // Group filters by filter_group_id
                $filter_groups = array();
                
                // Get filter group for each filter_id
                foreach ($filter_ids as $filter_id) {
                    $group_query = $this->db->query("SELECT filter_group_id FROM " . DB_PREFIX . "filter WHERE filter_id = '" . (int)$filter_id . "'");
                    if ($group_query->num_rows) {
                        $group_id = $group_query->row['filter_group_id'];
                        if (!isset($filter_groups[$group_id])) {
                            $filter_groups[$group_id] = array();
                        }
                        $filter_groups[$group_id][] = $filter_id;
                    }
                }
                
                // Apply AND logic across different groups, OR within same group
                foreach ($filter_groups as $group_id => $group_filters) {
                    if (count($group_filters) == 1) {
                        // Single filter in group
                        $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id = '" . (int)$group_filters[0] . "')";
                    } else {
                        // Multiple filters in same group - use OR
                        $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id IN (" . implode(',', array_map('intval', $group_filters)) . "))";
                    }
                }
            }
        } elseif (!empty($data['filter_filter_id'])) {
            // Backward compatibility for single filter
            $sql .= " AND g.gift_id IN (SELECT gift_id FROM " . DB_PREFIX . "gift_filter WHERE filter_id = '" . (int)$data['filter_filter_id'] . "')";
        }

        $sql .= " AND g.status = '1'";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}