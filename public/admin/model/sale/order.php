<?php
use yii\db\Query;
class ModelSaleOrder extends Model {
    public static $paymentMethod = [
        0 => '微信支付',
        1 => '支付宝支付'
    ];
    
    public function getPaymentMethod($pay_id) {
        return self::$paymentMethod[$pay_id];
    }
    
    public function getOrderByIds($order_ids) {
        if(is_array($order_ids)) {
            $order_ids = implode(",", $order_ids);
            //$order_query = $this->db->query("SELECT o.*, CONCAT(c.firstname, ' ', c.lastname) as customer FROM `" . DB_PREFIX . "order` o left join customer c on c.customer_id = o.customer_id WHERE o.order_id in (" . $order_ids . ")");
            $order_query = $this->db->query("SELECT s.product_ids, s.splitting_company, s.shipping_id, o.*, op.*, CONCAT(c.firstname, ' ', c.lastname) as customer FROM `" . DB_PREFIX . "order` o left join order_splitting s on s.order_id = o.order_id left join customer c on c.customer_id = o.customer_id right join order_product op on op.order_id = o.order_id WHERE op.order_id in (" . $order_ids . ") AND s.code = " . (int)$this->session->data['splitting_code']);
            return $order_query->rows;
        }
    }
    
    public function getOrderNumberByCity($order_child_id)
    {
        if ($this->session->data['splitting_code'] == 0) {
            return (new Query())->select('order_numbering_id')
                ->from(HI\TableName\ORDER_NUMBERING_SD)
                ->where(['order_child_id' => $order_child_id])
                ->one()['order_numbering_id'];
        } else {
            return (new Query())->select('order_numbering_id')
                ->from(HI\TableName\ORDER_NUMBERING_ZJ)
                ->where(['order_child_id' => $order_child_id])
                ->one()['order_numbering_id'];
        }
    }
    
    public function doQueryProductCode($productId)
    {
        return (new Query())->select('mpn')
            ->from(HI\TableName\PRODUCT)
            ->where(['product_id' => $productId, 'status' => 1])
            ->one()['mpn'];
    }
    
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT o.*, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer FROM `" . DB_PREFIX . "order` o left join customer c on c.customer_id = o.customer_id WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			$reward = 0;

			$order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

			foreach ($order_product_query->rows as $product) {
				$reward += $product['reward'];
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			if ($order_query->row['affiliate_id']) {
				$affiliate_id = $order_query->row['affiliate_id'];
			} else {
				$affiliate_id = 0;
			}

			$this->load->model('marketing/affiliate');

			$affiliate_info = $this->model_marketing_affiliate->getAffiliate($affiliate_id);

			if ($affiliate_info) {
				$affiliate_firstname = $affiliate_info['firstname'];
				$affiliate_lastname = $affiliate_info['lastname'];
			} else {
				$affiliate_firstname = '';
				$affiliate_lastname = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
				$language_directory = $language_info['directory'];
			} else {
				$language_code = '';
				$language_directory = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
			    'order_number'                => $order_query->row['order_number'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'customer'                => $order_query->row['customer'],
				'customer_group_id'       => $order_query->row['customer_group_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'fax'                     => $order_query->row['fax'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'reward'                  => $reward,
				'order_status_id'         => $order_query->row['order_status_id'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'affiliate_firstname'     => $affiliate_firstname,
				'affiliate_lastname'      => $affiliate_lastname,
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'language_directory'      => $language_directory,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return;
		}
	}

	public function getOrders($data = array()) {
		//$sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";
	    $sql = "SELECT o.shipping_country, o.order_number, o.shipping_city, o.shipping_zone, o.order_id, p.payload, o.shipping_firstname, o.shipping_address_1, CONCAT(ct.lastname) as customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o left join customer ct on ct.customer_id = o.customer_id left join order_picture p on p.order_id = o.order_id";
		if (isset($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_order_number'])) {
		    $sql .= " AND order_number like '%" . (string)$data['filter_order_number'] . "%'";
		}
		
		if (!empty($data['filter_shipping_firstname'])) {
		    $sql .= " AND shipping_firstname = '" . (string)$data['filter_shipping_firstname'] . "'";
		}
		
		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(ct.firstname, ' ', ct.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}

		$sort_data = array(
			'o.order_id',
			'customer',
			'status',
			'o.date_added',
			'o.date_modified',
			'o.total'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
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
	
	public function getHzOrder($order_id) {
	    $order_query = $this->db->query("SELECT s.shipping_id, s.splitting_company, o.*, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer FROM `" . DB_PREFIX . "order` o left join customer c on c.customer_id = o.customer_id left join order_splitting s on s.order_id = o.order_id WHERE s.code = " . (int)$this->session->data['splitting_code'] . " AND o.order_id = '" . (int)$order_id . "'");
	
	    if ($order_query->num_rows) {
	        $reward = 0;
	
	        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
	
	        foreach ($order_product_query->rows as $product) {
	            $reward += $product['reward'];
	        }
	
	        $this->load->model('localisation/language');
	
	        $language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);
	
	        if ($language_info) {
	            $language_code = $language_info['code'];
	            $language_directory = $language_info['directory'];
	        } else {
	            $language_code = '';
	            $language_directory = '';
	        }
	
	        return array(
	            'order_id'                => $order_query->row['order_id'],
	            'order_number'                => $order_query->row['order_number'],
	            'firstname'               => $order_query->row['firstname'],
	            'lastname'                => $order_query->row['lastname'],
	            'email'                   => $order_query->row['email'],
	            'telephone'               => $order_query->row['telephone'],
	            'fax'                     => $order_query->row['fax'],
	            'custom_field'            => json_decode($order_query->row['custom_field'], true),
	            'shipping_firstname'      => $order_query->row['shipping_firstname'],
	            'shipping_id'      => $order_query->row['shipping_id'],
	            'splitting_company'      => $order_query->row['splitting_company'],
	            'shipping_lastname'       => $order_query->row['shipping_lastname'],
	            'shipping_company'        => $order_query->row['shipping_company'],
	            'shipping_address_1'      => $order_query->row['shipping_address_1'],
	            'shipping_address_2'      => $order_query->row['shipping_address_2'],
	            'shipping_postcode'       => $order_query->row['shipping_postcode'],
	            'shipping_city'           => $order_query->row['shipping_city'],
	            'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
	            'shipping_zone'           => $order_query->row['shipping_zone'],
	            'shipping_country_id'     => $order_query->row['shipping_country_id'],
	            'shipping_country'        => $order_query->row['shipping_country'],
	            'shipping_address_format' => $order_query->row['shipping_address_format'],
	            'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
	            'shipping_method'         => $order_query->row['shipping_method'],
	            'shipping_code'           => $order_query->row['shipping_code'],
	            'comment'                 => $order_query->row['comment'],
	            'total'                   => $order_query->row['total'],
	            'reward'                  => $reward,
	            'order_status_id'         => $order_query->row['order_status_id'],
	            'affiliate_id'            => $order_query->row['affiliate_id'],
	            'commission'              => $order_query->row['commission'],
	            'language_id'             => $order_query->row['language_id'],
	            'language_code'           => $language_code,
	            'language_directory'      => $language_directory,
	            'currency_id'             => $order_query->row['currency_id'],
	            'currency_code'           => $order_query->row['currency_code'],
	            'currency_value'          => $order_query->row['currency_value'],
	            'ip'                      => $order_query->row['ip'],
	            'forwarded_ip'            => $order_query->row['forwarded_ip'],
	            'user_agent'              => $order_query->row['user_agent'],
	            'accept_language'         => $order_query->row['accept_language'],
	            'date_added'              => $order_query->row['date_added'],
	            'date_modified'           => $order_query->row['date_modified']
	        );
	    } else {
	        return;
	    }
	}
	
	public function getHzOrders($data = array()) {
	    //$sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";
	    switch ((int)$data['filter_splitting_code']) {
	        case 0:
	            $numbers_table = "order_numbering_sd";
	            break;
	        case 1:
	            $numbers_table = "order_numbering_zj";
	            break;
	        default:
	            $numbers_table = "";
	    }
	    $sql = "SELECT n.order_numbering_id, s.product_ids, s.splitting_company, s.shipping_id, s.parcle, o.telephone, s.order_status_id, o.shipping_country, o.order_number, o.shipping_city, o.shipping_zone, o.order_id, p.payload, o.shipping_firstname, o.shipping_address_1, CONCAT(ct.firstname, ' ', ct.lastname) as customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = s.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o left join customer ct on ct.customer_id = o.customer_id left join order_picture p on p.order_id = o.order_id left join order_splitting s on s.order_id = o.order_id left join {$numbers_table} n on s.order_child_id = n.order_child_id";
	    if (isset($data['filter_order_status'])) {
	        $implode = array();
	        if($data['filter_order_status'] == 20) {
	            $data['filter_order_status'] = "18,20";
	        }
	        $order_statuses = explode(',', $data['filter_order_status']);
	        foreach ($order_statuses as $order_status_id) {
	            $implode[] = "s.order_status_id = '" . (int)$order_status_id . "'";
	        }
	
	        if ($implode) {
	            $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
	        }
	    } else {
	        if(in_array("processing", explode('/', $_REQUEST['route']))) {
	            $hz_order_status = implode(',', Order::$configHzProcessingStatus);
	        }else {
	            $hz_order_status = implode(',', Order::$hzOrderStatusIds);
	        }
	        $sql .= " WHERE s.order_status_id in ({$hz_order_status}) AND o.order_status_id in ({$hz_order_status})";
	    }

	    if (!empty($data['filter_splitting_code']) || (int)$data['filter_splitting_code'] === 0) {
	        $sql .= " AND s.code = '" . (int)$data['filter_splitting_code'] . "'";
	    }
	    
	    if (!empty($data['filter_order_id'])) {
	        $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
	    }
	
	    if (!empty($data['filter_order_number'])) {
	        $sql .= " AND o.order_number like '%" . (string)$data['filter_order_number'] . "%'";
	    }
	    
	    if (!empty($data['filter_shipping_firstname'])) {
	        $sql .= " AND o.shipping_firstname like '%" . (string)$data['filter_shipping_firstname'] . "%'";
	    }
	    
	    if (!empty($data['filter_customer'])) {
	        $sql .= " AND CONCAT(ct.firstname, ' ', ct.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
	    }
	
	    if (!empty($data['filter_date_added'])) {
	        $sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
	    }
	
	    if (!empty($data['filter_date_modified'])) {
	        $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
	    }
	
	    if (!empty($data['filter_total'])) {
	        $sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
	    }
	
	    $sort_data = array(
	        'o.order_id',
	        'o.order_number',
	        'customer',
	        'status',
	        'o.date_added',
	        'o.date_modified',
	        'o.total'
	    );
	
	    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
	        $sql .= " ORDER BY " . $data['sort'];
	    } else {
	        $sql .= " ORDER BY o.order_id";
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

	public function getOrderProducts($order_id) {
		//$query = $this->db->query("SELECT op.*, o.total as pay_total, p.model as name, p.location, oc.name as coupon_name, oc.value as coupon_price FROM " . DB_PREFIX . "order_product op left join product p on p.product_id = op.product_id left join order_coupon oc on oc.order_id = op.order_id left join `order` o on o.order_id = op.order_id WHERE op.order_id = '" . (int)$order_id . "'");
		$query = $this->db->query("SELECT op.*, o.total as pay_total, p.model as name, p.location FROM " . DB_PREFIX . "order_product op left join product p on p.product_id = op.product_id left join `order` o on o.order_id = op.order_id WHERE op.order_id = '" . (int)$order_id . "'");
		return $query->rows;
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderVoucherByVoucherId($voucher_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE voucher_id = '" . (int)$voucher_id . "'");

		return $query->row;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}
	
	public function getHzTotalOrders($data = array()) {
	    $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o inner join order_splitting s on s.order_id = o.order_id ";
	
	    if (isset($data['filter_order_status'])) {
	        $implode = array();
	        if($data['filter_order_status'] == 20) {
	            $data['filter_order_status'] = "18,20";
	        }
	        $order_statuses = explode(',', $data['filter_order_status']);
	
	        foreach ($order_statuses as $order_status_id) {
	            $implode[] = "s.order_status_id = '" . (int)$order_status_id . "'";
	        }
	
	        if ($implode) {
	            $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
	        }
	    } else {
	        if(in_array("processing", explode('/', $_REQUEST['route']))) {
	            $hz_order_status = implode(',', Order::$configHzProcessingStatus);
	        }else {
	            $hz_order_status = implode(',', Order::$hzOrderStatusIds);
	        }
	        $sql .= " WHERE s.order_status_id in ({$hz_order_status}) AND o.order_status_id in ({$hz_order_status})";
	    }
	    
	    $sql .= " AND s.code = '" . @(int)$data['filter_splitting_code'] . "'";
	    
	    if (!empty($data['filter_order_id'])) {
	        $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
	    }
	
	    if (!empty($data['filter_order_number'])) {
	        $sql .= " AND order_number like '%" . (string)$data['filter_order_number'] . "%'";
	    }
	
	    if (!empty($data['filter_shipping_firstname'])) {
	        $sql .= " AND shipping_firstname like '%" . (string)$data['filter_shipping_firstname'] . "%'";
	    }
	
	    if (!empty($data['filter_customer'])) {
	        $sql .= " AND CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
	    }
	
	    if (!empty($data['filter_date_added'])) {
	        $sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
	    }
	
	    if (!empty($data['filter_date_modified'])) {
	        $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
	    }
	
	    if (!empty($data['filter_total'])) {
	        $sql .= " AND total = '" . (float)$data['filter_total'] . "'";
	    }

	    $query = $this->db->query($sql);
	
	    return $query->row['total'];
	}
	
	public function getTotalOrders($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order`";

		if (isset($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else {
			$sql .= " WHERE order_status_id > '0'";
		}
		
		if (!empty($data['filter_order_id'])) {
			$sql .= " AND order_id = '" . (int)$data['filter_order_id'] . "'";
		}
		
		if (!empty($data['filter_order_number'])) {
		    $sql .= " AND order_number like '%" . (string)$data['filter_order_number'] . "%'";
		}
		
		if (!empty($data['filter_shipping_firstname'])) {
		    $sql .= " AND shipping_firstname like '%" . (string)$data['filter_shipping_firstname'] . "%'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND total = '" . (float)$data['filter_total'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalOrdersByStoreId($store_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE store_id = '" . (int)$store_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrdersByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE order_status_id = '" . (int)$order_status_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByProcessingStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_processing_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode));

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByCompleteStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_complete_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode) . "");

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByLanguageId($language_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE language_id = '" . (int)$language_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByCurrencyId($currency_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE currency_id = '" . (int)$currency_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function createInvoiceNo($order_id) {
		$order_info = $this->getOrder($order_id);

		if ($order_info && !$order_info['invoice_no']) {
			$query = $this->db->query("SELECT MAX(invoice_no) AS invoice_no FROM `" . DB_PREFIX . "order` WHERE invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "'");

			if ($query->row['invoice_no']) {
				$invoice_no = $query->row['invoice_no'] + 1;
			} else {
				$invoice_no = 1;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_no = '" . (int)$invoice_no . "', invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "' WHERE order_id = '" . (int)$order_id . "'");

			return $order_info['invoice_prefix'] . $invoice_no;
		}
	}

	public function getOrderHistories($order_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT oh.date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalOrderHistories($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderHistoriesByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_status_id = '" . (int)$order_status_id . "'");

		return $query->row['total'];
	}

	public function getEmailsByProductsOrdered($products, $start, $end) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT DISTINCT email FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0' LIMIT " . (int)$start . "," . (int)$end);

		return $query->rows;
	}

	public function getTotalEmailsByProductsOrdered($products) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT DISTINCT email FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0'");

		return $query->row['total'];
	}
	
	public function editHzOrder($order_id, $data) {
	    $sql = "";
	    $sqlShip = "";
	    $attributeArray = ['shipping_firstname', 'telephone', 'shipping_country', 'shipping_city', 'shipping_zone', 'shipping_address_1'];
	    $attributeShippingArray = ['shipping_id', 'splitting_company'];
	    foreach ($data as $attribute => $order_attribute) {
	        if(in_array($attribute, $attributeShippingArray) && !empty($order_attribute)) {
	            $sqlShip .= "`".$attribute."` = '" . $this->db->escape($order_attribute) . "',";
	            if($attribute == "shipping_id") {
	                $shipping_id = $order_attribute;
	            }
	            if($attribute == "splitting_company") {
	                $splitting_company = $order_attribute;
	            }
	        }
	        if(!empty($order_attribute) && in_array($attribute, $attributeArray)) {
	            $sql .= "`".$attribute."` = '" . $this->db->escape($order_attribute) . "',";
	        }
	    }
	    $where = " where order_id = '" . (int)$order_id . "'";
	    if(!empty($sqlShip)) {
	        $sqlShip = substr($sqlShip, 0, -1);
	        $this->db->query("UPDATE `" . DB_PREFIX . "order_splitting` SET {$sqlShip} where order_id = '{$order_id}' AND code = " . (int)$this->session->data['splitting_code']);
	        if(isset($shipping_id) && isset($splitting_company)) {
	            $express = new Express();
	            $express -> subscribe($splitting_company, [$shipping_id]);
	            Order::shipping([$order_id], $this->session->data['splitting_code'], $this->user->getId());
	        }
	    }
	    if(!empty($sql)) {
	        $sql = substr($sql, 0, -1);
	        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET " . $sql . $where);
	    }
	}
	
	public function expressCompanys() {
	    //查询用到的物流公司
	    //$codes = implode(",", Order::$hzSplittingCompanys);
	    //return $query = $this->db->query("select * from express_code where code in ({$codes})") -> rows;
	    return DataBase::getTableDataRows('express_code', ['code' => Order::$hzSplittingCompanys], "", "*");
	}
	
	public function updateOrderStatus($order_ids, $status = 0, $code = null) {
	    //修改订单状态
	    $order_ids = implode(",", $order_ids);
	    $where = " where order_id in (".$order_ids.")";
	    if(is_null($code)) {
	        return $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = ". (int)$status . $where);
	    }else {
	        $where .= " AND code = " . (int)$code . "";
	        return $this->db->query("UPDATE `" . DB_PREFIX . "order_splitting` SET order_status_id = ". (int)$status . $where);
	    }
	}
	
	public function insertOrderlog($order_ids, $status = 0, $status_desc = '') {
	    //添加订单订单更新日志
        foreach ($order_ids as $sub_order_id) {
            $payload = json_encode(['status' => $status, "status_desc" => $status_desc]);
            $this->db->query("INSERT INTO order_log SET order_id = " . (int)$sub_order_id . ", operator = " . $this->session->data['user_id'] . ", payload =  '" . $this->db->escape($payload) . "', date_added = '" . date("Y-m-d H:i:s", time()) . "'");
        }
	}
	
	public function getOrderStatuslog($order_id) {
	    //获取订单订单更新日志
	    $query = $this->db->query("select * from order_log where order_id = " . (int)$order_id) -> rows;
	    $orderLogs = [];
	    foreach ($query as $orderLog) {
	        $payload = json_decode($orderLog['payload'], true);
	        if(!empty($payload)) {
	            $orderLog["status"] = $payload['status'];
	            $orderLog["status_desc"] = $payload['status_desc'];
	            $orderLogs[$payload['status']] = $orderLog; 
	        }
	    }
	    return $orderLogs;
	}
}