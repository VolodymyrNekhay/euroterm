<?php
class ModelLocalisationCountry extends Model {
	public function getCountry($country_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = '" . (int)$country_id . "' AND status = '1'");

		return $query->row;
	}

	public function getCountries() {
		$country_data = $this->cache->get('country.catalog');
		//8-custom-code: get names based on selected language (front-end)
		//if (!$country_data) {
			//$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' ORDER BY name ASC");
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country_" . str_replace("-","_",$this->session->data['language']) . " WHERE status = '1' ORDER BY name ASC");

			$country_data = $query->rows;

			$this->cache->set('country.catalog', $country_data);
		//}

		return $country_data;
	}
}