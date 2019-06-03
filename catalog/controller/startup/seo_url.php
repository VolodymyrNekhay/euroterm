<?php
class ControllerStartupSeoUrl extends Controller {
	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

            //LANGPARAM 3 first check if it is ru-ru or ua-uk and only after unset!!!
            if ($parts[0] == 'ru-ru' || $parts[0] == 'ua-uk') {
                unset($parts[0]);
            }

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}
			}
		}
	}

	public function rewrite($link, $forceLang = null) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
				    
                    $language_id = (int)$this->config->get('config_language_id'); //default/current
                    
                    //LANGPARAM 2.2
                    if ($forceLang != null) {
                        
                        //echo "<pre>";
                        if (isset($this->request->get['_route_'])) {
                            $parts = explode('/', $this->request->get['_route_']);
                            
                            if ($forceLang == 'ru-ru' && $parts[0] == 'ua-uk') {
                                $language_id = 2;
                            }
                            if ($forceLang == 'ua-uk' && $parts[0] == 'ru-ru') {
                                $language_id = 3;
                            }
                        }
                        //echo "</pre>";
                    }
                    
                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . $language_id . "'");
					

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

                        unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);
                    
                    //LANGPARAM 2.2
                    $language_id = (int)$this->config->get('config_language_id'); //default/current
                    
                    if ($forceLang != null) {
                        if (isset($this->request->get['_route_'])) {
                            $parts = explode('/', $this->request->get['_route_']);
                            
                            if ($forceLang == 'ru-ru' && $parts[0] == 'ua-uk') {
                                $language_id = 2;
                            }
                            if ($forceLang == 'ua-uk' && $parts[0] == 'ru-ru') {
                                $language_id = 3;
                            }
                        }
                    }

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . $language_id . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				}
			}
		}

		if ($url) {
		   //LANGPARAM 2.1
           if ($forceLang != null) {
               if (isset($this->request->get['_route_'])) {
                   $parts = explode('/', $this->request->get['_route_']);
               
                   if ($forceLang == 'ru-ru' && $parts[0] == 'ua-uk') {
                       $url = '/' . 'ru-ru' . $url;
                   }
                   if ($forceLang == 'ua-uk' && $parts[0] == 'ru-ru') {
                       $url = '/' . 'ua-uk' . $url;
                   }
                   if ($forceLang == $parts[0]) {
                       $url = '/' . $forceLang . $url;
                   }
               }
           } else {
                $url = '/' . $this->session->data['language'] . $url;
           }
               
            unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

            $link = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;

			return $link;
		} else {
            //LANGPARAM 2.2
            if ($forceLang != null) {
                if ($forceLang == 'ru-ru' && strpos($link, 'lang=ua-uk') > 0) {
                    $link = str_replace('lang=ua-uk', 'lang=ru-ru', $link);
                }
                if ($forceLang == 'ua-uk' && strpos($link, 'lang=ru-ru') > 0) {
                    $link = str_replace('lang=ru-ru', 'lang=ua-uk', $link);
                }
            } else {
                if (strpos($link, 'lang=') === false) {
                    $link .= '&amp;lang='.$this->session->data['language'];
                }
            }

			return $link;
		}
	}
}
